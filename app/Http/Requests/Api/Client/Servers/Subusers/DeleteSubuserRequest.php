<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Subusers;

use Kriegerhost\Models\Permission;

class DeleteSubuserRequest extends SubuserRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_USER_DELETE;
    }
}
