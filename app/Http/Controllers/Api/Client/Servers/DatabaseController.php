<?php

namespace Kriegerhost\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Database;
use Kriegerhost\Repositories\Eloquent\DatabaseRepository;
use Kriegerhost\Services\Databases\DatabasePasswordService;
use Kriegerhost\Transformers\Api\Client\DatabaseTransformer;
use Kriegerhost\Services\Databases\DatabaseManagementService;
use Kriegerhost\Services\Databases\DeployServerDatabaseService;
use Kriegerhost\Http\Controllers\Api\Client\ClientApiController;
use Kriegerhost\Http\Requests\Api\Client\Servers\Databases\GetDatabasesRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Databases\StoreDatabaseRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Databases\DeleteDatabaseRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Databases\RotatePasswordRequest;

class DatabaseController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Services\Databases\DeployServerDatabaseService
     */
    private $deployDatabaseService;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\DatabaseRepository
     */
    private $repository;

    /**
     * @var \Kriegerhost\Services\Databases\DatabaseManagementService
     */
    private $managementService;

    /**
     * @var \Kriegerhost\Services\Databases\DatabasePasswordService
     */
    private $passwordService;

    /**
     * DatabaseController constructor.
     */
    public function __construct(
        DatabaseManagementService $managementService,
        DatabasePasswordService $passwordService,
        DatabaseRepository $repository,
        DeployServerDatabaseService $deployDatabaseService
    ) {
        parent::__construct();

        $this->deployDatabaseService = $deployDatabaseService;
        $this->repository = $repository;
        $this->managementService = $managementService;
        $this->passwordService = $passwordService;
    }

    /**
     * Return all of the databases that belong to the given server.
     */
    public function index(GetDatabasesRequest $request, Server $server): array
    {
        return $this->fractal->collection($server->databases)
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Create a new database for the given server and return it.
     *
     * @throws \Throwable
     * @throws \Kriegerhost\Exceptions\Service\Database\TooManyDatabasesException
     * @throws \Kriegerhost\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException
     */
    public function store(StoreDatabaseRequest $request, Server $server): array
    {
        $database = $this->deployDatabaseService->handle($server, $request->validated());

        return $this->fractal->item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Rotates the password for the given server model and returns a fresh instance to
     * the caller.
     *
     * @return array
     *
     * @throws \Throwable
     */
    public function rotatePassword(RotatePasswordRequest $request, Server $server, Database $database)
    {
        $this->passwordService->handle($database);
        $database->refresh();

        return $this->fractal->item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toArray();
    }

    /**
     * Removes a database from the server.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function delete(DeleteDatabaseRequest $request, Server $server, Database $database): Response
    {
        $this->managementService->delete($database);

        return Response::create('', Response::HTTP_NO_CONTENT);
    }
}
