<?php

namespace Kriegerhost\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'Kriegerhost\Models\Server' => 'Kriegerhost\Policies\ServerPolicy',
    ];

    /**
     * Register any application authentication / authorization services.
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
