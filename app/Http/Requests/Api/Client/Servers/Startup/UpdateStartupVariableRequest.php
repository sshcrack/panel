<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Startup;

use Kriegerhost\Models\Permission;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class UpdateStartupVariableRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_STARTUP_UPDATE;
    }

    /**
     * The actual validation of the variable's value will happen inside the controller.
     *
     * @return array|string[]
     */
    public function rules(): array
    {
        return [
            'key' => 'required|string',
            'value' => 'present',
        ];
    }
}
