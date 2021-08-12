<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Files;

use Kriegerhost\Models\Permission;
use Kriegerhost\Contracts\Http\ClientPermissionsRequest;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class PullFileRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_FILE_CREATE;
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'url' => 'required|string|url',
            'directory' => 'sometimes|nullable|string',
        ];
    }
}
