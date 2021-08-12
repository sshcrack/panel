<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\Response;
use Kriegerhost\Models\Server;
use Kriegerhost\Services\Servers\SuspensionService;
use Kriegerhost\Services\Servers\ReinstallServerService;
use Kriegerhost\Http\Requests\Api\Application\Servers\ServerWriteRequest;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;

class ServerManagementController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Servers\ReinstallServerService
     */
    private $reinstallServerService;

    /**
     * @var \Kriegerhost\Services\Servers\SuspensionService
     */
    private $suspensionService;

    /**
     * SuspensionController constructor.
     */
    public function __construct(
        ReinstallServerService $reinstallServerService,
        SuspensionService $suspensionService
    ) {
        parent::__construct();

        $this->reinstallServerService = $reinstallServerService;
        $this->suspensionService = $suspensionService;
    }

    /**
     * Suspend a server on the Panel.
     *
     * @throws \Throwable
     */
    public function suspend(ServerWriteRequest $request, Server $server): Response
    {
        $this->suspensionService->toggle($server, SuspensionService::ACTION_SUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Unsuspend a server on the Panel.
     *
     * @throws \Throwable
     */
    public function unsuspend(ServerWriteRequest $request, Server $server): Response
    {
        $this->suspensionService->toggle($server, SuspensionService::ACTION_UNSUSPEND);

        return $this->returnNoContent();
    }

    /**
     * Mark a server as needing to be reinstalled.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function reinstall(ServerWriteRequest $request, Server $server): Response
    {
        $this->reinstallServerService->handle($server);

        return $this->returnNoContent();
    }
}
