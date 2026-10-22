<?php

namespace App\Support\Sso;

use SocialiteProviders\Keycloak\Provider;

/**
 * Driver Socialite 'keycloak' yang menyimpan id_token ke session saat login SSO.
 * id_token dipakai sebagai id_token_hint saat logout, supaya Keycloak langsung
 * mengakhiri sesi dan kembali ke halaman login DCMS tanpa halaman konfirmasi.
 */
class KeycloakProvider extends Provider
{
    public function user()
    {
        $user = parent::user();

        if ($idToken = $this->credentialsResponseBody['id_token'] ?? null) {
            session()->put('keycloak.id_token', $idToken);
        }

        return $user;
    }
}
