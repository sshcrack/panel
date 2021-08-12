<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Files;

use Kriegerhost\Models\Permission;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class UploadFileRequest extends ClientApiRequest
{
    /**
     * @return string
     */
    public function permission()
    {
        return Permission::ACTION_FILE_CREATE;
    }
}
