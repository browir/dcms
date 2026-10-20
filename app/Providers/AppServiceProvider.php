<?php

namespace App\Providers;

use App\Http\Responses\KeycloakLogoutResponse;
use App\Support\Sso\KeycloakUserSync;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Syifa\KeycloakSso\Events\KeycloakLoginSucceeded;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(\NotificationChannels\WebPush\WebPushServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // SSO Keycloak: sinkron unit organisasi & role setiap login berhasil.
        Event::listen(KeycloakLoginSucceeded::class, KeycloakUserSync::class);

        // Logout dari sesi SSO juga mengakhiri sesi di Keycloak (lihat KeycloakLogoutResponse).
        Event::listen(Logout::class, function (): void {
            request()->attributes->set('keycloak_sso_logout', (bool) session('keycloak.sso'));
        });
        $this->app->bind(LogoutResponseContract::class, KeycloakLogoutResponse::class);
    }
}
