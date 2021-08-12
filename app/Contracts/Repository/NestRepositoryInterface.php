<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Contracts\Repository;

use Kriegerhost\Models\Nest;

interface NestRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a nest or all nests with their associated eggs and variables.
     *
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Kriegerhost\Models\Nest
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggs(int $id = null);

    /**
     * Return a nest or all nests and the count of eggs and servers for that nest.
     *
     * @return \Kriegerhost\Models\Nest|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithCounts(int $id = null);

    /**
     * Return a nest along with its associated eggs and the servers relation on those eggs.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithEggServers(int $id): Nest;
}
