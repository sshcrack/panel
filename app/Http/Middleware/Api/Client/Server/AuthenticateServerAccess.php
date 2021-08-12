<?php

namespace Kriegerhost\Http\Middleware\Api\Client\Server;

use Closure;
use Illuminate\Http\Request;
use Kriegerhost\Models\Server;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kriegerhost\Exceptions\Http\Server\ServerStateConflictException;

class AuthenticateServerAccess
{
    /**
     * @var \Kriegerhost\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * Routes that this middleware should not apply to if the user is an admin.
     *
     * @var string[]
     */
    protected $except = [
        'api:client:server.ws',
    ];

    /**
     * AuthenticateServerAccess constructor.
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Authenticate that this server exists and is not suspended or marked as installing.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var \Kriegerhost\Models\User $user */
        $user = $request->user();
        $server = $request->route()->parameter('server');

        if (!$server instanceof Server) {
            throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
        }

        // At the very least, ensure that the user trying to make this request is the
        // server owner, a subuser, or a root admin. We'll leave it up to the controllers
        // to authenticate more detailed permissions if needed.
        if ($user->id !== $server->owner_id && !$user->root_admin) {
            // Check for subuser status.
            if (!$server->subusers->contains('user_id', $user->id)) {
                throw new NotFoundHttpException(trans('exceptions.api.resource_not_found'));
            }
        }

        try {
            $server->validateCurrentState();
        } catch (ServerStateConflictException $exception) {
            // Still allow users to get information about their server if it is installing or
            // being transferred.
            if (!$request->routeIs('api:client:server.view')) {
                if ($server->isSuspended() && !$request->routeIs('api:client:server.resources')) {
                    throw $exception;
                }
                if (!$user->root_admin || !$request->routeIs($this->except)) {
                    throw $exception;
                }
            }
        }

        $request->attributes->set('server', $server);

        return $next($request);
    }
}
