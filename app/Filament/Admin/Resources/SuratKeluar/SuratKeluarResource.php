<?php

namespace App\Filament\Admin\Resources\SuratKeluar;

use App\Filament\Admin\Resources\SuratKeluar\Pages\ListSuratKeluar;
use App\Filament\Admin\Resources\SuratKeluar\Tables\SuratKeluarTable;
use App\Models\Surat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Resource Filament untuk Surat Keluar.
 *
 * Resource ini HANYA-BACA dari sudut pandang Filament:
 *   - Tidak ada halaman Create, Edit, atau View.
 *   - Tidak ada aksi baris atau bulk action.
 *   - Pembuatan nomor surat dilakukan via modal "Buat Nomor Surat" di ListSuratKeluar.
 *
 * Akses dikendalikan oleh dua permission Spatie/Shield:
 *   ViewAny:SuratKeluar  — melihat daftar
 *   Create:SuratKeluar   — menggunakan tombol "Buat Nomor Surat"
 *
 * Untuk menambah/mengubah permission ke role, gunakan /admin/shield/roles.
 */
class SuratKeluarResource extends Resource
{
    protected static ?string $model = Surat::class;

    protected static string|UnitEnum|null $navigationGroup = 'Administrasi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'Surat Keluar';

    protected static ?string $pluralModelLabel = 'Surat Keluar';

    protected static ?string $recordTitleAttribute = 'nomor';

    // ── Akses berbasis permission Spatie/Shield ──────────────────────────────

    /**
     * Hanya user dengan permission ViewAny:SuratKeluar yang dapat mengakses.
     * Untuk memberikan ke role lain, gunakan /admin/shield/roles.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:SuratKeluar') ?? false;
    }

    // Resource ini hanya-baca: semua mutasi dimatikan secara eksplisit.

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    // ── Schema (wajib ada di Resource, tapi kosong karena tidak ada form) ────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    // ── Table ────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return SuratKeluarTable::configure($table);
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListSuratKeluar::route('/'),
        ];
    }
}
