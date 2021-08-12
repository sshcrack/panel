<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Network;

use Kriegerhost\Models\Permission;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class NewAllocationRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_ALLOCATION_CREATE;
    }
}
