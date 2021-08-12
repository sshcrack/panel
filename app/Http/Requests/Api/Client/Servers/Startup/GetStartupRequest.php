<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Startup;

use Kriegerhost\Models\Permission;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class GetStartupRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_STARTUP_READ;
    }
}
