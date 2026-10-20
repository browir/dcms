<?php

namespace App\Filament\Admin\Resources\LetterNumbers\Schemas;

use App\Enum\JenisSurat;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use App\Services\LetterNumberService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/**
 * Form "Ambil Nomor Surat" — dipakai di halaman Nomor Surat dan langsung dari form
 * Tambah Dokumen (tombol + pada pilihan nomor surat).
 */
class LetterNumberForm
{
    /** @return array<int, \Filament\Schemas\Components\Component|\Filament\Forms\Components\Field> */
    public static function components(): array
    {
        return [
            Text::make(fn (Get $get): HtmlString => new HtmlString(
                '<span style="font-size:.75rem;color:#64748b">Nomor yang akan terbit</span><br>'
                .'<code style="font-size:1rem;font-weight:700;letter-spacing:.02em">'.e(self::preview($get)).'</code>'
            )),

            Select::make('jenis_surat')
                ->label('Jenis Surat')
                ->options(JenisSurat::options())
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
                ->label('Tanggal Surat')
                ->helperText('Menentukan bulan & tahun di nomor dan urutan counter tahun tersebut.')
                ->native(false)
                ->displayFormat('d M Y')
                ->default(fn () => today())
                ->required()
                ->live(),

            TextInput::make('judul_surat')
                ->label('Judul Surat')
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
            $data['jenis_surat'],
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
            $get('jenis_surat'),
            $unit,
            filled($get('tanggal_surat')) ? Carbon::parse($get('tanggal_surat')) : today(),
        );
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
