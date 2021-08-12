<?php

namespace Kriegerhost\Services\Users;

use Kriegerhost\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Kriegerhost\Traits\Services\HasUserLevels;
use Kriegerhost\Repositories\Eloquent\UserRepository;

class UserUpdateService
{
    use HasUserLevels;

    /**
     * @var \Illuminate\Contracts\Hashing\Hasher
     */
    private $hasher;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\UserRepository
     */
    private $repository;

    /**
     * UpdateService constructor.
     */
    public function __construct(Hasher $hasher, UserRepository $repository)
    {
        $this->hasher = $hasher;
        $this->repository = $repository;
    }

    /**
     * Update the user model instance.
     *
     * @return \Kriegerhost\Models\User
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(User $user, array $data)
    {
        if (!empty(array_get($data, 'password'))) {
            $data['password'] = $this->hasher->make($data['password']);
        } else {
            unset($data['password']);
        }

        /** @var \Kriegerhost\Models\User $response */
        $response = $this->repository->update($user->id, $data);

        return $response;
    }
}
