<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Services\Users;

use Kriegerhost\Models\User;
use Kriegerhost\Exceptions\DisplayException;
use Illuminate\Contracts\Translation\Translator;
use Kriegerhost\Contracts\Repository\UserRepositoryInterface;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;

class UserDeletionService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\UserRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * @var \Kriegerhost\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * DeletionService constructor.
     */
    public function __construct(
        ServerRepositoryInterface $serverRepository,
        Translator $translator,
        UserRepositoryInterface $repository
    ) {
        $this->repository = $repository;
        $this->translator = $translator;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Delete a user from the panel only if they have no servers attached to their account.
     *
     * @param int|\Kriegerhost\Models\User $user
     *
     * @return bool|null
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     */
    public function handle($user)
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        $servers = $this->serverRepository->setColumns('id')->findCountWhere([['owner_id', '=', $user]]);
        if ($servers > 0) {
            throw new DisplayException($this->translator->trans('admin/user.exceptions.user_has_servers'));
        }

        return $this->repository->delete($user);
    }
}
