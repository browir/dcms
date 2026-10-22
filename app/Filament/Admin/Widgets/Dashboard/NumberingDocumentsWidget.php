<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\Documents\DocumentResource;
use App\Filament\Admin\Resources\LetterNumbers\LetterNumberResource;
use App\Filament\Admin\Widgets\Dashboard\Concerns\DashboardQueries;
use App\Models\Document;
use App\Models\LetterNumber;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran surat (counter per kategori + nomor yang baru terbit)
 * dan statistik dokumen resmi (status & tren 6 bulan).
 */
class NumberingDocumentsWidget extends Widget
{
    use DashboardQueries;

    // Tampil-tidaknya widget diatur per role di Peran & Hak Akses → Widget (View:<NamaWidget>).
    use HasWidgetShield;

    protected string $view = 'filament.widgets.dashboard.numbering-documents';

    /** Nama widget di Peran & Hak Akses → Widget. */
    public function getHeading(): string
    {
        return 'Beranda: Penomoran Surat & Statistik Dokumen';
    }

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 40;

    protected function getViewData(): array
    {
        return [
            'counters' => $this->numberCounters(),
            'companySequences' => $this->companySequences(),
            'recentNumbers' => $this->recentNumbers(),
            'year' => now()->year,
            'documentsUrl' => $this->opens(DocumentResource::class) ? DocumentResource::getUrl() : null,
            ...$this->documentStats(),
        ];
    }

    /** Nomor surat terbit tahun ini per jenis surat (dari register Nomor Surat). */
    private function numberCounters(): array
    {
        $rows = LetterNumber::query()->access()
            ->whereYear('tanggal_surat', now()->year)
            ->select('jenis_surat', DB::raw('COUNT(*) as issued'))
            ->groupBy('jenis_surat')
            ->orderByDesc('issued')
            ->limit(6)
            ->get();

        $max = max(1, (int) $rows->max('issued'));

        return $rows->map(function (LetterNumber $row) use ($max) {
            /** @var JenisSurat $jenis */
            $jenis = $row->jenis_surat;

            return [
                'name' => $jenis->label(),
                'prefix' => $jenis->value,
                // Warna jenis dari enum (hue unik per jenis), dibuat lebih terang untuk bar.
                'color' => str_replace('26%)', '42%)', $jenis->warna()['text']),
                'issued' => (int) $row->issued,
                'percent' => round($row->issued / $max * 100),
            ];
        })->all();
    }

    /** Urutan terakhir counter nomor surat tahun ini, per perusahaan penerbit. */
    private function companySequences(): array
    {
        $user = $this->currentUser();

        return DB::table('letter_number_counters')
            ->join('companies', 'companies.id', '=', 'letter_number_counters.company_id')
            ->where('letter_number_counters.year', now()->year)
            ->when(! $this->seesAllCompanies(), fn ($q) => $q->where('companies.id', $user->company_id))
            ->orderBy('companies.name')
            ->get(['companies.code', 'companies.kode_surat', 'letter_number_counters.last_number'])
            ->map(fn ($row) => [
                'code' => strtoupper($row->kode_surat ?: $row->code),
                'last' => sprintf('%03d', $row->last_number),
            ])
            ->all();
    }

    /** Nomor terbaru di register, beserta status apakah dokumennya sudah diisi. */
    private function recentNumbers(): array
    {
        return LetterNumber::query()->access()
            ->with('document:id,letter_number_id,deleted_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (LetterNumber $letter) => [
                'number' => $letter->nomor,
                'title' => $letter->judul_surat,
                'type' => $letter->jenis_surat->label(),
                'filled' => $letter->document !== null,
                'date' => $letter->tanggal_surat->locale('id')->isoFormat('D MMM YYYY'),
                'url' => match (true) {
                    $letter->document && ! $letter->document->trashed() && $this->opens(DocumentResource::class) => route('filament.admin.resources.documents.view', $letter->document),
                    $this->opens(LetterNumberResource::class) => LetterNumberResource::getUrl(),
                    default => null,
                },
            ])
            ->all();
    }

    private function documentStats(): array
    {
        $statusCounts = Document::query()->access()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = [
            [Document::STATUS_APPROVED, 'Disetujui', '#059669'],
            [Document::STATUS_PENDING_KABID, 'Menunggu Kabid', '#d97706'],
            [Document::STATUS_PENDING_DIREKTUR, 'Menunggu Direktur', '#2563eb'],
            [Document::STATUS_DRAFT, 'Draft', '#94a3b8'],
            [Document::STATUS_REJECTED, 'Ditolak', '#dc2626'],
            [Document::STATUS_ARCHIVED, 'Diarsipkan', '#6366f1'],
        ];

        $total = (int) $statusCounts->sum();
        $breakdown = collect($statuses)->map(fn ($s) => [
            'label' => $s[1],
            'color' => $s[2],
            'count' => (int) ($statusCounts[$s[0]] ?? 0),
            'percent' => $total > 0 ? round(($statusCounts[$s[0]] ?? 0) / $total * 100, 1) : 0,
        ])->all();

        $start = now()->startOfMonth()->subMonths(5);
        $monthly = Document::query()->access()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->countBy(fn (Document $d) => $d->created_at->format('Y-m'));

        $trend = collect(range(0, 5))->map(function ($i) use ($start, $monthly) {
            $month = $start->copy()->addMonths($i);

            return [
                'label' => $month->locale('id')->isoFormat('MMM'),
                'count' => (int) ($monthly[$month->format('Y-m')] ?? 0),
            ];
        });
        $maxTrend = max(1, (int) $trend->max('count'));

        $approved = (int) ($statusCounts[Document::STATUS_APPROVED] ?? 0);
        $rejected = (int) ($statusCounts[Document::STATUS_REJECTED] ?? 0);

        return [
            'docTotal' => $total,
            'breakdown' => $breakdown,
            'trend' => $trend->map(fn ($t) => [...$t, 'percent' => round($t['count'] / $maxTrend * 100)])->all(),
            'approvalRate' => ($approved + $rejected) > 0 ? round($approved / ($approved + $rejected) * 100) : null,
            'thisMonth' => (int) ($monthly[now()->format('Y-m')] ?? 0),
        ];
    }
}
