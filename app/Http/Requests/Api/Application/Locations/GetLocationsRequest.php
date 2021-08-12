<?php

namespace Kriegerhost\Http\Requests\Api\Application\Locations;

use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetLocationsRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_LOCATIONS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
