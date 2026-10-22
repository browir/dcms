<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\LogoutResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Logout Filament untuk sesi yang masuk lewat SSO: setelah sesi DCMS diakhiri, arahkan
 * ke logout Keycloak (single logout) supaya "Masuk dengan SSO" berikutnya meminta login
 * ulang. Penanda sesi SSO disimpan ke atribut request oleh listener event Logout
 * (AppServiceProvider), karena session sudah di-invalidate saat response ini dibuat.
 */
class KeycloakLogoutResponse extends LogoutResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        if (! $request->attributes->get('keycloak_sso_logout') || blank(config('keycloak-sso.base_url'))) {
            return parent::toResponse($request);
        }

        return redirect()->away(Socialite::driver('keycloak')->getLogoutUrl(
            url(config('keycloak-sso.routes.redirect_after_logout', '/admin/login')),
            config('keycloak-sso.client_id'),
            // id_token_hint: Keycloak langsung logout & kembali ke DCMS tanpa halaman konfirmasi.
            $request->attributes->get('keycloak_id_token'),
        ));
    }
}
