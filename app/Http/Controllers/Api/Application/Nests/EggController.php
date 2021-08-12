<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Nests;

use Kriegerhost\Models\Egg;
use Kriegerhost\Models\Nest;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;
use Kriegerhost\Transformers\Api\Application\EggTransformer;
use Kriegerhost\Http\Requests\Api\Application\Nests\Eggs\GetEggRequest;
use Kriegerhost\Http\Requests\Api\Application\Nests\Eggs\GetEggsRequest;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;

class EggController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Contracts\Repository\EggRepositoryInterface
     */
    private $repository;

    /**
     * EggController constructor.
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return all eggs that exist for a given nest.
     */
    public function index(GetEggsRequest $request): array
    {
        $eggs = $this->repository->findWhere([
            ['nest_id', '=', $request->getModel(Nest::class)->id],
        ]);

        return $this->fractal->collection($eggs)
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }

    /**
     * Return a single egg that exists on the specified nest.
     */
    public function view(GetEggRequest $request): array
    {
        return $this->fractal->item($request->getModel(Egg::class))
            ->transformWith($this->getTransformer(EggTransformer::class))
            ->toArray();
    }
}
