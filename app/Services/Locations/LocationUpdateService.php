<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Services\Locations;

use Kriegerhost\Models\Location;
use Kriegerhost\Contracts\Repository\LocationRepositoryInterface;

class LocationUpdateService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * LocationUpdateService constructor.
     */
    public function __construct(LocationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update an existing location.
     *
     * @param int|\Kriegerhost\Models\Location $location
     *
     * @return \Kriegerhost\Models\Location
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function handle($location, array $data)
    {
        $location = ($location instanceof Location) ? $location->id : $location;

        return $this->repository->update($location, $data);
    }
}
