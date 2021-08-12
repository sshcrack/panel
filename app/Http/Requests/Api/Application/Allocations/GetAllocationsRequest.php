<?php

namespace Kriegerhost\Http\Requests\Api\Application\Allocations;

use Kriegerhost\Models\Node;
use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetAllocationsRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_ALLOCATIONS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the node that we are requesting the allocations
     * for exists on the Panel.
     */
    public function resourceExists(): bool
    {
        $node = $this->route()->parameter('node');

        return $node instanceof Node && $node->exists;
    }
}
