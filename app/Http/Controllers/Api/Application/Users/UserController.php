<?php

namespace Kriegerhost\Http\Controllers\Api\Application\Users;

use Kriegerhost\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Kriegerhost\Services\Users\UserUpdateService;
use Kriegerhost\Services\Users\UserCreationService;
use Kriegerhost\Services\Users\UserDeletionService;
use Kriegerhost\Contracts\Repository\UserRepositoryInterface;
use Kriegerhost\Transformers\Api\Application\UserTransformer;
use Kriegerhost\Http\Requests\Api\Application\Users\GetUsersRequest;
use Kriegerhost\Http\Requests\Api\Application\Users\StoreUserRequest;
use Kriegerhost\Http\Requests\Api\Application\Users\DeleteUserRequest;
use Kriegerhost\Http\Requests\Api\Application\Users\UpdateUserRequest;
use Kriegerhost\Http\Controllers\Api\Application\ApplicationApiController;

class UserController extends ApplicationApiController
{
    /**
     * @var \Kriegerhost\Services\Users\UserCreationService
     */
    private $creationService;

    /**
     * @var \Kriegerhost\Services\Users\UserDeletionService
     */
    private $deletionService;

    /**
     * @var \Kriegerhost\Contracts\Repository\UserRepositoryInterface
     */
    private $repository;

    /**
     * @var \Kriegerhost\Services\Users\UserUpdateService
     */
    private $updateService;

    /**
     * UserController constructor.
     */
    public function __construct(
        UserRepositoryInterface $repository,
        UserCreationService $creationService,
        UserDeletionService $deletionService,
        UserUpdateService $updateService
    ) {
        parent::__construct();

        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Handle request to list all users on the panel. Returns a JSON-API representation
     * of a collection of users including any defined relations passed in
     * the request.
     */
    public function index(GetUsersRequest $request): array
    {
        $users = QueryBuilder::for(User::query())
            ->allowedFilters(['email', 'uuid', 'username', 'external_id'])
            ->allowedSorts(['id', 'uuid'])
            ->paginate($request->query('per_page') ?? 50);

        return $this->fractal->collection($users)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }

    /**
     * Handle a request to view a single user. Includes any relations that
     * were defined in the request.
     */
    public function view(GetUsersRequest $request, User $user): array
    {
        return $this->fractal->item($user)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->toArray();
    }

    /**
     * Update an existing user on the system and return the response. Returns the
     * updated user model response on success. Supports handling of token revocation
     * errors when switching a user from an admin to a normal user.
     *
     * Revocation errors are returned under the 'revocation_errors' key in the response
     * meta. If there are no errors this is an empty array.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateUserRequest $request, User $user): array
    {
        $this->updateService->setUserLevel(User::USER_LEVEL_ADMIN);
        $user = $this->updateService->handle($user, $request->validated());

        $response = $this->fractal->item($user)
            ->transformWith($this->getTransformer(UserTransformer::class));

        return $response->toArray();
    }

    /**
     * Store a new user on the system. Returns the created user and a HTTP/201
     * header on successful creation.
     *
     * @throws \Exception
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->creationService->handle($request->validated());

        return $this->fractal->item($user)
            ->transformWith($this->getTransformer(UserTransformer::class))
            ->addMeta([
                'resource' => route('api.application.users.view', [
                    'user' => $user->id,
                ]),
            ])
            ->respond(201);
    }

    /**
     * Handle a request to delete a user from the Panel. Returns a HTTP/204 response
     * on successful deletion.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     */
    public function delete(DeleteUserRequest $request, User $user): JsonResponse
    {
        $this->deletionService->handle($user);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
