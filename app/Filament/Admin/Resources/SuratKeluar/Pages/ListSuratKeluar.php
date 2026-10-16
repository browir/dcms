<?php

namespace App\Filament\Admin\Resources\SuratKeluar\Pages;

use App\Enums\JenisSurat;
use App\Filament\Admin\Resources\SuratKeluar\SuratKeluarResource;
use App\Models\Unit;
use App\Services\NomorSuratService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;

/**
 * Halaman daftar Surat Keluar.
 *
 * Fitur utama:
 *   - Tombol "Buat Nomor Surat" (modal) di header — hanya terlihat jika user
 *     memiliki permission Create:SuratKeluar.
 *   - Tabel riwayat hanya-baca (tidak ada edit, hapus, bulk action).
 *
 * Unit "siap" adalah unit yang memenuhi semua syarat:
 *   - units.kode        : terisi (bukan NULL dan bukan string kosong)
 *   - units.company_id  : terisi
 *   - companies.kode_surat : terisi
 * Unit yang tidak siap tidak akan tampil di dropdown, dan service juga menolaknya.
 */
class ListSuratKeluar extends ListRecords
{
    protected static string $resource = SuratKeluarResource::class;

    public function getTitle(): string
    {
        return 'Surat Keluar';
    }

    // ── Query unit siap ───────────────────────────────────────────────────────

    /**
     * Query untuk unit yang siap membuat nomor surat.
     *
     * Syarat "siap":
     *   1. units.kode        terisi (NOT NULL, NOT empty string)
     *   2. units.company_id  terisi (NOT NULL)
     *   3. companies.kode_surat terisi (NOT NULL, NOT empty string)
     *
     * Method ini dapat digunakan oleh test untuk memverifikasi logika filter.
     * PENTING: Jangan ubah kondisi ini tanpa sepengetahuan penulis tes.
     */
    public static function queryUnitSiap(): Builder
    {
        return Unit::query()
            ->whereNotNull('kode')
            ->where('kode', '<>', '')
            ->whereNotNull('company_id')
            ->whereHas(
                'company',
                fn (Builder $q) => $q->whereNotNull('kode_surat')->where('kode_surat', '<>', '')
            )
            ->with('company')
            ->orderBy('name');
    }

