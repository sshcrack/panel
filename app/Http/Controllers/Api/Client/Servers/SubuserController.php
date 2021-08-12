<?php

namespace Kriegerhost\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Kriegerhost\Models\Server;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Models\Permission;
use Illuminate\Support\Facades\Log;
use Kriegerhost\Repositories\Eloquent\SubuserRepository;
use Kriegerhost\Services\Subusers\SubuserCreationService;
use Kriegerhost\Repositories\Wings\DaemonServerRepository;
use Kriegerhost\Transformers\Api\Client\SubuserTransformer;
use Kriegerhost\Http\Controllers\Api\Client\ClientApiController;
use Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException;
use Kriegerhost\Http\Requests\Api\Client\Servers\Subusers\GetSubuserRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Subusers\StoreSubuserRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Subusers\DeleteSubuserRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Subusers\UpdateSubuserRequest;

class SubuserController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Repositories\Eloquent\SubuserRepository
     */
    private $repository;

    /**
     * @var \Kriegerhost\Services\Subusers\SubuserCreationService
     */
    private $creationService;

    /**
     * @var \Kriegerhost\Repositories\Wings\DaemonServerRepository
     */
    private $serverRepository;

    /**
     * SubuserController constructor.
     */
    public function __construct(
        SubuserRepository $repository,
        SubuserCreationService $creationService,
        DaemonServerRepository $serverRepository
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->creationService = $creationService;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Return the users associated with this server instance.
     *
     * @return array
     */
    public function index(GetSubuserRequest $request, Server $server)
    {
        return $this->fractal->collection($server->subusers)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Returns a single subuser associated with this server instance.
     *
     * @return array
     */
    public function view(GetSubuserRequest $request)
    {
        $subuser = $request->attributes->get('subuser');

        return $this->fractal->item($subuser)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Create a new subuser for the given server.
     *
     * @return array
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Service\Subuser\ServerSubuserExistsException
     * @throws \Kriegerhost\Exceptions\Service\Subuser\UserIsServerOwnerException
     * @throws \Throwable
     */
    public function store(StoreSubuserRequest $request, Server $server)
    {
        $response = $this->creationService->handle(
            $server,
            $request->input('email'),
            $this->getDefaultPermissions($request)
        );

        return $this->fractal->item($response)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Update a given subuser in the system for the server.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateSubuserRequest $request, Server $server): array
    {
        /** @var \Kriegerhost\Models\Subuser $subuser */
        $subuser = $request->attributes->get('subuser');

        $permissions = $this->getDefaultPermissions($request);
        $current = $subuser->permissions;

        sort($permissions);
        sort($current);

        // Only update the database and hit up the Wings instance to invalidate JTI's if the permissions
        // have actually changed for the user.
        if ($permissions !== $current) {
            $this->repository->update($subuser->id, [
                'permissions' => $this->getDefaultPermissions($request),
            ]);

            try {
                $this->serverRepository->setServer($server)->revokeUserJTI($subuser->user_id);
            } catch (DaemonConnectionException $exception) {
                // Don't block this request if we can't connect to the Wings instance. Chances are it is
                // offline in this event and the token will be invalid anyways once Wings boots back.
                Log::warning($exception, ['user_id' => $subuser->user_id, 'server_id' => $server->id]);
            }
        }

        return $this->fractal->item($subuser->refresh())
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Removes a subusers from a server's assignment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteSubuserRequest $request, Server $server)
    {
        /** @var \Kriegerhost\Models\Subuser $subuser */
        $subuser = $request->attributes->get('subuser');

        $this->repository->delete($subuser->id);

        try {
            $this->serverRepository->setServer($server)->revokeUserJTI($subuser->user_id);
        } catch (DaemonConnectionException $exception) {
            // Don't block this request if we can't connect to the Wings instance.
            Log::warning($exception, ['user_id' => $subuser->user_id, 'server_id' => $server->id]);
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Returns the default permissions for all subusers to ensure none are ever removed wrongly.
     */
    protected function getDefaultPermissions(Request $request): array
    {
        return array_unique(array_merge($request->input('permissions') ?? [], [Permission::ACTION_WEBSOCKET_CONNECT]));
    }
}
