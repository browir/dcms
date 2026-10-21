<?php

namespace App\Http\Resources\Api;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Transforms a Document model into a safe, client-facing JSON structure.
 *
 * Exposes only intended fields — never raw internal IDs for sensitive relations.
 */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Core identity
            'id' => $this->id,
            'title' => $this->title,
            'code_number' => $this->code_number,
            'description' => $this->description,
            'version' => $this->version,
            'status' => $this->status,
            'status_label' => Document::getStatuses()[$this->status] ?? $this->status,
            'document_type' => $this->document_type,

            // File metadata
            'file_name' => $this->file_name,
            'file_url' => $this->resolveFileUrl(),
            'file_hash' => $this->file_hash,

            // Flags
            'is_mandatory_read' => (bool) $this->is_mandatory_read,
            'is_public' => (bool) $this->is_public,
            'is_expired' => $this->is_expired,
            'is_expiring_soon' => $this->is_expiring_soon,
            'expires_at' => $this->expires_at?->toDateString(),

            // Author / editor
            'author' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'updated_by_user' => $this->whenLoaded('updatedByUser', fn () => [
                'id' => $this->updatedByUser?->id,
                'name' => $this->updatedByUser?->name,
            ]),

            // Review workflow
            'kabid_reviewer' => $this->whenLoaded('kabidReviewer', fn () => [
                'id' => $this->kabidReviewer?->id,
                'name' => $this->kabidReviewer?->name,
                'reviewed_at' => $this->kabid_reviewed_at?->toISOString(),
                'notes' => $this->kabid_notes,
            ]),
            'direktur_reviewer' => $this->whenLoaded('direkturReviewer', fn () => [
                'id' => $this->direkturReviewer?->id,
                'name' => $this->direkturReviewer?->name,
                'reviewed_at' => $this->direktur_reviewed_at?->toISOString(),
                'notes' => $this->direktur_notes,
            ]),

            // Organisational hierarchy
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company?->id,
                'name' => $this->company?->name,
                'code' => $this->company?->code,
            ]),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
                'code' => $this->department?->code,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit?->id,
                'name' => $this->unit?->name,
                'code' => $this->unit?->code ?? $this->unit?->prefix,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'prefix' => $this->category?->prefix,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Resolve a temporary signed URL for the document file.
     * Uses the 'documents' disk. Falls back to generated_file_path if no primary file.
     */
    protected function resolveFileUrl(): ?string
    {
        $path = $this->file_path ?? $this->generated_file_path;

        if (! $path) {
            return null;
        }

        try {
            $disk = Storage::disk('documents');

            // Disk privat: berikan link bertanda tangan yang berlaku 1 jam.
            return $disk->exists($path)
                ? URL::temporarySignedRoute('documents.serve.signed', now()->addHour(), ['path' => $path])
                : null;
        } catch (\Exception) {
            return null;
        }
    }
}
