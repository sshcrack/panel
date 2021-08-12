<?php

namespace Kriegerhost\Http\Requests\Api\Application\Nests\Eggs;

use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetEggsRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_EGGS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
