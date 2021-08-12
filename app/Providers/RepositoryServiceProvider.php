<?php

namespace Kriegerhost\Providers;

use Illuminate\Support\ServiceProvider;
use Kriegerhost\Repositories\Eloquent\EggRepository;
use Kriegerhost\Repositories\Eloquent\NestRepository;
use Kriegerhost\Repositories\Eloquent\NodeRepository;
use Kriegerhost\Repositories\Eloquent\TaskRepository;
use Kriegerhost\Repositories\Eloquent\UserRepository;
use Kriegerhost\Repositories\Eloquent\ApiKeyRepository;
use Kriegerhost\Repositories\Eloquent\ServerRepository;
use Kriegerhost\Repositories\Eloquent\SessionRepository;
use Kriegerhost\Repositories\Eloquent\SubuserRepository;
use Kriegerhost\Repositories\Eloquent\DatabaseRepository;
use Kriegerhost\Repositories\Eloquent\LocationRepository;
use Kriegerhost\Repositories\Eloquent\ScheduleRepository;
use Kriegerhost\Repositories\Eloquent\SettingsRepository;
use Kriegerhost\Repositories\Eloquent\AllocationRepository;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;
use Kriegerhost\Repositories\Eloquent\EggVariableRepository;
use Kriegerhost\Contracts\Repository\NestRepositoryInterface;
use Kriegerhost\Contracts\Repository\NodeRepositoryInterface;
use Kriegerhost\Contracts\Repository\TaskRepositoryInterface;
use Kriegerhost\Contracts\Repository\UserRepositoryInterface;
use Kriegerhost\Repositories\Eloquent\DatabaseHostRepository;
use Kriegerhost\Contracts\Repository\ApiKeyRepositoryInterface;
use Kriegerhost\Contracts\Repository\ServerRepositoryInterface;
use Kriegerhost\Repositories\Eloquent\ServerVariableRepository;
use Kriegerhost\Contracts\Repository\SessionRepositoryInterface;
use Kriegerhost\Contracts\Repository\SubuserRepositoryInterface;
use Kriegerhost\Contracts\Repository\DatabaseRepositoryInterface;
use Kriegerhost\Contracts\Repository\LocationRepositoryInterface;
use Kriegerhost\Contracts\Repository\ScheduleRepositoryInterface;
use Kriegerhost\Contracts\Repository\SettingsRepositoryInterface;
use Kriegerhost\Contracts\Repository\AllocationRepositoryInterface;
use Kriegerhost\Contracts\Repository\EggVariableRepositoryInterface;
use Kriegerhost\Contracts\Repository\DatabaseHostRepositoryInterface;
use Kriegerhost\Contracts\Repository\ServerVariableRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register all of the repository bindings.
     */
    public function register()
    {
        // Eloquent Repositories
        $this->app->bind(AllocationRepositoryInterface::class, AllocationRepository::class);
        $this->app->bind(ApiKeyRepositoryInterface::class, ApiKeyRepository::class);
        $this->app->bind(DatabaseRepositoryInterface::class, DatabaseRepository::class);
        $this->app->bind(DatabaseHostRepositoryInterface::class, DatabaseHostRepository::class);
        $this->app->bind(EggRepositoryInterface::class, EggRepository::class);
        $this->app->bind(EggVariableRepositoryInterface::class, EggVariableRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->bind(NestRepositoryInterface::class, NestRepository::class);
        $this->app->bind(NodeRepositoryInterface::class, NodeRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
        $this->app->bind(ServerRepositoryInterface::class, ServerRepository::class);
        $this->app->bind(ServerVariableRepositoryInterface::class, ServerVariableRepository::class);
        $this->app->bind(SessionRepositoryInterface::class, SessionRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        $this->app->bind(SubuserRepositoryInterface::class, SubuserRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
