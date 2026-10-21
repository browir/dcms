<?php

namespace App\Http\Resources\Api;

use App\Models\Agenda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * Kegiatan unit yang sudah diumumkan, untuk aplikasi lain yang terintegrasi
 * (papan pengumuman, aplikasi mobile, HRIS, dsb.).
 *
 * @mixin Agenda
 */
class AgendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->kategori,
            'category_label' => Agenda::KATEGORI[$this->kategori] ?? null,
            'description' => $this->description,

            'start' => $this->date_time?->toIso8601String(),
            'end' => $this->effectiveEndTime()->toIso8601String(),
            'schedule_label' => $this->scheduleLabel(),
            'place' => $this->placeName(),
            'room' => $this->whenLoaded('meetingLocation', fn () => $this->meetingLocation ? [
                'id' => $this->meetingLocation->id,
                'name' => $this->meetingLocation->name,
                'capacity' => $this->meetingLocation->capacity,
            ] : null),

            'organizer' => [
                'company' => $this->company?->name,
                'company_code' => $this->company ? strtoupper($this->company->kode_surat ?: $this->company->code) : null,
                'department' => $this->department?->name,
                'unit' => $this->unit?->name,
            ],
            'audience' => $this->audience,
            'status' => $this->status,

            'notice' => $this->letterNumber ? [
                'number' => $this->letterNumber->nomor,
                'date' => $this->letterNumber->tanggal_surat?->toDateString(),
                // Link PDF bertanda tangan, berlaku 30 hari.
                'pdf_url' => URL::temporarySignedRoute('kegiatan.surat.signed', now()->addDays(30), ['agenda' => $this->id]),
            ] : null,

            'announced_at' => $this->announced_at?->toIso8601String(),
            'url' => url('/admin/kegiatan/'.$this->id),
        ];
    }
}
