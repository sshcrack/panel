<?php

namespace Kriegerhost\Http\Requests\Api\Application\Users;

use Kriegerhost\Models\User;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Return the validation rules for this request.
     */
    public function rules(array $rules = null): array
    {
        $userId = $this->getModel(User::class)->id;

        return parent::rules(User::getRulesForUpdate($userId));
    }
}
