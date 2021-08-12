<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Users;

use Kriegerhost\Transformers\Api\Application\UserTransformer;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;
use Kriegerhost\Http\Requests\Api\Application\Users\GetExternalUserRequest;

class ExternalUserController extends ApplicationApiController
{
    /**
     * Retrieve a specific user from the database using their external ID.
     */
    public function index(GetExternalUserRequest $request): array
    {
        return $this->fractal->item($request->getUserModel())
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }
}
