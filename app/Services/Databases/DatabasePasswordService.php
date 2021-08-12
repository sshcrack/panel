<?php

namespace Kriegerhost\Services\Databases;

use Kriegerhost\Models\Database;
use Kriegerhost\Helpers\Utilities;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Encryption\Encrypter;
use Kriegerhost\Extensions\DynamicDatabaseConnection;
use Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface;

class DatabasePasswordService
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private $connection;

    /**
     * @var \Kriegerhost\Extensions\DynamicDatabaseConnection
     */
    private $dynamic;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface
     */
    private $repository;

    /**
     * DatabasePasswordService constructor.
     */
    public function __construct(
        ConnectionInterface $connection,
        DatabaseRepositoryInterface $repository,
        DynamicDatabaseConnection $dynamic,
        Encrypter $encrypter
    ) {
        $this->connection = $connection;
        $this->dynamic = $dynamic;
        $this->encrypter = $encrypter;
        $this->repository = $repository;
    }

    /**
     * Updates a password for a given database.
     *
     * @param \Kriegerhost\Models\Database|int $database
     *
     * @throws \Throwable
     */
    public function handle(Database $database): string
    {
        $password = Utilities::randomStringWithSpecialCharacters(24);

        $this->connection->transaction(function () use ($database, $password) {
            $this->dynamic->set('dynamic', $database->database_host_id);

            $this->repository->withoutFreshModel()->update($database->id, [
                'password' => $this->encrypter->encrypt($password),
            ]);

            $this->repository->dropUser($database->username, $database->remote);
            $this->repository->createUser($database->username, $database->remote, $password, $database->max_connections);
            $this->repository->assignUserToDatabase($database->database, $database->username, $database->remote);
            $this->repository->flush();
        });

        return $password;
    }
}
