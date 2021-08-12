<?php

namespace Kriegerhost\Http\Requests\Api\Application\Users;

use Kriegerhost\Models\User;
use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteUserRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_USERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * Determine if the requested user exists on the Panel.
     */
    public function resourceExists(): bool
    {
        $user = $this->route()->parameter('user');

        return $user instanceof User && $user->exists;
    }
}
