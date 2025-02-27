<?php

namespace Kriegerhost\Http\Requests\Api\Application\Locations;

use Kriegerhost\Models\Location;

class GetLocationRequest extends GetLocationsRequest
{
    /**
     * Determine if the requested location exists on the Panel.
     */
    public function resourceExists(): bool
    {
        $location = $this->route()->parameter('location');

        return $location instanceof Location && $location->exists;
    }
}
