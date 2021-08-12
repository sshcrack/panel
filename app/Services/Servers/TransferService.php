<?php

namespace Kriegerhost\Services\Servers;

use Kriegerhost\Models\Server;
use Kriegerhost\Repositories\Wings\DaemonServerRepository;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;

class TransferService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * @var \Kriegerhost\Repositories\Wings\DaemonServerRepository
     */
    private $daemonServerRepository;

    /**
     * TransferService constructor.
     */
    public function __construct(
        DaemonServerRepository $daemonServerRepository,
        ServerRepositoryInterface $repository
    ) {
        $this->repository = $repository;
        $this->daemonServerRepository = $daemonServerRepository;
    }

    /**
     * Requests an archive from the daemon.
     *
     * @param int|\Kriegerhost\Models\Server $server
     *
     * @throws \Throwable
     */
    public function requestArchive(Server $server)
    {
        $this->daemonServerRepository->setServer($server)->requestArchive();
    }
}
