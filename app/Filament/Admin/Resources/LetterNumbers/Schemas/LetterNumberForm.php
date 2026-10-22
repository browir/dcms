<?php

namespace App\Filament\Admin\Resources\LetterNumbers\Schemas;

use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use App\Services\LetterNumberService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/**
 * Form "Ambil Nomor Dokumen" — dipakai di halaman Nomor Dokumen dan langsung dari form
 * Tambah Dokumen (tombol + pada pilihan nomor dokumen).
 */
class LetterNumberForm
{
    /**
     * @param  string|null  $kelompok  batasi pilihan kategori ke Dokumen Resmi / Surat Keluar
     * @return array<int, Component|Field>
     */
    public static function components(?string $kelompok = null): array
    {
        return [
            Text::make(fn (Get $get): HtmlString => new HtmlString(
                '<span style="font-size:.75rem;color:#64748b">Nomor yang akan terbit</span><br>'
                .'<code style="font-size:1rem;font-weight:700;letter-spacing:.02em">'.e(self::preview($get)).'</code>'
            )),

            Select::make('category_id')
                ->label('Kategori Dokumen')
                ->helperText('Kode kategori dipakai di nomor dokumen. Kategori diatur di Data Master → Kategori Dokumen.')
                ->options(fn (Get $get): array => self::categoryOptions($get('unit_id'), $kelompok))
                ->searchable()
                ->native(false)
                ->required()
                ->live(),

            Select::make('unit_id')
                ->label('Unit')
                ->helperText('Perusahaan penerbit mengikuti unit.')
                ->options(fn () => self::unitOptions())
                ->default(fn () => Auth::user()?->unit_id)
                ->searchable()
                ->native(false)
                ->required()
                ->live(),

            DatePicker::make('tanggal_surat')
                ->label('Tanggal Dokumen')
                ->helperText('Menentukan bulan & tahun di nomor dan urutan counter tahun tersebut.')
                ->native(false)
                ->displayFormat('d M Y')
                ->default(fn () => today())
                ->required()
                ->live(),

            TextInput::make('judul_surat')
                ->label('Judul')
                ->required()
                ->maxLength(255),

            TextInput::make('perihal')
                ->label('Perihal')
                ->maxLength(255),

            Textarea::make('deskripsi')
                ->label('Deskripsi')
                ->rows(2),
        ];
    }

    /** Terbitkan nomor dari data form di atas. */
    public static function issue(array $data): LetterNumber
    {
        /** @var User|null $user */
        $user = Auth::user();

        return app(LetterNumberService::class)->issue(
            Unit::query()->findOrFail($data['unit_id']),
            DocumentCategory::query()->findOrFail($data['category_id']),
            Carbon::parse($data['tanggal_surat']),
            $data['judul_surat'],
            $data['perihal'] ?? null,
            $data['deskripsi'] ?? null,
            $user,
        );
    }

    private static function preview(Get $get): string
    {
        $unit = Unit::query()->with('department.company')->find($get('unit_id'));

        return app(LetterNumberService::class)->preview(
            $unit?->department?->company,
            DocumentCategory::query()->find($get('category_id'))?->code,
            $unit,
            filled($get('tanggal_surat')) ? Carbon::parse($get('tanggal_surat')) : today(),
        );
    }

    /**
     * Kategori aktif untuk perusahaan unit terpilih (atau perusahaan user), dikelompokkan
     * per Dokumen Resmi / Surat Keluar.
     *
     * @return array<string, array<int, string>>|array<int, string>
     */
    public static function categoryOptions(mixed $unitId, ?string $kelompok = null): array
    {
        $companyId = Unit::query()->with('department')->find($unitId)?->department?->company_id
            ?? Auth::user()?->company_id;

        $categories = DocumentCategory::query()
            ->usableFor($companyId)
            ->when($kelompok, fn (Builder $q) => $q->where('kelompok', $kelompok))
            ->orderBy('kelompok')
            ->orderBy('name')
            ->get();

        if ($kelompok) {
            return $categories->mapWithKeys(fn (DocumentCategory $c) => [$c->id => $c->option_label])->all();
        }

        return $categories
            ->groupBy(fn (DocumentCategory $c) => DocumentCategory::KELOMPOK[$c->kelompok] ?? 'Lainnya')
            ->map(fn ($group) => $group->mapWithKeys(fn (DocumentCategory $c) => [$c->id => $c->option_label])->all())
            ->all();
    }

    /** Unit yang boleh dipilih: semua untuk admin global, selain itu unit di perusahaan user. */
    private static function unitOptions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $all = $user?->hasRole('super_admin') || $user?->can('view_all_companies_data');
        $service = app(LetterNumberService::class);

        return Unit::query()
            ->with('department.company')
            ->where('is_active', true)
            ->whereHas('department', fn (Builder $q) => $q
                ->when(! $all, fn (Builder $d) => $d->where('company_id', $user?->company_id)))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Unit $unit) => [
                $unit->id => $service->companyCode($unit->department->company).' · '.$service->unitCode($unit).' — '.$unit->name,
            ])
            ->all();
    }
}
