<?php

namespace App\Support\Sso;

/** Status login SSO Keycloak (package syifa/keycloak-sso). */
class Sso
{
    /** SSO aktif kalau client Keycloak sudah dikonfigurasi di .env. */
    public static function enabled(): bool
    {
        return filled(config('keycloak-sso.client_id')) && filled(config('keycloak-sso.base_url'));
    }
}
