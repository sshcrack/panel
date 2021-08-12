<?php

namespace Kriegerhost\Transformers\Api\Application;

use Kriegerhost\Models\Node;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Allocation;
use Kriegerhost\Services\Acl\Api\AdminAcl;

class AllocationTransformer extends BaseTransformer
{
    /**
     * Relationships that can be loaded onto allocation transformations.
     *
     * @var array
     */
    protected $availableIncludes = ['node', 'server'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Allocation::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed allocation array.
     *
     * @return array
     */
    public function transform(Allocation $allocation)
    {
        return [
            'id' => $allocation->id,
            'ip' => $allocation->ip,
            'alias' => $allocation->ip_alias,
            'port' => $allocation->port,
            'notes' => $allocation->notes,
            'assigned' => !is_null($allocation->server_id),
        ];
    }

    /**
     * Load the node relationship onto a given transformation.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Kriegerhost\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeNode(Allocation $allocation)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_NODES)) {
            return $this->null();
        }

        return $this->item(
            $allocation->node,
            $this->makeTransformer(NodeTransformer::class),
            Node::RESOURCE_NAME
        );
    }

    /**
     * Load the server relationship onto a given transformation.
     *
     * @return \League\Fractal\Resource\Item|\League\Fractal\Resource\NullResource
     *
     * @throws \Kriegerhost\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeServer(Allocation $allocation)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVERS) || !$allocation->server) {
            return $this->null();
        }

        return $this->item(
            $allocation->server,
            $this->makeTransformer(ServerTransformer::class),
            Server::RESOURCE_NAME
        );
    }
}
