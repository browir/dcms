<?php

namespace App\Providers;

use App\Http\Responses\KeycloakLogoutResponse;
use App\Support\Sso\KeycloakUserSync;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Support\Enums\Width;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\WebPushServiceProvider;
use Syifa\KeycloakSso\Events\KeycloakLoginSucceeded;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(WebPushServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Standar semua tabel data: filter langsung terlihat & langsung diterapkan,
        // filter/urutan diingat selama sesi, dan pilihan jumlah baris yang wajar.
        Table::configureUsing(fn (Table $table) => $table
            // Tombol "Filter" sebaris dengan kotak Cari; panel filter lebar & langsung diterapkan.
            ->filtersLayout(FiltersLayout::Dropdown)
            ->filtersFormWidth(Width::FourExtraLarge)
            ->filtersFormColumns(['md' => 2, 'lg' => 3])
            ->filtersTriggerAction(fn (Action $action) => $action
                ->button()
                ->outlined()
                ->size('sm')
                ->color('gray')
                ->label('Filter'))
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->searchDebounce('400ms')
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
        );

        // SSO Keycloak: sinkron unit organisasi & role setiap login berhasil.
        Event::listen(KeycloakLoginSucceeded::class, KeycloakUserSync::class);

        // Logout dari sesi SSO juga mengakhiri sesi di Keycloak (lihat KeycloakLogoutResponse).
        Event::listen(Logout::class, function (): void {
            request()->attributes->set('keycloak_sso_logout', (bool) session('keycloak.sso'));
        });
        $this->app->bind(LogoutResponseContract::class, KeycloakLogoutResponse::class);
    }
}
