<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Servers;

use Kriegerhost\Models\User;
use Kriegerhost\Models\Server;
use Kriegerhost\Services\Servers\StartupModificationService;
use Kriegerhost\Transformers\Api\Application\ServerTransformer;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;
use Kriegerhost\Http\Requests\Api\Application\Servers\UpdateServerStartupRequest;

class StartupController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Servers\StartupModificationService
     */
    private $modificationService;

    /**
     * StartupController constructor.
     */
    public function __construct(StartupModificationService $modificationService)
    {
        parent::__construct();

        $this->modificationService = $modificationService;
    }

    /**
     * Update the startup and environment settings for a specific server.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function index(UpdateServerStartupRequest $request): array
    {
        $server = $this->modificationService
            ->setUserLevel(User::USER_LEVEL_ADMIN)
            ->handle($request->getModel(Server::class), $request->validated());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
