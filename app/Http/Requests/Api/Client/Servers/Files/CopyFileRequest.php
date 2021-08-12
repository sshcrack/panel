<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Files;

use Kriegerhost\Models\Permission;
use Kriegerhost\Contracts\Http\ClientPermissionsRequest;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class CopyFileRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_CREATE;
    }

    public function rules(): array
    {
        return [
            'location' => 'required|string',
        ];
    }
}
