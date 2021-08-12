<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Services\Nests;

use Kriegerhost\Contracts\Repository\NestRepositoryInterface;
use Kriegerhost\Exceptions\Service\HasActiveServersException;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;

class NestDeletionService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Kriegerhost\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * NestDeletionService constructor.
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        NestRepositoryInterface $repository
    ) {
        $this->serverRepository = $serverRepository;
        $this->repository = $repository;
    }

    /**
     * Delete a nest from the system only if there are no servers attached to it.
     *
     * @throws \Kriegerhost\Exceptions\Service\HasActiveServersException
     */
    public function handle(int $nest): int
    {
        $count = $this->serverRepository->findCountWhere([['nest_id', '=', $nest]]);
        if ($count > 0) {
            throw new HasActiveServersException(trans('exceptions.nest.delete_has_servers'));
        }

        return $this->repository->delete($nest);
    }
}
