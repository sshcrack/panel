<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Settings;

use Kriegerhost\Models\Permission;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class ReinstallServerRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_SETTINGS_REINSTALL;
    }
}
