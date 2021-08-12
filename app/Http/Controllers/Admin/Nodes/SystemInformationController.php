<?php

namespace Kriegerhost\Http\Controllers\Admin\Nodes;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Kriegerhost\Models\Node;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Repositories\Wings\DaemonConfigurationRepository;

class SystemInformationController extends Controller
{
    /**
     * @var \Kriegerhost\Repositories\Wings\DaemonConfigurationRepository
     */
    private $repository;

    /**
     * SystemInformationController constructor.
     */
    public function __construct(DaemonConfigurationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns system information from the Daemon.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function __invoke(Request $request, Node $node)
    {
        $data = $this->repository->setNode($node)->getSystemInformation();

        return JsonResponse::create([
            'version' => $data['version'] ?? '',
            'system' => [
                'type' => Str::title($data['os'] ?? 'Unknown'),
                'arch' => $data['architecture'] ?? '--',
                'release' => $data['kernel_version'] ?? '--',
                'cpus' => $data['cpu_count'] ?? 0,
            ],
        ]);
    }
}
