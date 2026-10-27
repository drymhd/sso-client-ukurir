<?php

namespace Saitrans\SsoClient;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Saitrans\SsoClient\Console\Commands\InstallSsoClientCommand;
use Saitrans\SsoClient\Http\Controllers\SsoController;
use Saitrans\SsoClient\Http\Middleware\EnsureSsoAuthenticated;
use Saitrans\SsoClient\Services\SsoService;

class SsoClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sso-client.php', 'sso-client'
        );

        // Register singleton service
        $this->app->singleton('sso-client', function () {
            return new SsoService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        // Publish config file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/sso-client.php' => config_path('sso-client.php'),
            ], 'sso-client-config');

            // Register artisan commands
            $this->commands([
                InstallSsoClientCommand::class,
            ]);
        }

        // Register Middleware alias
        $router->aliasMiddleware('sso.auth', EnsureSsoAuthenticated::class);

        // Register package routes automatically
        $this->registerRoutes();
    }

    /**
     * Daftarkan rute bawaan package (/sso/login, /sso/callback, /sso/logout).
     */
    protected function registerRoutes(): void
    {
        if (!config('sso-client.routes.enabled', true)) {
            return;
        }

        Route::group([
            'prefix'     => config('sso-client.routes.prefix', 'sso'),
            'middleware' => config('sso-client.routes.middleware', ['web']),
            'as'         => 'sso.',
        ], function () {
            Route::get('/login', [SsoController::class, 'login'])->name('login');
            Route::get('/callback', [SsoController::class, 'callback'])->name('callback');
            Route::match(['get', 'post'], '/logout', [SsoController::class, 'logout'])->name('logout');
        });
    }
}
