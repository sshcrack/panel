<?php

namespace Kriegerhost\Services\Subusers;

use Illuminate\Support\Str;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Subuser;
use Illuminate\Database\ConnectionInterface;
use Kriegerhost\Services\Users\UserCreationService;
use Kriegerhost\Repositories\Eloquent\SubuserRepository;
use Kriegerhost\Contracts\Repository\UserRepositoryInterface;
use Kriegerhost\Exceptions\Repository\RecordNotFoundException;
use Kriegerhost\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Kriegerhost\Exceptions\Service\Subuser\ServerSubuserExistsException;

class SubuserCreationService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\SubuserRepository
     */
    private $subuserRepository;

    /**
     * @var \Kriegerhost\Services\Users\UserCreationService
     */
    private $userCreationService;

    /**
     * @var \Kriegerhost\Contracts\Repository\UserRepositoryInterface
     */
    private $userRepository;

    /**
     * SubuserCreationService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        SubuserRepository $subuserRepository,
        UserCreationService $userCreationService,
        UserRepositoryInterface $userRepository
    ) {
        $this->connection = $connection;
        $this->subuserRepository = $subuserRepository;
        $this->userRepository = $userRepository;
        $this->userCreationService = $userCreationService;
    }

    /**
     * Creates a new user on the system and assigns them access to the provided server.
     * If the email address already belongs to a user on the system a new user will not
     * be created.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Service\Subuser\ServerSubuserExistsException
     * @throws \Kriegerhost\Exceptions\Service\Subuser\UserIsServerOwnerException
     * @throws \Throwable
     */
    public function handle(Server $server, string $email, array $permissions): Subuser
    {
        return $this->connection->transaction(function () use ($server, $email, $permissions) {
            try {
                $user = $this->userRepository->findFirstWhere([['email', '=', $email]]);

                if ($server->owner_id === $user->id) {
                    throw new UserIsServerOwnerException(trans('exceptions.subusers.user_is_owner'));
                }

                $subuserCount = $this->subuserRepository->findCountWhere([['user_id', '=', $user->id], ['server_id', '=', $server->id]]);
                if ($subuserCount !== 0) {
                    throw new ServerSubuserExistsException(trans('exceptions.subusers.subuser_exists'));
                }
            } catch (RecordNotFoundException $exception) {
                // Just cap the username generated at 64 characters at most and then append a random string
                // to the end to make it "unique"...
                $username = substr(preg_replace('/([^\w\.-]+)/', '', strtok($email, '@')), 0, 64) . Str::random(3);

                $user = $this->userCreationService->handle([
                    'email' => $email,
                    'username' => $username,
                    'name_first' => 'Server',
                    'name_last' => 'Subuser',
                    'root_admin' => false,
                ]);
            }

            return $this->subuserRepository->create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'permissions' => array_unique($permissions),
            ]);
        });
    }
}
