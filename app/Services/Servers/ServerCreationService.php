<?php

namespace Kriegerhost\Services\Servers;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use Kriegerhost\Models\Egg;
use Kriegerhost\Models\User;
use Webmozart\Assert\Assert;
use Kriegerhost\Models\Server;
use Illuminate\Support\Collection;
use Kriegerhost\Models\Allocation;
use Illuminate\Database\ConnectionInterface;
use Kriegerhost\Models\Objects\DeploymentObject;
use Kriegerhost\Repositories\Eloquent\EggRepository;
use Kriegerhost\Repositories\Eloquent\ServerRepository;
use Kriegerhost\Repositories\Wings\DaemonServerRepository;
use Kriegerhost\Services\Deployment\FindViableNodesService;
use Kriegerhost\Repositories\Eloquent\ServerVariableRepository;
use Kriegerhost\Services\Deployment\AllocationSelectionService;
use Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException;

class ServerCreationService
{
    /**
     * @var \Kriegerhost\Services\Deployment\AllocationSelectionService
     */
    private $allocationSelectionService;

    /**
     * @var \Kriegerhost\Services\Servers\ServerConfigurationStructureService
     */
    private $configurationStructureService;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Kriegerhost\Services\Deployment\FindViableNodesService
     */
    private $findViableNodesService;

    /**
     * @var \Kriegerhost\Services\Servers\VariableValidatorService
     */
    private $validatorService;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\EggRepository
     */
    private $eggRepository;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\ServerVariableRepository
     */
    private $serverVariableRepository;

    /**
     * @var \Kriegerhost\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Kriegerhost\Services\Servers\ServerDeletionService
     */
    private $serverDeletionService;

    /**
     * CreationService constructor.
     *
     * @param \Kriegerhost\Services\Servers\ServerConfigurationStructureService $configurationStructureService
     * @param \Kriegerhost\Services\Servers\ServerDeletionService $serverDeletionService
     * @param \Kriegerhost\Services\Servers\VariableValidatorService $validatorService
     */
    public function __construct(
        AllocationSelectionService $allocationSelectionService,
        ConnectionInterface $connection,
        DaemonServerRepository $daemonServerRepository,
        EggRepository $eggRepository,
        FindViableNodesService $findViableNodesService,
        ServerConfigurationStructureService $configurationStructureService,
        ServerDeletionService $serverDeletionService,
        ServerRepository $repository,
        ServerVariableRepository $serverVariableRepository,
        VariableValidatorService $validatorService
    ) {
        $this->allocationSelectionService = $allocationSelectionService;
        $this->configurationStructureService = $configurationStructureService;
        $this->connection = $connection;
        $this->findViableNodesService = $findViableNodesService;
        $this->validatorService = $validatorService;
        $this->eggRepository = $eggRepository;
        $this->repository = $repository;
        $this->serverVariableRepository = $serverVariableRepository;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->serverDeletionService = $serverDeletionService;
    }

    /**
     * Create a server on the Panel and trigger a request to the Daemon to begin the server
     * creation process. This function will attempt to set as many additional values
     * as possible given the input data. For example, if an allocation_id is passed with
     * no node_id the node_is will be picked from the allocation.
     *
     * @throws \Throwable
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Deployment\NoViableNodeException
     * @throws \Kriegerhost\Exceptions\Service\Deployment\NoViableAllocationException
     */
    public function handle(array $data, DeploymentObject $deployment = null): Server
    {
        // If a deployment object has been passed we need to get the allocation
        // that the server should use, and assign the node from that allocation.
        if ($deployment instanceof DeploymentObject) {
            $allocation = $this->configureDeployment($data, $deployment);
            $data['allocation_id'] = $allocation->id;
            $data['node_id'] = $allocation->node_id;
        }

        // Auto-configure the node based on the selected allocation
        // if no node was defined.
        if (empty($data['node_id'])) {
            Assert::false(empty($data['allocation_id']), 'Expected a non-empty allocation_id in server creation data.');

            $data['node_id'] = Allocation::query()->findOrFail($data['allocation_id'])->node_id;
        }

        if (empty($data['nest_id'])) {
            Assert::false(empty($data['egg_id']), 'Expected a non-empty egg_id in server creation data.');

            $data['nest_id'] = Egg::query()->findOrFail($data['egg_id'])->nest_id;
        }

        $eggVariableData = $this->validatorService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle(Arr::get($data, 'egg_id'), Arr::get($data, 'environment', []));

        // Due to the design of the Daemon, we need to persist this server to the disk
        // before we can actually create it on the Daemon.
        //
        // If that connection fails out we will attempt to perform a cleanup by just
        // deleting the server itself from the system.
        /** @var \Kriegerhost\Models\Server $server */
        $server = $this->connection->transaction(function () use ($data, $eggVariableData) {
            // Create the server and assign any additional allocations to it.
            $server = $this->createModel($data);

            $this->storeAssignedAllocations($server, $data);
            $this->storeEggVariables($server, $eggVariableData);

            return $server;
        }, 5);

        try {
            $this->daemonServerRepository->setServer($server)->create(
                array_merge(
                    $this->configurationStructureService->handle($server),
                    [
                        'start_on_completion' => Arr::get($data, 'start_on_completion', false),
                    ],
                ),
            );
        } catch (DaemonConnectionException $exception) {
            $this->serverDeletionService->withForce(true)->handle($server);

            throw $exception;
        }

        return $server;
    }

