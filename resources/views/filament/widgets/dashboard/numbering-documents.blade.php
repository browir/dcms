<x-filament-widgets::widget>
    <div class="dsh-grid dsh-grid-1-1">
        {{-- Penomoran surat --}}
        <section id="dsh-numbering" class="dsh-card" aria-labelledby="dsh-numbering-title">
            <header class="dsh-card-head">
                <span class="dsh-card-icon dsh-tone-numbering"><x-filament::icon icon="heroicon-o-hashtag" class="dsh-icon" /></span>
                <div class="dsh-card-heading">
                    <h3 id="dsh-numbering-title" class="dsh-card-title">Penomoran Surat</h3>
                    <p class="dsh-card-sub">Nomor terbit per jenis surat, tahun {{ $year }}</p>
                </div>
            </header>

            @if (! empty($companySequences))
                <div class="dsh-sequences">
                    <span class="dsh-sequences-label">Urutan terakhir</span>
                    @foreach ($companySequences as $sequence)
                        <span class="dsh-sequence"><strong>{{ $sequence['last'] }}</strong> {{ $sequence['code'] }}</span>
                    @endforeach
                </div>
            @endif

            @if (empty($counters))
                <div class="dsh-empty">
                    <x-filament::icon icon="heroicon-o-hashtag" class="dsh-empty-icon" />
                    <p>Belum ada nomor surat terbit tahun ini.</p>
                </div>
            @else
                <ul class="dsh-counters">
                    @foreach ($counters as $counter)
                        <li class="dsh-counter">
                            <span class="dsh-prefix" style="--dsh-cat: {{ $counter['color'] }}">{{ $counter['prefix'] }}</span>
                            <span class="dsh-counter-body">
                                <span class="dsh-counter-name">{{ $counter['name'] }}</span>
                                <span class="dsh-bar"><span style="width: {{ $counter['percent'] }}%; background: {{ $counter['color'] }}"></span></span>
                            </span>
                            <span class="dsh-counter-value">{{ $counter['issued'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (! empty($recentNumbers))
                <div class="dsh-subhead">Nomor terbaru</div>
                <ul class="dsh-list">
                    @foreach ($recentNumbers as $item)
                        <li>
                            <a href="{{ $item['url'] }}" wire:navigate class="dsh-row dsh-row-compact">
                                <span class="dsh-number">{{ $item['number'] }}</span>
                                <span class="dsh-row-body">
                                    <span class="dsh-item-title">{{ $item['title'] }}</span>
                                    <span class="dsh-item-meta">{{ $item['type'] }}@unless ($item['filled']) · <span class="dsh-text-warning">Belum diisi dokumen</span>@endunless</span>
                                </span>
                                <span class="dsh-row-age">{{ $item['date'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Statistik dokumen --}}
        <section class="dsh-card" aria-labelledby="dsh-docstats-title">
            <header class="dsh-card-head">
                <span class="dsh-card-icon dsh-tone-document"><x-filament::icon icon="heroicon-o-chart-bar" class="dsh-icon" /></span>
                <div class="dsh-card-heading">
                    <h3 id="dsh-docstats-title" class="dsh-card-title">Statistik Dokumen Resmi</h3>
                    <p class="dsh-card-sub">Status seluruh dokumen dan dokumen baru 6 bulan terakhir</p>
                </div>
                <a href="{{ route('filament.admin.resources.documents.index') }}" wire:navigate class="dsh-link">Semua dokumen</a>
            </header>

            <div class="dsh-kpis">
                <div class="dsh-kpi">
                    <span class="dsh-kpi-value">{{ number_format($docTotal, 0, ',', '.') }}</span>
                    <span class="dsh-kpi-label">Total dokumen</span>
                </div>
                <div class="dsh-kpi">
                    <span class="dsh-kpi-value">{{ $thisMonth }}</span>
                    <span class="dsh-kpi-label">Dibuat bulan ini</span>
                </div>
                <div class="dsh-kpi">
                    <span class="dsh-kpi-value">{{ $approvalRate !== null ? $approvalRate.'%' : '—' }}</span>
                    <span class="dsh-kpi-label">Tingkat persetujuan</span>
                </div>
            </div>

            @if ($docTotal > 0)
                <div class="dsh-stack" role="img" aria-label="Komposisi status dokumen">
                    @foreach ($breakdown as $status)
                        @if ($status['count'] > 0)
                            <span style="width: {{ $status['percent'] }}%; background: {{ $status['color'] }}"
                                  title="{{ $status['label'] }}: {{ $status['count'] }}"></span>
                        @endif
                    @endforeach
                </div>
                <ul class="dsh-legend">
                    @foreach ($breakdown as $status)
                        <li>
                            <span class="dsh-swatch" style="background: {{ $status['color'] }}"></span>
                            {{ $status['label'] }} <strong>{{ $status['count'] }}</strong>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="dsh-subhead">Dokumen baru per bulan</div>
            <div class="dsh-trend">
                @foreach ($trend as $month)
                    <div class="dsh-trend-col" title="{{ $month['label'] }}: {{ $month['count'] }} dokumen">
                        <span class="dsh-trend-value">{{ $month['count'] }}</span>
                        <span class="dsh-trend-track"><span class="dsh-trend-bar" style="height: {{ max($month['percent'], $month['count'] > 0 ? 6 : 0) }}%"></span></span>
                        <span class="dsh-trend-label">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-widgets::widget>
