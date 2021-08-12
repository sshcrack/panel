<?php

namespace Kriegerhost\Http;

use Kriegerhost\Models\ApiKey;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\Authenticate;
use Kriegerhost\Http\Middleware\TrimStrings;
use Kriegerhost\Http\Middleware\TrustProxies;
use Illuminate\Session\Middleware\StartSession;
use Kriegerhost\Http\Middleware\EncryptCookies;
use Kriegerhost\Http\Middleware\Api\IsValidJson;
use Kriegerhost\Http\Middleware\VerifyCsrfToken;
use Kriegerhost\Http\Middleware\VerifyReCaptcha;
use Kriegerhost\Http\Middleware\AdminAuthenticate;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Kriegerhost\Http\Middleware\LanguageMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Kriegerhost\Http\Middleware\Api\AuthenticateKey;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Kriegerhost\Http\Middleware\Api\SetSessionDriver;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kriegerhost\Http\Middleware\MaintenanceMiddleware;
use Kriegerhost\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Kriegerhost\Http\Middleware\Api\AuthenticateIPAccess;
use Kriegerhost\Http\Middleware\Api\ApiSubstituteBindings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Kriegerhost\Http\Middleware\Api\Daemon\DaemonAuthenticate;
use Kriegerhost\Http\Middleware\RequireTwoFactorAuthentication;
use Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Kriegerhost\Http\Middleware\Api\Client\SubstituteClientApiBindings;
use Kriegerhost\Http\Middleware\Api\Application\AuthenticateApplicationUser;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        CheckForMaintenanceMode::class,
        EncryptCookies::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        TrustProxies::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            LanguageMiddleware::class,
            RequireTwoFactorAuthentication::class,
        ],
        'api' => [
            IsValidJson::class,
            ApiSubstituteBindings::class,
            SetSessionDriver::class,
            'api..key:' . ApiKey::TYPE_APPLICATION,
            AuthenticateApplicationUser::class,
            AuthenticateIPAccess::class,
        ],
        'client-api' => [
            StartSession::class,
            SetSessionDriver::class,
            AuthenticateSession::class,
            IsValidJson::class,
            SubstituteClientApiBindings::class,
            'api..key:' . ApiKey::TYPE_ACCOUNT,
            AuthenticateIPAccess::class,
            // This is perhaps a little backwards with the Client API, but logically you'd be unable
            // to create/get an API key without first enabling 2FA on the account, so I suppose in the
            // end it makes sense.
            //
            // You just wouldn't be authenticating with the API by providing a 2FA token.
            RequireTwoFactorAuthentication::class,
        ],
        'daemon' => [
            SubstituteBindings::class,
            DaemonAuthenticate::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'guest' => RedirectIfAuthenticated::class,
        'admin' => AdminAuthenticate::class,
        'csrf' => VerifyCsrfToken::class,
        'throttle' => ThrottleRequests::class,
        'can' => Authorize::class,
        'bindings' => SubstituteBindings::class,
        'recaptcha' => VerifyReCaptcha::class,
        'node.maintenance' => MaintenanceMiddleware::class,

        // API Specific Middleware
        'api..key' => AuthenticateKey::class,
    ];
}
