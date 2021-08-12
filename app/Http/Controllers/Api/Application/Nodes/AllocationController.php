<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Nodes;

use Kriegerhost\Models\Node;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Models\Allocation;
use Kriegerhost\Services\Allocations\AssignmentService;
use Kriegerhost\Services\Allocations\AllocationDeletionService;
use Kriegerhost\Transformers\Api\Application\AllocationTransformer;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;
use Kriegerhost\Http\Requests\Api\Application\Allocations\GetAllocationsRequest;
use Kriegerhost\Http\Requests\Api\Application\Allocations\StoreAllocationRequest;
use Kriegerhost\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest;

class AllocationController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Allocations\AssignmentService
     */
    private $assignmentService;

    /**
     * @var \Kriegerhost\Services\Allocations\AllocationDeletionService
     */
    private $deletionService;

    /**
     * AllocationController constructor.
     */
    public function __construct(
        AssignmentService $assignmentService,
        AllocationDeletionService $deletionService
    ) {
        parent::__construct();

        $this->assignmentService = $assignmentService;
        $this->deletionService = $deletionService;
    }

    /**
     * Return all of the allocations that exist for a given node.
     */
    public function index(GetAllocationsRequest $request, Node $node): array
    {
        $allocations = $node->allocations()->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toArray();
    }

    /**
     * Store new allocations for a given node.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function store(StoreAllocationRequest $request, Node $node): JsonResponse
    {
        $this->assignmentService->handle($node, $request->validated());

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Delete a specific allocation from the Panel.
     *
     * @throws \Kriegerhost\Exceptions\Service\Allocation\ServerUsingAllocationException
     */
    public function delete(DeleteAllocationRequest $request, Node $node, Allocation $allocation): JsonResponse
    {
        $this->deletionService->handle($allocation);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
