<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Servers;

use Kriegerhost\Transformers\Api\Application\ServerTransformer;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;
use Kriegerhost\Http\Requests\Api\Application\Servers\GetExternalServerRequest;

class ExternalServerController extends ApplicationApiController
{
    /**
     * Retrieve a specific server from the database using its external ID.
     */
    public function index(GetExternalServerRequest $request): array
    {
        return $this->fractal->item($request->getServerModel())
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
