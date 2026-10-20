# DCMS

Document & Meeting Management System berbasis Laravel 12 + Filament 4.

## Kebutuhan

- PHP 8.2+ (ekstensi: `pdo_mysql`, `intl`, `gd`, `zip`, `mbstring`, `fileinfo`)
- Composer 2
- Node.js 20+ dan npm
- MySQL 8 (fitur full-text search dokumen hanya aktif di MySQL)

Di Windows, semua kebutuhan di atas tersedia lewat [Laragon](https://laragon.org).

## Setup lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database kosong (contoh `dcms`), lalu sesuaikan `DB_*` di `.env`. Isi juga `ADMIN_EMAIL` dan `ADMIN_PASSWORD` untuk akun admin pertama.

```bash
php artisan migrate
php artisan db:seed --class=CompanyDataPermissionsSeeder
php artisan shield:generate --all --panel=admin --option=permissions
php artisan db:seed --class=AdminUserSeeder
php artisan storage:link
npm run build
```

> Pakai `--option=permissions` saat `shield:generate` agar file Policy di `app/Policies` tidak ditimpa.

## Menjalankan aplikasi

```bash
php artisan serve
```

Buka http://127.0.0.1:8000/admin dan login dengan username atau email admin.

Undangan rapat, pengingat, dan notifikasi dikirim lewat queue (`QUEUE_CONNECTION=database`), jadi jalankan juga worker:

```bash
php artisan queue:work
```

Atau jalankan server, queue, log, dan Vite sekaligus dengan `composer dev`.

## Konfigurasi opsional

| Variabel | Kegunaan |
| --- | --- |
| `MAIL_*` | Pengiriman email. Default `log` (email hanya ditulis ke `storage/logs/laravel.log`). |
| `VAPID_*` | Web push notification. Generate dengan `php artisan webpush:vapid`. |
| `N8N_WEBHOOK_URL`, `N8N_WEBHOOK_SECRET` | Notifikasi WhatsApp, lihat [docs/notifikasi-whatsapp-n8n.md](docs/notifikasi-whatsapp-n8n.md). |

## Login SSO (Keycloak)

Karyawan masuk dengan akun SSO Syifa Global Group lewat package [`syifa/keycloak-sso`](https://github.com/ahyaghifari/sgg-sso) — tidak perlu mendaftar. SSO aktif kalau `KEYCLOAK_CLIENT_ID` dan `KEYCLOAK_BASE_URL` terisi; saat aktif, pendaftaran akun mandiri dimatikan dan login lokal tetap tersedia sebagai cadangan administrator.

1. **Keycloak** — buat client khusus DCMS (Standard Flow + Client Authentication):
   - *Valid Redirect URIs*: `https://<domain-dcms>/auth/keycloak/callback`
   - *Valid post logout redirect URIs*: `https://<domain-dcms>/admin/login`
   - Mapper *Client Roles* dengan **Add to userinfo** aktif. Buat client role `dcms-admin`, `dcms-direktur`, `dcms-kabid` dan assign ke grup jabatan.
2. **HRIS** — minta token API (panel `/keycloak` → Aplikasi Terhubung) untuk `HRIS_API_TOKEN`.
3. Isi variabel `KEYCLOAK_*` dan `HRIS_API_*` di `.env` (lihat `.env.example`), lalu:
   ```bash
   php artisan migrate
   php artisan db:seed --class=PegawaiRoleSeeder
   ```
4. Isi **Kode HRIS** di Data Master → Departemen dan Unit sesuai kode di HRIS `/publik/unit-organisasi` (departemen HRIS → Departemen, divisi HRIS → Unit).

Setiap login, akun dicocokkan via `keycloak_sub` → NIP → email (akun lama otomatis tersambung); akun baru dibuat dengan nama/email resmi dari HRIS dan role `pegawai`. Departemen/unit disinkronkan dari HRIS, dan role jabatan dari client role Keycloak (`config/keycloak-sso.php` → `dcms.role_map`). Logout dari sesi SSO juga mengakhiri sesi Keycloak.

## Dark mode

Tombol bulan/matahari di navbar mengganti mode terang/gelap (tersimpan di browser). CSS kustom ditulis untuk mode terang; pasangan gelapnya dibuat otomatis ke `public/css/dcms-dark.css`. Setelah mengubah warna di blok `<style>` atau `style=""`, jalankan:

```bash
php artisan theme:build-dark
```

Override yang tidak bisa dipetakan otomatis ditulis manual di `resources/views/filament/theme-dark.blade.php`.

## Testing

```bash
php artisan test
```

Test memakai SQLite in-memory, jadi tidak menyentuh database lokal.
