<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Subusers;

use Kriegerhost\Models\Permission;

class StoreSubuserRequest extends SubuserRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_USER_CREATE;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|between:1,191',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ];
    }
}
