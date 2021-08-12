<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Subusers;

use Kriegerhost\Models\Permission;

class UpdateSubuserRequest extends SubuserRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_USER_UPDATE;
    }

    public function rules(): array
    {
        return [
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ];
    }
}
