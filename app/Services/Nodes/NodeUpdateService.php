<?php

namespace Kriegerhost\Services\Nodes;

use Illuminate\Support\Str;
use Kriegerhost\Models\Node;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Kriegerhost\Repositories\Eloquent\NodeRepository;
use Kriegerhost\Repositories\Wings\DaemonConfigurationRepository;
use Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException;
use Kriegerhost\Exceptions\Service\Node\ConfigurationNotPersistedException;

class NodeUpdateService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Kriegerhost\Repositories\Wings\DaemonConfigurationRepository
     */
    private $configurationRepository;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\NodeRepository
     */
    private $repository;

    /**
     * UpdateService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        Encrypter $encrypter,
        DaemonConfigurationRepository $configurationRepository,
        NodeRepository $repository
    ) {
        $this->connection = $connection;
        $this->configurationRepository = $configurationRepository;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Update the configuration values for a given node on the machine.
     *
     * @return \Kriegerhost\Models\Node
     *
     * @throws \Throwable
     */
    public function handle(Node $node, array $data, bool $resetToken = false)
    {
        if ($resetToken) {
            $data['daemon_token'] = $this->encrypter->encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH));
            $data['daemon_token_id'] = Str::random(Node::DAEMON_TOKEN_ID_LENGTH);
        }

        [$updated, $exception] = $this->connection->transaction(function () use ($data, $node) {
            /** @var \Kriegerhost\Models\Node $updated */
            $updated = $this->repository->withFreshModel()->update($node->id, $data, true, true);

            try {
                // If we're changing the FQDN for the node, use the newly provided FQDN for the connection
                // address. This should alleviate issues where the node gets pointed to a "valid" FQDN that
                // isn't actually running the daemon software, and therefore you can't actually change it
                // back.
                //
                // This makes more sense anyways, because only the Panel uses the FQDN for connecting, the
                // node doesn't actually care about this.
                //
                // @see https://github.com/kriegerhost/panel/issues/1931
                $node->fqdn = $updated->fqdn;

                $this->configurationRepository->setNode($node)->update($updated);
            } catch (DaemonConnectionException $exception) {
                Log::warning($exception, ['node_id' => $node->id]);

                // Never actually throw these exceptions up the stack. If we were able to change the settings
                // but something went wrong with Wings we just want to store the update and let the user manually
                // make changes as needed.
                //
                // This avoids issues with proxies such as CloudFlare which will see Wings as offline and then
                // inject their own response pages, causing this logic to get fucked up.
                //
                // @see https://github.com/kriegerhost/panel/issues/2712
                return [$updated, true];
            }

            return [$updated, false];
        });

        if ($exception) {
            throw new ConfigurationNotPersistedException(trans('exceptions.node.daemon_off_config_updated'));
        }

        return $updated;
    }
}
