<?php

namespace App\Filament\Admin\Resources\Agendas\Pages;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\Agendas\AgendaActions;
use App\Filament\Admin\Resources\Agendas\AgendaResource;
use App\Models\Agenda;
use App\Models\Unit;
use App\Services\AgendaAnnouncer;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAgenda extends CreateRecord
{
    protected static string $resource = AgendaResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, ...self::organization($data['unit_id'] ?? null), 'created_by' => auth()->id()];
    }

    /** Setelah tersimpan: surat pemberitahuan (opsional), lalu pengumuman (opsional). */
    protected function afterCreate(): void
    {
        /** @var Agenda $agenda */
        $agenda = $this->getRecord();

        if ($this->data['buat_surat'] ?? false) {
            try {
                $letter = app(AgendaAnnouncer::class)->issueNotice(
                    $agenda,
                    JenisSurat::tryFrom((string) ($this->data['jenis_surat'] ?? '')) ?? JenisSurat::EDARAN_PENGUMUMAN,
                    auth()->user(),
                );

                Notification::make()->success()->title('Surat pemberitahuan dibuat')->body($letter->nomor)->send();
            } catch (\Throwable $e) {
                report($e);
                Notification::make()->danger()->title('Surat pemberitahuan gagal dibuat')->body($e->getMessage())->send();
            }
        }

        AgendaActions::runAnnouncement(
            $agenda->refresh(),
            (bool) ($this->data['umumkan_aplikasi'] ?? false),
            (bool) ($this->data['umumkan_whatsapp'] ?? false),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    /** Perusahaan & departemen penyelenggara mengikuti unit. */
    public static function organization(mixed $unitId): array
    {
        $unit = Unit::query()->with('department')->find($unitId);

        return [
            'department_id' => $unit?->department_id,
            'company_id' => $unit?->department?->company_id ?? auth()->user()?->company_id,
        ];
    }
}
