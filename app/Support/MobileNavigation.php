<?php

namespace App\Support;

use App\Filament\Admin\Pages\MyBookmarks;
use App\Filament\Admin\Resources\Agendas\AgendaResource;
use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\IncomingLetters\IncomingLetterResource;
use App\Filament\Admin\Resources\LetterNumbers\LetterNumberResource;
use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Models\DocumentCategory;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

/**
 * Data navigasi mobile (bar bawah + menu). Sumbernya navigasi Filament yang sama dengan
 * navbar desktop, sehingga halaman yang tidak boleh diakses user tidak pernah muncul.
 */
class MobileNavigation
{
    /** Ikon & warna per halaman, dikunci dengan path URL setelah prefix panel. */
    private const STYLES = [
        '' => ['home', '#2563eb'],
        'meetings' => ['calendar', '#0891b2'],
        'kegiatan' => ['my_cal', '#ea580c'],
        'nomor-dokumen' => ['my_doc', '#d97706'],
        'documents' => ['document', '#7c3aed'],
        'documents?surat_keluar' => ['send', '#0284c7'],
        'surat-masuk' => ['inbox', '#059669'],
        'bookmarks' => ['bookmark', '#db2777'],
        'document-change-requests' => ['change', '#0f766e'],
        'compliance-hub' => ['compliance', '#2563eb'],
        'reminders' => ['bell', '#ea580c'],
        'recycle-bin' => ['recycle', '#ef4444'],
        'companies' => ['company', '#059669'],
        'departments' => ['dept', '#7c3aed'],
        'units' => ['unit', '#f59e0b'],
        'document-categories' => ['category', '#0891b2'],
        'meeting-locations' => ['location', '#dc2626'],
        'users' => ['users', '#1d4ed8'],
        'shield/roles' => ['role', '#9333ea'],
        'audit-trail' => ['audit', '#64748b'],
        'app-settings' => ['dashboard', '#475569'],
    ];

