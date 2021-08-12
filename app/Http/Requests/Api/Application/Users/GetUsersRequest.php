<?php

namespace Kriegerhost\Http\Requests\Api\Application\Users;

use Kriegerhost\Services\Acl\Api\AdminAcl as Acl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetUsersRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = Acl::RESOURCE_USERS;

    /**
     * @var int
     */
    protected $permission = Acl::READ;
}
