<?php

namespace Kriegerhost\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Kriegerhost\Models\Server;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Repositories\Eloquent\ServerRepository;
use Kriegerhost\Services\Servers\ReinstallServerService;
use Kriegerhost\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Kriegerhost\Http\Requests\Api\Client\Servers\Settings\RenameServerRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Settings\SetDockerImageRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Settings\ReinstallServerRequest;

class SettingsController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * @var \Kriegerhost\Services\Servers\ReinstallServerService
     */
    private $reinstallServerService;

    /**
     * SettingsController constructor.
     */
    public function __construct(
        ServerRepository $repository,
        ReinstallServerService $reinstallServerService
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->reinstallServerService = $reinstallServerService;
    }

    /**
     * Renames a server.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function rename(RenameServerRequest $request, Server $server)
    {
        $this->repository->update($server->id, [
            'name' => $request->input('name'),
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Reinstalls the server on the daemon.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function reinstall(ReinstallServerRequest $request, Server $server)
    {
        $this->reinstallServerService->handle($server);

        return new JsonResponse([], Response::HTTP_ACCEPTED);
    }

    /**
     * Changes the Docker image in use by the server.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function dockerImage(SetDockerImageRequest $request, Server $server)
    {
        if (!in_array($server->image, $server->egg->docker_images)) {
            throw new BadRequestHttpException('This server\'s Docker image has been manually set by an administrator and cannot be updated.');
        }

        $server->forceFill(['image' => $request->input('docker_image')])->saveOrFail();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
