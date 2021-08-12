<?php

namespace Kriegerhost\Repositories\Eloquent;

use Kriegerhost\Models\User;
use Kriegerhost\Contracts\Repository\UserRepositoryInterface;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return User::class;
    }
}
