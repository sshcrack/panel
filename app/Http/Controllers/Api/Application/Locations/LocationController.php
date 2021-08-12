<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Locations;

use Illuminate\Http\Response;
use Kriegerhost\Models\Location;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Kriegerhost\Services\Locations\LocationUpdateService;
use Kriegerhost\Services\Locations\LocationCreationService;
use Kriegerhost\Services\Locations\LocationDeletionService;
use Kriegerhost\Contracts\Repository\LocationRepositoryInterface;
use Kriegerhost\Transformers\Api\Application\LocationTransformer;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;
use Kriegerhost\Http\Requests\Api\Application\Locations\GetLocationRequest;
use Kriegerhost\Http\Requests\Api\Application\Locations\GetLocationsRequest;
use Kriegerhost\Http\Requests\Api\Application\Locations\StoreLocationRequest;
use Kriegerhost\Http\Requests\Api\Application\Locations\DeleteLocationRequest;
use Kriegerhost\Http\Requests\Api\Application\Locations\UpdateLocationRequest;

class LocationController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Locations\LocationCreationService
     */
    private $creationService;

    /**
     * @var \Kriegerhost\Services\Locations\LocationDeletionService
     */
    private $deletionService;

    /**
     * @var \Kriegerhost\Contracts\Repository\LocationRepositoryInterface
     */
    private $repository;

    /**
     * @var \Kriegerhost\Services\Locations\LocationUpdateService
     */
    private $updateService;

    /**
     * LocationController constructor.
     */
    public function __construct(
        LocationCreationService $creationService,
        LocationDeletionService $deletionService,
        LocationRepositoryInterface $repository,
        LocationUpdateService $updateService
    ) {
        parent::__construct();

        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Return all of the locations currently registered on the Panel.
     */
    public function index(GetLocationsRequest $request): array
    {
        $locations = QueryBuilder::for(Location::query())
            ->allowedFilters(['short', 'long'])
            ->allowedSorts(['id'])
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($locations)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Return a single location.
     */
    public function view(GetLocationRequest $request): array
    {
        return $this->fractal->item($request->getModel(Location::class))
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Store a new location on the Panel and return a HTTP/201 response code with the
     * new location attached.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function store(StoreLocationRequest $request): JsonResponse
    {
        $location = $this->creationService->handle($request->validated());

        return $this->fractal->item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->addMeta([
                'resource' => route('api.application.locations.view', [
                    'location' => $location->id,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Update a location on the Panel and return the updated record to the user.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateLocationRequest $request): array
    {
        $location = $this->updateService->handle($request->getModel(Location::class), $request->validated());

        return $this->fractal->item($location)
            ->transformWith($this->getTransformer(LocationTransformer::class))
            ->toArray();
    }

    /**
     * Delete a location from the Panel.
     *
     * @throws \Kriegerhost\Exceptions\Service\Location\HasActiveNodesException
     */
    public function delete(DeleteLocationRequest $request): Response
    {
        $this->deletionService->handle($request->getModel(Location::class));

        return response('', 204);
    }
}
