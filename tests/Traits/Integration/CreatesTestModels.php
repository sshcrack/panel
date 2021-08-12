<?php

namespace Kriegerhost\Tests\Traits\Integration;

use Ramsey\Uuid\Uuid;
use Kriegerhost\Models\Egg;
use Kriegerhost\Models\Nest;
use Kriegerhost\Models\Node;
use Kriegerhost\Models\User;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Location;
use Kriegerhost\Models\Allocation;

trait CreatesTestModels
{
    /**
     * Creates a server model in the databases for the purpose of testing. If an attribute
     * is passed in that normally requires this function to create a model no model will be
     * created and that attribute's value will be used.
     *
     * The returned server model will have all of the relationships loaded onto it.
     *
     * @return \Kriegerhost\Models\Server
     */
    public function createServerModel(array $attributes = [])
    {
        if (isset($attributes['user_id'])) {
            $attributes['owner_id'] = $attributes['user_id'];
        }

        if (!isset($attributes['owner_id'])) {
            /** @var \Kriegerhost\Models\User $user */
            $user = User::factory()->create();
            $attributes['owner_id'] = $user->id;
        }

        if (!isset($attributes['node_id'])) {
            if (!isset($attributes['location_id'])) {
                /** @var \Kriegerhost\Models\Location $location */
                $location = Location::factory()->create();
                $attributes['location_id'] = $location->id;
            }

            /** @var \Kriegerhost\Models\Node $node */
            $node = Node::factory()->create(['location_id' => $attributes['location_id']]);
            $attributes['node_id'] = $node->id;
        }

        if (!isset($attributes['allocation_id'])) {
            /** @var \Kriegerhost\Models\Allocation $allocation */
            $allocation = Allocation::factory()->create(['node_id' => $attributes['node_id']]);
            $attributes['allocation_id'] = $allocation->id;
        }

        if (!isset($attributes['nest_id'])) {
            /** @var \Kriegerhost\Models\Nest $nest */
            $nest = Nest::with('eggs')->first();
            $attributes['nest_id'] = $nest->id;

            if (!isset($attributes['egg_id'])) {
                $attributes['egg_id'] = $nest->getRelation('eggs')->first()->id;
            }
        }

        if (!isset($attributes['egg_id'])) {
            /** @var \Kriegerhost\Models\Egg $egg */
            $egg = Egg::where('nest_id', $attributes['nest_id'])->first();
            $attributes['egg_id'] = $egg->id;
        }

        unset($attributes['user_id'], $attributes['location_id']);

        /** @var \Kriegerhost\Models\Server $server */
        $server = Server::factory()->create($attributes);

        Allocation::query()->where('id', $server->allocation_id)->update(['server_id' => $server->id]);

        return $server->fresh([
            'location', 'user', 'node', 'allocation', 'nest', 'egg',
        ]);
    }

    /**
     * Clones a given egg allowing us to make modifications that don't affect other
     * tests that rely on the egg existing in the correct state.
     */
    protected function cloneEggAndVariables(Egg $egg): Egg
    {
        $model = $egg->replicate(['id', 'uuid']);
        $model->uuid = Uuid::uuid4()->toString();
        $model->push();

        /** @var \Kriegerhost\Models\Egg $model */
        $model = $model->fresh();

        foreach ($egg->variables as $variable) {
            $variable->replicate(['id', 'egg_id'])->forceFill(['egg_id' => $model->id])->push();
        }

        return $model->fresh();
    }
}