    /**
     * Tujuan utama di bar bawah (tombol Menu ditambahkan oleh view). Item dengan `options`
     * membuka pilihan dulu (mis. Dokumen → Dokumen Resmi / Surat Keluar / Surat Masuk).
     *
     * @return array<int, array{label: string, url: string, icon: string, active: bool, options: array<int, array{label: string, url: string, icon: string, color: string}>}>
     */
    public static function bottomBar(): array
    {
        $items = [
            ['label' => 'Beranda', 'url' => Filament::getUrl(), 'icon' => 'home', 'can' => true],
            ['label' => 'Dokumen', 'icon' => 'document', 'options' => self::documentOptions()],
            ['label' => 'Rapat', 'resource' => MeetingResource::class, 'icon' => 'calendar'],
            ['label' => 'Kegiatan', 'resource' => AgendaResource::class, 'icon' => 'my_cal'],
        ];

        return collect($items)
            ->filter(fn (array $item): bool => isset($item['options']) ? $item['options'] !== [] : ($item['can'] ?? $item['resource']::canAccess()))
            ->map(function (array $item): array {
                $options = $item['options'] ?? [];
                // Hanya satu pilihan yang boleh dibuka → langsung ke halaman itu, tanpa pilihan.
                $url = $options[0]['url'] ?? (isset($item['resource']) ? $item['resource']::getUrl() : $item['url']);

                return [
                    'label' => $item['label'],
                    'url' => $url,
                    'icon' => $item['icon'],
                    'active' => $options
                        ? collect($options)->contains('active', true)
                        : self::isActive($url, exact: ! isset($item['resource'])),
                    'options' => count($options) > 1 ? $options : [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Pilihan tombol Dokumen di bar bawah, hanya yang boleh dibuka user.
     *
     * @return array<int, array{label: string, url: string, icon: string, color: string, active: bool, description: string}>
     */
    public static function documentOptions(): array
    {
        $options = [];

        if (DocumentResource::canAccess()) {
            foreach (DocumentCategory::KELOMPOK as $kelompok => $label) {
                $options[] = [
                    ...self::item($label, $url = DocumentResource::getUrl('index', ['kelompok' => $kelompok])),
                    'active' => self::isActive($url, exact: false) && request()->query('kelompok', DocumentCategory::KELOMPOK_DOKUMEN) === $kelompok,
                    'description' => $kelompok === DocumentCategory::KELOMPOK_SURAT_KELUAR
                        ? 'Surat resmi yang kita terbitkan'
                        : 'SOP, SK, kebijakan, pedoman, formulir',
                ];
            }
        }

        if (IncomingLetterResource::canAccess()) {
            $options[] = [...self::item('Surat Masuk', $url = IncomingLetterResource::getUrl()), 'active' => self::isActive($url, exact: false), 'description' => 'Surat dari pihak luar'];
        }

        if (LetterNumberResource::canAccess()) {
            $options[] = [...self::item('Nomor Dokumen', $url = LetterNumberResource::getUrl()), 'active' => self::isActive($url, exact: false), 'description' => 'Ambil nomor sebelum membuat dokumen'];
        }

        return $options;
    }

    /**
     * Grup menu: aksi "Buat Baru" yang diizinkan, lalu grup navigasi desktop.
     *
     * @param  array<int, string>  $exceptUrls  URL yang sudah tampil di bar bawah
     * @return array<int, array{label: string, items: array<int, array{label: string, url: string, icon: string, color: string}>}>
     */
    public static function menuGroups(array $exceptUrls = []): array
    {
        $groups = [];

        $create = collect([
            [DocumentResource::class, 'Tambah Dokumen', 'add_doc', '#059669'],
            [MeetingResource::class, 'Tambah Rapat', 'add_cal', '#d97706'],
            [AgendaResource::class, 'Catat Kegiatan', 'my_cal', '#ea580c'],
        ])
            ->filter(fn (array $action): bool => $action[0]::canAccess() && $action[0]::canCreate())
            ->map(fn (array $action): array => [
                'label' => $action[1],
                'url' => $action[0]::getUrl('create'),
                'icon' => $action[2],
                'color' => $action[3],
            ])
            ->values()
            ->all();

        if ($create) {
            $groups[] = ['label' => 'Buat Baru', 'items' => $create];
        }

        foreach (Filament::getNavigation() as $group) {
            /** @var NavigationGroup $group */
            $items = collect($group->getItems())
                ->filter(fn (NavigationItem $item): bool => ! in_array($item->getUrl(), $exceptUrls, true))
                ->map(fn (NavigationItem $item): array => self::item($item->getLabel(), $item->getUrl()))
                ->values()
                ->all();

            // Bookmark tidak tampil di navbar desktop (ada ikon di topbar), tapi berguna di menu mobile.
            if ($group->getLabel() === 'Dokumen' && MyBookmarks::canAccess()) {
                $items[] = self::item('Bookmark', MyBookmarks::getUrl());
            }

            if ($items) {
                $groups[] = ['label' => $group->getLabel() ?? 'Utama', 'items' => $items];
            }
        }

        return $groups;
    }

    /** @return array{label: string, url: string, icon: string, color: string} */
    private static function item(string $label, string $url): array
    {
        // Surat Keluar memakai URL daftar dokumen dengan ?kelompok=surat_keluar.
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $key = self::pathKey($url).(isset($query['kelompok']) && $query['kelompok'] !== 'dokumen' ? '?'.$query['kelompok'] : '');
        [$icon, $color] = self::STYLES[$key] ?? self::STYLES[self::pathKey($url)] ?? ['document', '#475569'];

        return ['label' => $label, 'url' => $url, 'icon' => $icon, 'color' => $color];
    }

    /** Path setelah prefix panel, mis. ".../admin/shield/roles" → "shield/roles". */
    private static function pathKey(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $panelPath = trim((string) parse_url(Filament::getUrl(), PHP_URL_PATH), '/');

        return trim(str_starts_with($path, $panelPath) ? substr($path, strlen($panelPath)) : $path, '/');
    }

    private static function isActive(string $url, bool $exact): bool
    {
        $current = trim(request()->path(), '/');
        $target = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $exact ? $current === $target : ($current === $target || str_starts_with($current, $target.'/'));
    }
}
