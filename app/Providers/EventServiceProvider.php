<?php

namespace Kriegerhost\Providers;

use Kriegerhost\Events\Server\Installed as ServerInstalledEvent;
use Kriegerhost\Notifications\ServerInstalled as ServerInstalledNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ServerInstalledEvent::class => [
            ServerInstalledNotification::class,
        ],
    ];
}
