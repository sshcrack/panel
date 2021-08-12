<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Servers;

use Kriegerhost\Models\Server;
use Kriegerhost\Services\Servers\BuildModificationService;
use Kriegerhost\Services\Servers\DetailsModificationService;
use Kriegerhost\Transformers\Api\Application\ServerTransformer;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;
use Kriegerhost\Http\Requests\Api\Application\Servers\UpdateServerDetailsRequest;
use Kriegerhost\Http\Requests\Api\Application\Servers\UpdateServerBuildConfigurationRequest;

class ServerDetailsController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Servers\BuildModificationService
     */
    private $buildModificationService;

    /**
     * @var \Kriegerhost\Services\Servers\DetailsModificationService
     */
    private $detailsModificationService;

    /**
     * ServerDetailsController constructor.
     */
    public function __construct(
        BuildModificationService $buildModificationService,
        DetailsModificationService $detailsModificationService
    ) {
        parent::__construct();

        $this->buildModificationService = $buildModificationService;
        $this->detailsModificationService = $detailsModificationService;
    }

    /**
     * Update the details for a specific server.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function details(UpdateServerDetailsRequest $request): array
    {
        $server = $this->detailsModificationService->returnUpdatedModel()->handle(
            $request->getModel(Server::class),
            $request->validated()
        );

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * Update the build details for a specific server.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function build(UpdateServerBuildConfigurationRequest $request, Server $server): array
    {
        $server = $this->buildModificationService->handle($server, $request->validated());

        return $this->fractal->item($server)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
