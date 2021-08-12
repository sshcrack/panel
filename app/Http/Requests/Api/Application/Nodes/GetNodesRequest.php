<?php

namespace Kriegerhost\Http\Requests\Api\Application\Nodes;

use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetNodesRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_NODES;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
