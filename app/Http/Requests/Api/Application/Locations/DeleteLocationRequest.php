<?php

namespace Kriegerhost\Http\Requests\Api\Application\Locations;

use Kriegerhost\Models\Location;
use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteLocationRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_LOCATIONS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * Determine if the requested location exists on the Panel.
     */
    public function resourceExists(): bool
    {
        $location = $this->route()->parameter('location');

        return $location instanceof Location && $location->exists;
    }
}
