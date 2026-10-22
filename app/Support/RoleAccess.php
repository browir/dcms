<?php

namespace App\Support;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Izin dasar per role DCMS. Hanya MENAMBAH izin (tidak mencabut), jadi aman dijalankan
 * ulang dan tidak menimpa pengaturan yang dibuat admin di Peran & Hak Akses.
 *
 * - pegawai  : role default karyawan (SSO). Cakupan data tetap dibatasi scope access() di model.
 * - kabid    : pegawai + review/ACC dokumen tingkat Kabid + kirim pengingat.
 * - direktur : kabid + ACC dokumen tingkat Direktur.
 * - super_admin : semua izin (config Shield define_via_gate=false, jadi harus eksplisit).
 */
class RoleAccess
{
    /** Widget Beranda. Atur tampil/tidaknya per role di Peran & Hak Akses → Widget. */
    public const DASHBOARD_WIDGETS = [
        'View:EmployeeActivityHubWidget',
        'View:ModuleOverviewWidget',
        'View:AgendaRoomsWidget',
        'View:FollowUpWidget',
        'View:NumberingDocumentsWidget',
        'View:MyCalendarWidget',
    ];

    public const PEGAWAI = [
        ...self::DASHBOARD_WIDGETS,
        // Dokumen: lihat, ambil nomor & isi dokumen, revisi dokumen sendiri, usulan revisi
        'ViewAny:Document',
        'View:Document',
        'Create:Document',
        'Update:Document',
        'ViewAny:DocumentChangeRequest',
        'View:DocumentChangeRequest',
        'Create:DocumentChangeRequest',
        'View:ComplianceHub',
        'View:MyBookmarks',
        // Surat masuk: membaca surat untuk unitnya (mencatat surat diatur per role)
        'ViewAny:IncomingLetter',
        'View:IncomingLetter',
        // Rapat: buat & ikuti rapat; notulis perlu Update untuk mengisi notulen
        'ViewAny:Meeting',
        'View:Meeting',
        'Create:Meeting',
        'Update:Meeting',
        // Pengingat pribadi
        'access_reminder_hub',
        'create_own_reminder',
        'create_personal_reminder',
    ];

    public const KABID = [
        ...self::PEGAWAI,
        'review_document',
        'approve_document_kabid',
        'View:AuditTrail',
        'View:RecycleBin',
        'send_mandatory_read_reminder',
        'send_meeting_reminder',
        'send_expiry_reminder',
    ];

    public const DIREKTUR = [
        ...self::KABID,
        'approve_document_direktur',
    ];

    /** Izin widget yang class-nya sudah dihapus (tampil sebagai sampah di Peran & Hak Akses). */
    public const STALE = [
        'View:DocumentAnalyticsWidget',
        'View:DocumentExpiryWidget',
        'View:MeetingCardsWidget',
        'View:MeetingInvitedWidget',
        'View:MeetingStatsWidget',
        'View:MeetingTodayWidget',
        'View:PriorityActionsWidget',
        'View:QuickAccessHubWidget',
        'View:DocumentManagementDashboard',
        // Halaman yang tidak memakai izin View:<Page> (lihat config filament-shield pages.exclude)
        'View:Dashboard',
        'View:ReminderHub',
    ];

    public static function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()->whereIn('name', self::STALE)->where('guard_name', 'web')->delete();

        // Semua izin resource/page/widget/custom yang dikenal Shield harus ada, supaya
        // super_admin lengkap dan semuanya bisa dicentang di Peran & Hak Akses.
        foreach (self::shieldPermissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            config('keycloak-sso.dcms.default_role', 'pegawai') => self::PEGAWAI,
            'kabid' => self::KABID,
            'direktur' => self::DIREKTUR,
        ];

        foreach ($roles as $name => $permissions) {
            Role::findOrCreate($name, 'web')->givePermissionTo(
                collect($permissions)->unique()->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'))
            );
        }

        Role::findOrCreate('super_admin', 'web')->givePermissionTo(Permission::query()->where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return array<int, string> */
    public static function shieldPermissions(): array
    {
        return collect(FilamentShield::getResources() ?? [])
            ->flatMap(fn (array $resource) => collect($resource['permissions'])->pluck('key'))
            ->merge(collect(FilamentShield::getPages() ?? [])->flatMap(fn (array $page) => array_keys($page['permissions'])))
            ->merge(collect(FilamentShield::getWidgets() ?? [])->flatMap(fn (array $widget) => array_keys($widget['permissions'])))
            ->merge(array_keys(FilamentShield::getCustomPermissions() ?? []))
            ->unique()
            ->values()
            ->all();
    }
}
