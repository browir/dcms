<?php

namespace App\Filament\Admin\Resources\IncomingLetters\Pages;

use App\Filament\Admin\Resources\IncomingLetters\IncomingLetterResource;
use App\Models\IncomingLetter;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListIncomingLetters extends ListRecords
{
    protected static string $resource = IncomingLetterResource::class;

    protected ?string $subheading = 'Surat dari pihak luar: dicatat dengan nomor agenda masuk dan diteruskan ke unit tujuan.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Catat Surat Masuk')
                ->icon('heroicon-m-plus'),
        ];
    }

    public function getTabs(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $forUnit = fn (Builder $q): Builder => $q->where('unit_id', $user->unit_id ?? 0);
        $count = fn (callable $scope): ?int => ($n = $scope(IncomingLetter::query()->access())->count()) > 0 ? $n : null;

        return [
            'unit_saya' => Tab::make('Untuk Unit Saya')
                ->icon('heroicon-m-inbox')
                ->modifyQueryUsing($forUnit)
                ->badge(fn () => $count(fn (Builder $q) => $forUnit($q)->where('status', IncomingLetter::STATUS_BARU)))
                ->badgeColor('warning'),
            'belum_selesai' => Tab::make('Belum Selesai')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', '!=', IncomingLetter::STATUS_SELESAI)),
            'selesai' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', IncomingLetter::STATUS_SELESAI)),
            'semua' => Tab::make('Semua'),
        ];
    }

    /** Ada surat untuk unit user → buka tab itu, selain itu "Semua". */
    public function getDefaultActiveTab(): string|int|null
    {
        $unitId = Auth::user()?->unit_id;

        return $unitId && IncomingLetter::query()->access()->where('unit_id', $unitId)->exists() ? 'unit_saya' : 'semua';
    }
}
