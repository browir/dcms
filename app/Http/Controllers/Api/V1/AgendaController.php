<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AgendaResource;
use App\Models\Agenda;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengumuman kegiatan unit untuk aplikasi terintegrasi. Hanya kegiatan yang sudah
 * DIUMUMKAN (announced_at terisi) yang tampil.
 *
 * Routes:
 *   GET /api/v1/kegiatan        → index()
 *   GET /api/v1/kegiatan/{id}   → show()
 */
class AgendaController extends Controller
{
    use ApiResponse;

    /**
     * Query string:
     *   - from / to   : rentang tanggal (Y-m-d). Default: kegiatan yang belum selesai.
     *   - company     : kode perusahaan (kode surat atau kode), mis. SGG
     *   - audience    : unit | company | all
     *   - limit       : per halaman (maks 100, default 20)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'company' => ['nullable', 'string', 'max:20'],
            'audience' => ['nullable', 'in:unit,company,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $agendas = $this->announced()
            ->when($validated['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('end_time', '>=', $from))
            ->when($validated['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('date_time', '<=', $to))
            ->when(blank($validated['from'] ?? null) && blank($validated['to'] ?? null), fn (Builder $q) => $q->where('end_time', '>=', now()))
            ->when($validated['company'] ?? null, fn (Builder $q, $code) => $q->whereHas('company', fn (Builder $c) => $c
                ->where('kode_surat', $code)->orWhere('code', $code)))
            ->when($validated['audience'] ?? null, fn (Builder $q, $audience) => $q->where('audience', $audience))
            ->orderBy('date_time')
            ->paginate($validated['limit'] ?? 20);

        return $this->paginatedResponse(AgendaResource::collection($agendas), 'Kegiatan berhasil diambil.');
    }

    public function show(int $id): JsonResponse
    {
        $agenda = $this->announced()->find($id);

        if (! $agenda) {
            return $this->notFoundResponse('Kegiatan tidak ditemukan atau belum diumumkan.');
        }

        return $this->successResponse(data: new AgendaResource($agenda), message: 'Kegiatan berhasil diambil.');
    }

    private function announced(): Builder
    {
        return Agenda::query()
            ->whereNotNull('announced_at')
            ->where('status', '!=', 'cancelled')
            ->with(['company', 'department', 'unit', 'meetingLocation', 'letterNumber']);
    }
}
