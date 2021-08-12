<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Nests;

use Kriegerhost\Models\Nest;
use Kriegerhost\Contracts\Repository\NestRepositoryInterface;
use Kriegerhost\Transformers\Api\Application\NestTransformer;
use Kriegerhost\Http\Requests\Api\Application\Nests\GetNestsRequest;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;

class NestController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Contracts\Repository\NestRepositoryInterface
     */
    private $repository;

    /**
     * NestController constructor.
     */
    public function __construct(NestRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return all Nests that exist on the Panel.
     */
    public function index(GetNestsRequest $request): array
    {
        $nests = $this->repository->paginated($request->query('per_page') ?? 50);

        return $this->fractal->collection($nests)
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }

    /**
     * Return information about a single Nest model.
     */
    public function view(GetNestsRequest $request): array
    {
        return $this->fractal->item($request->getModel(Nest::class))
            ->transformWith($this->getTransformer(NestTransformer::class))
            ->toArray();
    }
}
