<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Database;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Services\Databases\DatabasePasswordService;
use Kriegerhost\Services\Databases\DatabaseManagementService;
use Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface;
use Kriegerhost\Transformers\Api\Application\ServerDatabaseTransformer;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;
use Kriegerhost\Http\Requests\Api\Application\Servers\Databases\GetServerDatabaseRequest;
use Kriegerhost\Http\Requests\Api\Application\Servers\Databases\GetServerDatabasesRequest;
use Kriegerhost\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest;
use Kriegerhost\Http\Requests\Api\Application\Servers\Databases\StoreServerDatabaseRequest;

class DatabaseController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Databases\DatabaseManagementService
     */
    private $databaseManagementService;

    /**
     * @var \Kriegerhost\Services\Databases\DatabasePasswordService
     */
    private $databasePasswordService;

    /**
     * @var \Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * DatabaseController constructor.
     */
    public function __construct(
        DatabaseManagementService $databaseManagementService,
        DatabasePasswordService $databasePasswordService,
        DatabaseRepositoryInterface $repository
    ) {
        parent::__construct();

        $this->databaseManagementService = $databaseManagementService;
        $this->databasePasswordService = $databasePasswordService;
        $this->repository = $repository;
    }

    /**
     * Return a listing of all databases currently available to a single
     * server.
     */
    public function index(GetServerDatabasesRequest $request, Server $server): array
    {
        return $this->fractal->collection($server->databases)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Return a single server database.
     */
    public function view(GetServerDatabaseRequest $request, Server $server, Database $database): array
    {
        return $this->fractal->item($database)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Reset the password for a specific server database.
     *
     * @throws \Throwable
     */
    public function resetPassword(ServerDatabaseWriteRequest $request, Server $server, Database $database): JsonResponse
    {
        $this->databasePasswordService->handle($database);

        return JsonResponse::create([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Create a new database on the Panel for a given server.
     *
     * @throws \Throwable
     */
    public function store(StoreServerDatabaseRequest $request, Server $server): JsonResponse
    {
        $database = $this->databaseManagementService->create($server, array_merge($request->validated(), [
            'database' => $request->databaseName(),
        ]));

        return $this->fractal->item($database)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->addMeta([
                'resource' => route('api.application.servers.databases.view', [
                    'server' => $server->id,
                    'database' => $database->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Handle a request to delete a specific server database from the Panel.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(ServerDatabaseWriteRequest $request): Response
    {
        $this->databaseManagementService->delete($request->getModel(Database::class));

        return response('', 204);
    }
}