    /**
     * Gets an allocation to use for automatic deployment.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Kriegerhost\Exceptions\Service\Deployment\NoViableNodeException
     */
    private function configureDeployment(array $data, DeploymentObject $deployment): Allocation
    {
        /** @var \Illuminate\Support\Collection $nodes */
        $nodes = $this->findViableNodesService->setLocations($deployment->getLocations())
            ->setDisk(Arr::get($data, 'disk'))
            ->setMemory(Arr::get($data, 'memory'))
            ->handle();

        return $this->allocationSelectionService->setDedicated($deployment->isDedicated())
            ->setNodes($nodes->pluck('id')->toArray())
            ->setPorts($deployment->getPorts())
            ->handle();
    }

    /**
     * Store the server in the database and return the model.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    private function createModel(array $data): Server
    {
        $uuid = $this->generateUniqueUuidCombo();

        /** @var \Kriegerhost\Models\Server $model */
        $model = $this->repository->create([
            'external_id' => Arr::get($data, 'external_id'),
            'uuid' => $uuid,
            'uuidShort' => substr($uuid, 0, 8),
            'node_id' => Arr::get($data, 'node_id'),
            'name' => Arr::get($data, 'name'),
            'description' => Arr::get($data, 'description') ?? '',
            'status' => Server::STATUS_INSTALLING,
            'skip_scripts' => Arr::get($data, 'skip_scripts') ?? isset($data['skip_scripts']),
            'owner_id' => Arr::get($data, 'owner_id'),
            'memory' => Arr::get($data, 'memory'),
            'swap' => Arr::get($data, 'swap'),
            'disk' => Arr::get($data, 'disk'),
            'io' => Arr::get($data, 'io'),
            'cpu' => Arr::get($data, 'cpu'),
            'threads' => Arr::get($data, 'threads'),
            'oom_disabled' => Arr::get($data, 'oom_disabled') ?? true,
            'allocation_id' => Arr::get($data, 'allocation_id'),
            'nest_id' => Arr::get($data, 'nest_id'),
            'egg_id' => Arr::get($data, 'egg_id'),
            'startup' => Arr::get($data, 'startup'),
            'image' => Arr::get($data, 'image'),
            'database_limit' => Arr::get($data, 'database_limit') ?? 0,
            'allocation_limit' => Arr::get($data, 'allocation_limit') ?? 0,
            'backup_limit' => Arr::get($data, 'backup_limit') ?? 0,
        ]);

        return $model;
    }

    /**
     * Configure the allocations assigned to this server.
     */
    private function storeAssignedAllocations(Server $server, array $data)
    {
        $records = [$data['allocation_id']];
        if (isset($data['allocation_additional']) && is_array($data['allocation_additional'])) {
            $records = array_merge($records, $data['allocation_additional']);
        }

        Allocation::query()->whereIn('id', $records)->update([
            'server_id' => $server->id,
        ]);
    }

    /**
     * Process environment variables passed for this server and store them in the database.
     */
    private function storeEggVariables(Server $server, Collection $variables)
    {
        $records = $variables->map(function ($result) use ($server) {
            return [
                'server_id' => $server->id,
                'variable_id' => $result->id,
                'variable_value' => $result->value ?? '',
            ];
        })->toArray();

        if (!empty($records)) {
            $this->serverVariableRepository->insert($records);
        }
    }

    /**
     * Create a unique UUID and UUID-Short combo for a server.
     */
    private function generateUniqueUuidCombo(): string
    {
        $uuid = Uuid::uuid4()->toString();

        if (!$this->repository->isUniqueUuidCombo($uuid, substr($uuid, 0, 8))) {
            return $this->generateUniqueUuidCombo();
        }

        return $uuid;
    }
}
