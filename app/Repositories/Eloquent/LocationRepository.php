<?php

namespace Kriegerhost\Repositories\Eloquent;

use Kriegerhost\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Kriegerhost\Exceptions\Repository\RecordNotFoundException;
use Kriegerhost\Contracts\Repository\LocationRepositoryInterface;

class LocationRepository extends EloquentRepository implements LocationRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Location::class;
    }

    /**
     * Return locations with a count of nodes and servers attached to it.
     */
    public function getAllWithDetails(): Collection
    {
        return $this->getBuilder()->withCount('nodes', 'servers')->get($this->getColumns());
    }

    /**
     * Return all of the available locations with the nodes as a relationship.
     */
    public function getAllWithNodes(): Collection
    {
        return $this->getBuilder()->with('nodes')->get($this->getColumns());
    }

    /**
     * Return all of the nodes and their respective count of servers for a location.
     *
     * @return mixed
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithNodes(int $id): Location
    {
        try {
            return $this->getBuilder()->with('nodes.servers')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }

    /**
     * Return a location and the count of nodes in that location.
     *
     * @return mixed
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithNodeCount(int $id): Location
    {
        try {
            return $this->getBuilder()->withCount('nodes')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }
}
