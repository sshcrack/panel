<?php

namespace Kriegerhost\Http\Controllers\Api\Remote;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Models\Permission;
use Kriegerhost\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Kriegerhost\Repositories\Eloquent\UserRepository;
use Kriegerhost\Exceptions\Http\HttpForbiddenException;
use Kriegerhost\Repositories\Eloquent\ServerRepository;
use Kriegerhost\Services\Servers\GetUserPermissionsService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kriegerhost\Http\Requests\Api\Remote\SftpAuthenticationFormRequest;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class SftpAuthenticationController extends Controller
{
    use ThrottlesLogins;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\UserRepository
     */
    private $userRepository;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\ServerRepository
     */
    private $serverRepository;

    /**
     * @var \Kriegerhost\Services\Servers\GetUserPermissionsService
     */
    private $permissionsService;

    /**
     * SftpController constructor.
     */
    public function __construct(
        GetUserPermissionsService $permissionsService,
        UserRepository $userRepository,
        ServerRepository $serverRepository
    ) {
        $this->userRepository = $userRepository;
        $this->serverRepository = $serverRepository;
        $this->permissionsService = $permissionsService;
    }

    /**
     * Authenticate a set of credentials and return the associated server details
     * for a SFTP connection on the daemon.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function __invoke(SftpAuthenticationFormRequest $request): JsonResponse
    {
        // Reverse the string to avoid issues with usernames that contain periods.
        $parts = explode('.', strrev($request->input('username')), 2);

        // Unreverse the strings after parsing them apart.
        $connection = [
            'username' => strrev(array_get($parts, 1)),
            'server' => strrev(array_get($parts, 0)),
        ];

        if ($this->hasTooManyLoginAttempts($request)) {
            $seconds = $this->limiter()->availableIn($this->throttleKey($request));

            throw new TooManyRequestsHttpException($seconds, "Too many login attempts for this account, please try again in {$seconds} seconds.");
        }

        /** @var \Kriegerhost\Models\Node $node */
        $node = $request->attributes->get('node');
        if (empty($connection['server'])) {
            throw new NotFoundHttpException();
        }

        /** @var \Kriegerhost\Models\User $user */
        $user = $this->userRepository->findFirstWhere([
            ['username', '=', $connection['username']],
        ]);

        $server = $this->serverRepository->getByUuid($connection['server'] ?? '');
        if (!password_verify($request->input('password'), $user->password) || $server->node_id !== $node->id) {
            $this->incrementLoginAttempts($request);

            throw new HttpForbiddenException('Authorization credentials were not correct, please try again.');
        }

        if (!$user->root_admin && $server->owner_id !== $user->id) {
            $permissions = $this->permissionsService->handle($server, $user);

            if (!in_array(Permission::ACTION_FILE_SFTP, $permissions)) {
                throw new HttpForbiddenException('You do not have permission to access SFTP for this server.');
            }
        }

        $server->validateCurrentState();

        return new JsonResponse([
            'server' => $server->uuid,
            // Deprecated, but still needed at the moment for Wings.
            'token' => '',
            'permissions' => $permissions ?? ['*'],
        ]);
    }

    /**
     * Get the throttle key for the given request.
     */
    protected function throttleKey(Request $request): string
    {
        $username = explode('.', strrev($request->input('username', '')));

        return strtolower(strrev($username[0] ?? '') . '|' . $request->ip());
    }
}
