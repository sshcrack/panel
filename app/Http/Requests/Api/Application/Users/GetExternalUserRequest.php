<?php

namespace Kriegerhost\Http\Requests\Api\Application\Users;

use Kriegerhost\Models\User;
use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Contracts\Repository\UserRepositoryInterface;
use Kriegerhost\Exceptions\Repository\RecordNotFoundException;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetExternalUserRequest extends ApplicationApiRequest
{
    /**
     * @var User
     */
    private $userModel;

    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_USERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested external user exists.
     */
    public function resourceExists(): bool
    {
        $repository = $this->container->make(UserRepositoryInterface::class);

        try {
            $this->userModel = $repository->findFirstWhere([
                ['external_id', '=', $this->route()->parameter('external_id')],
            ]);
        } catch (RecordNotFoundException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Return the user model for the requested external user.
     */
    public function getUserModel(): User
    {
        return $this->userModel;
    }
}
