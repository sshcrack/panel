<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Kriegerhost\Models\User;
use Kriegerhost\Models\Mount;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\MountServer;
use Prologue\Alerts\AlertsMessageBag;
use Kriegerhost\Exceptions\DisplayException;
use Kriegerhost\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Kriegerhost\Services\Servers\SuspensionService;
use Kriegerhost\Repositories\Eloquent\MountRepository;
use Kriegerhost\Services\Servers\ServerDeletionService;
use Kriegerhost\Services\Servers\ReinstallServerService;
use Kriegerhost\Exceptions\Model\DataValidationException;
use Kriegerhost\Repositories\Wings\DaemonServerRepository;
use Kriegerhost\Services\Servers\BuildModificationService;
use Kriegerhost\Services\Databases\DatabasePasswordService;
use Kriegerhost\Services\Servers\DetailsModificationService;
use Kriegerhost\Services\Servers\StartupModificationService;
use Kriegerhost\Contracts\Repository\NestRepositoryInterface;
use Kriegerhost\Repositories\Eloquent\DatabaseHostRepository;
use Kriegerhost\Services\Databases\DatabaseManagementService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;
use Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface;
use Kriegerhost\Contracts\Repository\AllocationRepositoryInterface;
use Kriegerhost\Services\Servers\ServerConfigurationStructureService;
use Kriegerhost\Http\Requests\Admin\Servers\Databases\StoreServerDatabaseRequest;

