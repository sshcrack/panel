<?php

namespace Kriegerhost\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Kriegerhost\Models\Server;
use Kriegerhost\Repositories\Wings\DaemonPowerRepository;
use Kriegerhost\Http\Controllers\Api\Client\ClientApiController;
use Kriegerhost\Http\Requests\Api\Client\Servers\SendPowerRequest;

class PowerController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Repositories\Wings\DaemonPowerRepository
     */
    private $repository;

    /**
     * PowerController constructor.
     */
    public function __construct(DaemonPowerRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Send a power action to a server.
     *
     * @throws \Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function index(SendPowerRequest $request, Server $server): Response
    {
        $this->repository->setServer($server)->send(
            $request->input('signal')
        );

        return $this->returnNoContent();
    }
}
