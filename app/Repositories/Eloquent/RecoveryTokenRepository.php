<?php

namespace Kriegerhost\Repositories\Eloquent;

use Kriegerhost\Models\RecoveryToken;

class RecoveryTokenRepository extends EloquentRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return RecoveryToken::class;
    }
}
