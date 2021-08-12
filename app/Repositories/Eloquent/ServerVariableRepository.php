<?php

namespace Kriegerhost\Repositories\Eloquent;

use Kriegerhost\Models\ServerVariable;
use Kriegerhost\Contracts\Repository\ServerVariableRepositoryInterface;

class ServerVariableRepository extends EloquentRepository implements ServerVariableRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return ServerVariable::class;
    }
}