class ServersController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Kriegerhost\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \Kriegerhost\Services\Servers\BuildModificationService
     */
    protected $buildModificationService;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Kriegerhost\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * @var \Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface
     */
    protected $databaseRepository;

    /**
     * @var \Kriegerhost\Services\Databases\DatabaseManagementService
     */
    protected $databaseManagementService;

    /**
     * @var \Kriegerhost\Services\Databases\DatabasePasswordService
     */
    protected $databasePasswordService;

    /**
     * @var \Kriegerhost\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    protected $databaseHostRepository;

    /**
     * @var \Kriegerhost\Services\Servers\ServerDeletionService
     */
    protected $deletionService;

    /**
     * @var \Kriegerhost\Services\Servers\DetailsModificationService
     */
    protected $detailsModificationService;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\MountRepository
     */
    protected $mountRepository;

    /**
     * @var \Kriegerhost\Contracts\Repository\NestRepositoryInterface
     */
    protected $nestRepository;

    /**
     * @var \Kriegerhost\Services\Servers\ReinstallServerService
     */
    protected $reinstallService;

    /**
     * @var \Kriegerhost\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Kriegerhost\Services\Servers\ServerConfigurationStructureService
     */
    private $serverConfigurationStructureService;

    /**
     * @var \Kriegerhost\Services\Servers\StartupModificationService
     */
    private $startupModificationService;

    /**
     * @var \Kriegerhost\Services\Servers\SuspensionService
     */
    protected $suspensionService;

    /**
     * ServersController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        AllocationRepositoryInterface $allocationRepository,
        BuildModificationService $buildModificationService,
        ConfigRepository $config,
        DaemonServerRepository $daemonServerRepository,
        DatabaseManagementService $databaseManagementService,
        DatabasePasswordService $databasePasswordService,
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseHostRepository $databaseHostRepository,
        ServerDeletionService $deletionService,
        DetailsModificationService $detailsModificationService,
        ReinstallServerService $reinstallService,
        ServerRepositoryInterface $repository,
        MountRepository $mountRepository,
        NestRepositoryInterface $nestRepository,
        ServerConfigurationStructureService $serverConfigurationStructureService,
        StartupModificationService $startupModificationService,
        SuspensionService $suspensionService
    ) {
        $this->alert = $alert;
        $this->allocationRepository = $allocationRepository;
        $this->buildModificationService = $buildModificationService;
        $this->config = $config;
        $this->daemonServerRepository = $daemonServerRepository;
        $this->databaseHostRepository = $databaseHostRepository;
        $this->databaseManagementService = $databaseManagementService;
        $this->databasePasswordService = $databasePasswordService;
        $this->databaseRepository = $databaseRepository;
        $this->detailsModificationService = $detailsModificationService;
        $this->deletionService = $deletionService;
        $this->nestRepository = $nestRepository;
        $this->reinstallService = $reinstallService;
        $this->repository = $repository;
        $this->mountRepository = $mountRepository;
        $this->serverConfigurationStructureService = $serverConfigurationStructureService;
        $this->startupModificationService = $startupModificationService;
        $this->suspensionService = $suspensionService;
    }

    /**
     * Update the details for a server.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function setDetails(Request $request, Server $server)
    {
        $this->detailsModificationService->handle($server, $request->only([
            'owner_id', 'external_id', 'name', 'description',
        ]));

        $this->alert->success(trans('admin/server.alerts.details_updated'))->flash();

        return redirect()->route('admin.servers.view.details', $server->id);
    }

    /**
     * Toggles the install status for a server.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function toggleInstall(Server $server)
    {
        if ($server->status === Server::STATUS_INSTALL_FAILED) {
            throw new DisplayException(trans('admin/server.exceptions.marked_as_failed'));
        }

        $this->repository->update($server->id, [
            'status' => $server->isInstalled() ? Server::STATUS_INSTALLING : null,
        ], true, true);

        $this->alert->success(trans('admin/server.alerts.install_toggled'))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Reinstalls the server with the currently assigned service.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function reinstallServer(Server $server)
    {
        $this->reinstallService->handle($server);
        $this->alert->success(trans('admin/server.alerts.server_reinstalled'))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Manage the suspension status for a server.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function manageSuspension(Request $request, Server $server)
    {
        $this->suspensionService->toggle($server, $request->input('action'));
        $this->alert->success(trans('admin/server.alerts.suspension_toggled', [
            'status' => $request->input('action') . 'ed',
        ]))->flash();

        return redirect()->route('admin.servers.view.manage', $server->id);
    }

    /**
     * Update the build configuration for a server.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateBuild(Request $request, Server $server)
    {
        try {
            $this->buildModificationService->handle($server, $request->only([
                'allocation_id', 'add_allocations', 'remove_allocations',
                'memory', 'swap', 'io', 'cpu', 'threads', 'disk',
                'database_limit', 'allocation_limit', 'backup_limit', 'oom_disabled',
            ]));
        } catch (DataValidationException $exception) {
            throw new ValidationException($exception->validator);
        }

        $this->alert->success(trans('admin/server.alerts.build_updated'))->flash();

        return redirect()->route('admin.servers.view.build', $server->id);
    }

    /**
     * Start the server deletion process.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Throwable
     */
    public function delete(Request $request, Server $server)
    {
        $this->deletionService->withForce($request->filled('force_delete'))->handle($server);
        $this->alert->success(trans('admin/server.alerts.server_deleted'))->flash();

        return redirect()->route('admin.servers');
    }

    /**
     * Update the startup command as well as variables.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function saveStartup(Request $request, Server $server)
    {
        $data = $request->except('_token');
        if (!empty($data['custom_docker_image'])) {
            $data['docker_image'] = $data['custom_docker_image'];
            unset($data['custom_docker_image']);
        }

        try {
            $this->startupModificationService
                ->setUserLevel(User::USER_LEVEL_ADMIN)
                ->handle($server, $data);
        } catch (DataValidationException $exception) {
            throw new ValidationException($exception->validator);
        }

        $this->alert->success(trans('admin/server.alerts.startup_changed'))->flash();

        return redirect()->route('admin.servers.view.startup', $server->id);
    }

    /**
     * Creates a new database assigned to a specific server.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function newDatabase(StoreServerDatabaseRequest $request, Server $server)
    {
        $this->databaseManagementService->create($server, [
            'database' => DatabaseManagementService::generateUniqueDatabaseName($request->input('database'), $server->id),
            'remote' => $request->input('remote'),
            'database_host_id' => $request->input('database_host_id'),
            'max_connections' => $request->input('max_connections'),
        ]);

        return redirect()->route('admin.servers.view.database', $server->id)->withInput();
    }

    /**
     * Resets the database password for a specific database on this server.
     *
     * @param int $server
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function resetDatabasePassword(Request $request, $server)
    {
        $database = $this->databaseRepository->findFirstWhere([
            ['server_id', '=', $server],
            ['id', '=', $request->input('database')],
        ]);

        $this->databasePasswordService->handle($database);

        return response('', 204);
    }

    /**
     * Deletes a database from a server.
     *
     * @param int $server
     * @param int $database
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function deleteDatabase($server, $database)
    {
        $database = $this->databaseRepository->findFirstWhere([
            ['server_id', '=', $server],
            ['id', '=', $database],
        ]);

        $this->databaseManagementService->delete($database);

        return response('', 204);
    }

    /**
     * Add a mount to a server.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function addMount(Server $server, Mount $mount)
    {
        $mountServer = (new MountServer())->forceFill([
            'mount_id' => $mount->id,
            'server_id' => $server->id,
        ]);

        $mountServer->saveOrFail();

        $this->alert->success('Mount was added successfully.')->flash();

        return redirect()->route('admin.servers.view.mounts', $server->id);
    }

    /**
     * Remove a mount from a server.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteMount(Server $server, Mount $mount)
    {
        MountServer::where('mount_id', $mount->id)->where('server_id', $server->id)->delete();

        $this->alert->success('Mount was removed successfully.')->flash();

        return redirect()->route('admin.servers.view.mounts', $server->id);
    }
}
