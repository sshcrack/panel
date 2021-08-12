<?php

namespace Kriegerhost\Http\Requests\Api\Application\Nests;

use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetNestsRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_NESTS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
