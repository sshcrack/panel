<?php

namespace Kriegerhost\Repositories\Wings;

use Webmozart\Assert\Assert;
use Kriegerhost\Models\Server;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\TransferException;
use Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonCommandRepository extends DaemonRepository
{
    /**
     * Sends a command or multiple commands to a running server instance.
     *
     * @param string|string[] $command
     *
     * @throws \Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function send($command): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/commands', $this->server->uuid),
                [
                    'json' => ['commands' => is_array($command) ? $command : [$command]],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
