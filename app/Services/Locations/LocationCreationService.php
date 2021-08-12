<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Services\Locations;

use Kriegerhost\Contracts\Repository\LocationRepositoryInterface;

class LocationCreationService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * LocationCreationService constructor.
     */
    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new location.
     *
     * @return \Kriegerhost\Models\Location
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        return $this->repository->create($data);
    }
}
