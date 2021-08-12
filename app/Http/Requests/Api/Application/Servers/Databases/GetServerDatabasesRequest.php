<?php

namespace Kriegerhost\Http\Requests\Api\Application\Servers\Databases;

use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetServerDatabasesRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVER_DATABASES;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