    // ── Header actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buat-nomor-surat')
                ->label('Buat Nomor Surat')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->visible(fn (): bool => auth()->user()?->can('Create:SuratKeluar') ?? false)
                ->modalHeading('Buat Nomor Surat Keluar')
                ->modalDescription('Isi formulir di bawah. Nomor surat akan dibuat secara otomatis setelah Anda menyimpan.')
                ->modalWidth('xl')
                ->modalSubmitActionLabel('Buat Nomor Surat')
                ->form(function (): array {
                    // Query unit yang siap — dijalankan saat modal dibuka.
                    $unitSiap = static::queryUnitSiap()->get();

                    $unitOptions = $unitSiap->mapWithKeys(
                        fn (Unit $u) => [
                            $u->id => "{$u->company->kode_surat}.{$u->kode} — {$u->name}",
                        ]
                    )->toArray();

                    $adaUnit = ! empty($unitOptions);

                    return [
                        Select::make('jenis_surat')
                            ->label('Jenis Surat')
                            ->helperText('Pilih jenis surat yang akan diterbitkan.')
                            ->options(JenisSurat::options())
                            ->required()
                            ->native(false)
                            ->searchable(),

                        $adaUnit
                            ? Select::make('unit_id')
                                ->label('Unit Pembuat')
                                ->helperText('Perusahaan akan mengikuti unit yang dipilih.')
                                ->options($unitOptions)
                                ->required()
                                ->native(false)
                                ->searchable()
                            : Placeholder::make('tidak_ada_unit')
                                ->label('Unit Pembuat')
                                ->content(new HtmlString(
                                    '<div class="rounded-lg border border-warning-300 bg-warning-50 px-4 py-3 text-sm text-warning-700">'
                                    . '<strong>⚠️ Belum ada unit yang siap digunakan.</strong><br>'
                                    . 'Unit harus memiliki <code class="rounded bg-warning-100 px-1">kode</code>, '
                                    . '<code class="rounded bg-warning-100 px-1">company_id</code> terisi, '
                                    . 'dan perusahaannya memiliki '
                                    . '<code class="rounded bg-warning-100 px-1">kode_surat</code>.'
                                    . '</div>'
                                )),

                        DatePicker::make('tanggal')
                            ->label('Tanggal Surat')
                            ->helperText('Tahun dan bulan tanggal ini yang menentukan format nomor.')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(today()->toDateString()),

                        TextInput::make('judul_surat')
                            ->label('Judul Surat')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Surat Tugas Perjalanan Dinas'),

                        TextInput::make('perihal')
                            ->label('Perihal')
                            ->maxLength(255)
                            ->placeholder('Contoh: Perihal pembahasan anggaran Q4'),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->placeholder('Keterangan tambahan (opsional)...'),
                    ];
                })
                ->action(function (array $data, Action $action): void {
                    // ── Pertahanan otorisasi (defensive authorization) ────────
                    // visible() hanya menyembunyikan tombol di UI;
                    // pemeriksaan ini memblokir eksekusi bahkan jika aksi dipanggil
                    // langsung (misalnya lewat API Livewire atau tools eksternal).
                    if (! auth()->user()?->can('Create:SuratKeluar')) {
                        Notification::make()
                            ->title('Akses Ditolak')
                            ->body('Anda tidak memiliki izin untuk membuat nomor surat.')
                            ->danger()
                            ->send();

                        $action->halt();

                        return;
                    }

                    // Cek unit_id tersedia (mungkin tidak ada karena tidak ada unit siap)
                    if (empty($data['unit_id'])) {
                        Notification::make()
                            ->title('Pilih unit pembuat terlebih dahulu')
                            ->body('Pastikan ada unit yang memiliki kode dan perusahaan dengan kode_surat.')
                            ->warning()
                            ->send();

                        $action->halt();

                        return;
                    }

                    $unit    = Unit::with(['company', 'department'])->findOrFail($data['unit_id']);
                    $jenis   = JenisSurat::from($data['jenis_surat']);
                    $tanggal = Carbon::parse($data['tanggal']);
                    $user    = auth()->user();

                    try {
                        $surat = NomorSuratService::generate($unit, $jenis, $tanggal, [
                            'judul_surat'      => $data['judul_surat'],
                            'perihal'          => $data['perihal']   ?? null,
                            'deskripsi'        => $data['deskripsi'] ?? null,
                            'dibuat_oleh_id'   => $user?->id,
                            'dibuat_oleh_nama' => $user?->name ?? 'Sistem',
                        ]);

                        // Tampilkan nomor hasil dengan tombol salin
                        Notification::make()
                            ->title('Nomor Surat Berhasil Dibuat')
                            ->body("**{$surat->nomor}**")
                            ->success()
                            ->persistent()
                            ->actions([
                                Action::make('salin-nomor')
                                    ->label('Salin Nomor')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->button()
                                    ->color('gray')
                                    ->alpineClickHandler(
                                        // Js::from() menjamin nomor di-encode JSON dengan benar,
                                        // aman dari tanda kutip atau karakter khusus lainnya.
                                        'window.navigator.clipboard.writeText(' . Js::from($surat->nomor) . ')'
                                    ),
                            ])
                            ->send();

                    } catch (\RuntimeException $e) {
                        // Tampilkan pesan galat yang ramah — tanpa stack trace
                        Notification::make()
                            ->title('Gagal Membuat Nomor Surat')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        // Tahan modal agar user dapat melihat pesan dan mencoba lagi
                        $action->halt();
                    }
                }),
        ];
    }
}
