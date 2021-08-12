<?php

namespace Kriegerhost\Services\Allocations;

use Kriegerhost\Models\Allocation;
use Kriegerhost\Contracts\Repository\AllocationRepositoryInterface;
use Kriegerhost\Exceptions\Service\Allocation\ServerUsingAllocationException;

class AllocationDeletionService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\AllocationRepositoryInterface
     */
    private $repository;

    /**
     * AllocationDeletionService constructor.
     */
    public function __construct(AllocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Delete an allocation from the database only if it does not have a server
     * that is actively attached to it.
     *
     * @return int
     *
     * @throws \Kriegerhost\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function handle(Allocation $allocation)
    {
        if (!is_null($allocation->server_id)) {
            throw new ServerUsingAllocationException(trans('exceptions.allocations.server_using'));
        }

        return $this->repository->delete($allocation->id);
    }
}
