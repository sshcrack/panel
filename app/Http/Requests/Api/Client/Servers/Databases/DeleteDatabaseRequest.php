<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Databases;

use Kriegerhost\Models\Server;
use Kriegerhost\Models\Database;
use Kriegerhost\Models\Permission;
use Kriegerhost\Contracts\Http\ClientPermissionsRequest;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class DeleteDatabaseRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_DATABASE_DELETE;
    }

    public function resourceExists(): bool
    {
        return $this->getModel(Server::class)->id === $this->getModel(Database::class)->server_id;
    }
}
