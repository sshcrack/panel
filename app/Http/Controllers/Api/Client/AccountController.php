<?php

namespace Kriegerhost\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Services\Users\UserUpdateService;
use Kriegerhost\Transformers\Api\Client\AccountTransformer;
use Kriegerhost\Http\Requests\Api\Client\Account\UpdateEmailRequest;
use Kriegerhost\Http\Requests\Api\Client\Account\UpdatePasswordRequest;

class AccountController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Services\Users\UserUpdateService
     */
    private $updateService;

    /**
     * @var \Illuminate\Auth\SessionGuard
     */
    private $sessionGuard;

    /**
     * AccountController constructor.
     */
    public function __construct(AuthManager $sessionGuard, UserUpdateService $updateService)
    {
        parent::__construct();

        $this->updateService = $updateService;
        $this->sessionGuard = $sessionGuard;
    }

    public function index(Request $request): array
    {
        return $this->fractal->item($request->user())
            ->transformWith($this->getTransformer(AccountTransformer::class))
            ->toArray();
    }

    /**
     * Update the authenticated user's email address.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $this->updateService->handle($request->user(), $request->validated());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Update the authenticated user's password. All existing sessions will be logged
     * out immediately.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->updateService->handle($request->user(), $request->validated());

        $this->sessionGuard->logoutOtherDevices($request->input('password'));

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
