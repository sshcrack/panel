<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Nodes;

use Kriegerhost\Services\Deployment\FindViableNodesService;
use Kriegerhost\Transformers\Api\Application\NodeTransformer;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;
use Kriegerhost\Http\Requests\Api\Application\Nodes\GetDeployableNodesRequest;

class NodeDeploymentController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Deployment\FindViableNodesService
     */
    private $viableNodesService;

    /**
     * NodeDeploymentController constructor.
     */
    public function __construct(FindViableNodesService $viableNodesService)
    {
        parent::__construct();

        $this->viableNodesService = $viableNodesService;
    }

    /**
     * Finds any nodes that are available using the given deployment criteria. This works
     * similarly to the server creation process, but allows you to pass the deployment object
     * to this endpoint and get back a list of all Nodes satisfying the requirements.
     *
     * @throws \Kriegerhost\Exceptions\Service\Deployment\NoViableNodeException
     */
    public function __invoke(GetDeployableNodesRequest $request): array
    {
        $data = $request->validated();
        $nodes = $this->viableNodesService->setLocations($data['location_ids'] ?? [])
            ->setMemory($data['memory'])
            ->setDisk($data['disk'])
            ->handle($request->query('per_page'), $request->query('page'));

        return $this->fractal->collection($nodes)
            ->transformWith($this->getTransformer(NodeTransformer::class))
            ->toArray();
    }
}
