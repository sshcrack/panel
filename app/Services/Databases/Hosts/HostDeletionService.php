<?php

namespace Kriegerhost\Services\Databases\Hosts;

use Kriegerhost\Exceptions\Service\HasActiveServersException;
use Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface;
use Kriegerhost\Contracts\Repository\DatabaseHostRepositoryInterface;

class HostDeletionService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $databaseRepository;

    /**
     * @var \Kriegerhost\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    private $repository;

    /**
     * HostDeletionService constructor.
     */
    public function __construct(
        DatabaseRepositoryInterface $databaseRepository,
        DatabaseHostRepositoryInterface $repository
    ) {
        $this->databaseRepository = $databaseRepository;
        $this->repository = $repository;
    }

    /**
     * Delete a specified host from the Panel if no databases are
     * attached to it.
     *
     * @throws \Kriegerhost\Exceptions\Service\HasActiveServersException
     */
    public function handle(int $host): int
    {
        $count = $this->databaseRepository->findCountWhere([['database_host_id', '=', $host]]);
        if ($count > 0) {
            throw new HasActiveServersException(trans('exceptions.databases.delete_has_databases'));
        }

        return $this->repository->delete($host);
    }
}
