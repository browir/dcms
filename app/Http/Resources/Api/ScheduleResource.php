<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Transforms a Meeting model into a client-facing JSON structure.
 *
 * Exposes agenda, location/link, attendee list with attendance status,
 * and any attached SOP/Document references.
 */
class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Core identity
            'id' => $this->id,
            'title' => $this->title,
            'doc_number' => $this->doc_number,
            'agenda' => $this->agenda,
            'content' => $this->content,

            // Time & location
            'date_time' => $this->date_time?->toISOString(),
            'date' => $this->date_time?->toDateString(),
            'time' => $this->date_time?->format('H:i'),
            'location' => $this->location,

            // Status
            'status' => $this->status,
            'status_label' => $this->resolveStatusLabel(),

            // Organiser info
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'email' => $this->creator?->email,
            ]),
            'notulis' => $this->whenLoaded('notulis', fn () => [
                'id' => $this->notulis?->id,
                'name' => $this->notulis?->name,
            ]),

            // Participants list with attendance status
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'attendance' => $user->pivot->attendance,
            ])->values()->all()
            ),

            // Attachments — array of file paths stored in JSON column
            'attachments' => $this->resolveAttachments(),

            // Organisational hierarchy
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company?->id,
                'name' => $this->company?->name,
            ]),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit?->id,
                'name' => $this->unit?->name,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Map DB status values to human-readable labels.
     */
    private function resolveStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Dijadwalkan',
            'ongoing' => 'Sedang Berlangsung',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    /**
     * Resolve the attachments JSON column into an array of objects with URLs.
     * The `attachments` column stores an array of file paths (strings).
     */
    private function resolveAttachments(): array
    {
        $rawAttachments = $this->attachments;

        if (empty($rawAttachments) || ! is_array($rawAttachments)) {
            return [];
        }

        return collect($rawAttachments)
            ->map(function (mixed $item) {
                // Attachments may be a plain path string or an associative array
                if (is_string($item)) {
                    return [
                        'file_name' => basename($item),
                        'file_path' => $item,
                        'file_url' => $this->resolveAttachmentUrl($item),
                    ];
                }

                if (is_array($item)) {
                    $path = $item['path'] ?? $item['file_path'] ?? null;

                    return [
                        'file_name' => $item['name'] ?? $item['file_name'] ?? ($path ? basename($path) : null),
                        'file_path' => $path,
                        'file_url' => $path ? $this->resolveAttachmentUrl($path) : null,
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * URL publik lampiran rapat (disk public). Disk documents privat, jadi tidak dipakai di sini.
     */
    private function resolveAttachmentUrl(string $path): ?string
    {
        try {
            return Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : null;
        } catch (\Exception) {
            return null;
        }
    }
}
