<?php

namespace Kriegerhost\Http\Controllers\Api\Client\Servers;

use Kriegerhost\Models\Server;
use Kriegerhost\Repositories\Eloquent\SubuserRepository;
use Kriegerhost\Transformers\Api\Client\ServerTransformer;
use Kriegerhost\Services\Servers\GetUserPermissionsService;
use Kriegerhost\Http\Controllers\Api\Client\ClientApiController;
use Kriegerhost\Http\Requests\Api\Client\Servers\GetServerRequest;

class ServerController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Repositories\Eloquent\SubuserRepository
     */
    private $repository;

    /**
     * @var \Kriegerhost\Services\Servers\GetUserPermissionsService
     */
    private $permissionsService;

    /**
     * ServerController constructor.
     */
    public function __construct(GetUserPermissionsService $permissionsService, SubuserRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
        $this->permissionsService = $permissionsService;
    }

    /**
     * Transform an individual server into a response that can be consumed by a
     * client using the API.
     */
    public function index(GetServerRequest $request, Server $server): array
    {
        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->addMeta([
                'is_server_owner' => $request->user()->id === $server->owner_id,
                'user_permissions' => $this->permissionsService->handle($server, $request->user()),
            ])
            ->toArray();
    }
}
