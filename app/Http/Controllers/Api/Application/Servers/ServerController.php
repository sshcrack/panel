<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Kriegerhost\Models\Server;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Kriegerhost\Services\Servers\ServerCreationService;
use Kriegerhost\Services\Servers\ServerDeletionService;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;
use Kriegerhost\Transformers\Api\Application\ServerTransformer;
use Kriegerhost\Http\Requests\Api\Application\Servers\GetServerRequest;
use Kriegerhost\Http\Requests\Api\Application\Servers\GetServersRequest;
use Kriegerhost\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Kriegerhost\Http\Requests\Api\Application\Servers\StoreServerRequest;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;

class ServerController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Servers\ServerCreationService
     */
    private $creationService;

    /**
     * @var \Kriegerhost\Services\Servers\ServerDeletionService
     */
    private $deletionService;

    /**
     * @var \Kriegerhost\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ServerController constructor.
     */
    public function __construct(
        ServerCreationService $creationService,
        ServerDeletionService $deletionService,
        ServerRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
    }

    /**
     * Return all of the servers that currently exist on the Panel.
     */
    public function index(GetServersRequest $request): array
    {
        $servers = QueryBuilder::for(Server::query())
            ->allowedFilters(['uuid', 'uuidShort', 'name', 'image', 'external_id'])
            ->allowedSorts(['id', 'uuid'])
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($servers)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * Create a new server on the system.
     *
     * @throws \Throwable
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Kriegerhost\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function store(StoreServerRequest $request): JsonResponse
    {
        $server = $this->creationService->handle($request->validated(), $request->getDeploymentObject());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->respond(201);
    }

    /**
     * Show a single server transformed for the application API.
     */
    public function view(GetServerRequest $request): array
    {
        return $this->fractal->item($request->getModel(Server::class))
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * @throws \Kriegerhost\Exceptions\DisplayException
     */
    public function delete(ServerWriteRequest $request, Server $server, string $force = ''): Response
    {
        $this->deletionService->withForce($force === 'force')->handle($server);

        return $this->returnNoContent();
    }
}
