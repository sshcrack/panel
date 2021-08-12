<?php

namespace Kriegerhost\Http\Requests\Api\Application\Servers\Databases;

use Kriegerhost\Services\Acl\Api\AdminAcl;

class ServerDatabaseWriteRequest extends GetServerDatabasesRequest
{
    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;
}
