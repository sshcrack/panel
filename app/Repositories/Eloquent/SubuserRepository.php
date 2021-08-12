<?php

namespace Kriegerhost\Repositories\Eloquent;

use Kriegerhost\Models\Subuser;
use Kriegerhost\Exceptions\Repository\RecordNotFoundException;
use Kriegerhost\Contracts\Repository\SubuserRepositoryInterface;

class SubuserRepository extends EloquentRepository implements SubuserRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Subuser::class;
    }

    /**
     * Return a subuser with the associated server relationship.
     */
    public function loadServerAndUserRelations(Subuser $subuser, bool $refresh = false): Subuser
    {
        if (!$subuser->relationLoaded('server') || $refresh) {
            $subuser->load('server');
        }

        if (!$subuser->relationLoaded('user') || $refresh) {
            $subuser->load('user');
        }

        return $subuser;
    }

    /**
     * Return a subuser with the associated permissions relationship.
     */
    public function getWithPermissions(Subuser $subuser, bool $refresh = false): Subuser
    {
        if (!$subuser->relationLoaded('permissions') || $refresh) {
            $subuser->load('permissions');
        }

        if (!$subuser->relationLoaded('user') || $refresh) {
            $subuser->load('user');
        }

        return $subuser;
    }

    /**
     * Return a subuser and associated permissions given a user_id and server_id.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithPermissionsUsingUserAndServer(int $user, int $server): Subuser
    {
        $instance = $this->getBuilder()->with('permissions')->where([
            ['user_id', '=', $user],
            ['server_id', '=', $server],
        ])->first();

        if (is_null($instance)) {
            throw new RecordNotFoundException();
        }

        return $instance;
    }
}
