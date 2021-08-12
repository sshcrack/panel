<?php

namespace Kriegerhost\Http\Requests\Api\Application\Servers;

use Kriegerhost\Models\Server;
use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Exceptions\Repository\RecordNotFoundException;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetExternalServerRequest extends ApplicationApiRequest
{
    /**
     * @var \Kriegerhost\Models\Server
     */
    private $serverModel;

    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested external user exists.
     */
    public function resourceExists(): bool
    {
        $repository = $this->container->make(ServerRepositoryInterface::class);

        try {
            $this->serverModel = $repository->findFirstWhere([
                ['external_id', '=', $this->route()->parameter('external_id')],
            ]);
        } catch (RecordNotFoundException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Return the server model for the requested external server.
     */
    public function getServerModel(): Server
    {
        return $this->serverModel;
    }
}
