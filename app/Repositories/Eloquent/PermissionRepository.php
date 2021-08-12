<?php

namespace Kriegerhost\Repositories\Eloquent;

use Exception;
use Kriegerhost\Contracts\Repository\PermissionRepositoryInterface;

class PermissionRepository extends EloquentRepository implements PermissionRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function model()
    {
        throw new Exception('This functionality is not implemented.');
    }
}
