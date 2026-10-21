<?php

namespace App\Filament\Admin\Resources\Agendas\Pages;

use App\Filament\Admin\Resources\Agendas\AgendaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAgendas extends ListRecords
{
    protected static string $resource = AgendaResource::class;

    public function getSubheading(): ?string
    {
        return 'Agenda kegiatan unit. Bisa memakai ruangan, dibuatkan surat pemberitahuan, dan diumumkan ke karyawan maupun grup WhatsApp.';
    }

    public function getTabs(): array
    {
        return [
            'mendatang' => Tab::make('Mendatang')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('end_time', '>=', now())->where('status', '!=', 'cancelled')),
            'lewat' => Tab::make('Sudah lewat')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('end_time', '<', now())->reorder('date_time', 'desc')),
            'semua' => Tab::make('Semua'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Catat Kegiatan')->icon('heroicon-o-plus'),
        ];
    }
}
