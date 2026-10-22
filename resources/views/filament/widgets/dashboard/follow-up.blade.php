<x-filament-widgets::widget>
    <div id="dsh-followup" class="dsh-grid dsh-grid-1-1">
        {{-- Notulensi rapat --}}
        <section class="dsh-card" aria-labelledby="dsh-minutes-title">
            <header class="dsh-card-head">
                <span class="dsh-card-icon dsh-tone-minutes"><x-filament::icon icon="heroicon-o-pencil-square" class="dsh-icon" /></span>
                <div class="dsh-card-heading">
                    <h3 id="dsh-minutes-title" class="dsh-card-title">Notulensi Rapat</h3>
                    <p class="dsh-card-sub">Rapat yang sudah berlangsung tapi belum ada notulennya</p>
                </div>
                @if ($pendingMinutesCount > 0)
                    <span class="dsh-count dsh-count-alert">{{ $pendingMinutesCount }}</span>
                @endif
            </header>

            @if ($pendingMinutes->isEmpty())
                <div class="dsh-empty dsh-empty-ok">
                    <x-filament::icon icon="heroicon-o-check-circle" class="dsh-empty-icon" />
                    <p>Semua rapat sudah bernotulen.</p>
                </div>
            @else
                <ul class="dsh-list">
                    @foreach ($pendingMinutes as $item)
                        <li>
                            <x-dsh-link :href="$item['url']" class="dsh-row">
                                <span class="dsh-row-body">
                                    <span class="dsh-item-title">{{ $item['title'] }}</span>
                                    <span class="dsh-item-meta">{{ $item['meta'] }}</span>
                                </span>
                                <span class="dsh-row-side">
                                    @if ($item['mine'])
                                        <span class="dsh-chip dsh-chip-minutes">Tugas Anda</span>
                                    @endif
                                    <span class="dsh-row-age">{{ $item['age'] }}</span>
                                </span>
                            </x-dsh-link>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($recentMinutes->isNotEmpty())
                <div class="dsh-subhead">Notulen terbaru</div>
                <ul class="dsh-list">
                    @foreach ($recentMinutes as $item)
                        <li>
                            <x-dsh-link :href="$item['url']" class="dsh-row dsh-row-compact">
                                <x-filament::icon icon="heroicon-m-document-check" class="dsh-row-lead dsh-text-minutes" />
                                <span class="dsh-row-body">
                                    <span class="dsh-item-title">{{ $item['title'] }}</span>
                                    <span class="dsh-item-meta">{{ $item['meta'] }}</span>
                                </span>
                                @if ($item['actions'] > 0)
                                    <span class="dsh-row-age">{{ $item['actions'] }} action plan</span>
                                @endif
                            </x-dsh-link>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Dokumen resmi --}}
        <section class="dsh-card" aria-labelledby="dsh-docs-title">
            <header class="dsh-card-head">
                <span class="dsh-card-icon dsh-tone-document"><x-filament::icon icon="heroicon-o-document-text" class="dsh-icon" /></span>
                <div class="dsh-card-heading">
                    <h3 id="dsh-docs-title" class="dsh-card-title">Dokumen Perlu Tindakan</h3>
                    <p class="dsh-card-sub">Antrean persetujuan dan dokumen yang perlu Anda selesaikan</p>
                </div>
                @if ($documentsCount > 0)
                    <span class="dsh-count dsh-count-alert">{{ $documentsCount }}</span>
                @endif
            </header>

            @if ($documents->isEmpty())
                <div class="dsh-empty dsh-empty-ok">
                    <x-filament::icon icon="heroicon-o-check-circle" class="dsh-empty-icon" />
                    <p>Tidak ada dokumen yang menunggu tindakan Anda.</p>
                </div>
            @else
                <ul class="dsh-list">
                    @foreach ($documents as $item)
                        <li>
                            <x-dsh-link :href="$item['url']" class="dsh-row">
                                <span class="dsh-row-body">
                                    <span class="dsh-item-title">{{ $item['title'] }}</span>
                                    <span class="dsh-item-meta dsh-mono">{{ $item['meta'] }}</span>
                                </span>
                                <span class="dsh-chip dsh-doc-{{ $item['status'] }}">{{ $item['statusLabel'] }}</span>
                            </x-dsh-link>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($unfilledNumbers->isNotEmpty())
                <div class="dsh-subhead">Nomor surat Anda yang belum diisi dokumen</div>
                <ul class="dsh-list">
                    @foreach ($unfilledNumbers as $item)
                        <li>
                            <x-dsh-link :href="$item['url']" class="dsh-row dsh-row-compact">
                                <span class="dsh-number">{{ $item['number'] }}</span>
                                <span class="dsh-row-body">
                                    <span class="dsh-item-title">{{ $item['title'] }}</span>
                                </span>
                                <span class="dsh-row-age dsh-text-warning">Isi dokumen →</span>
                            </x-dsh-link>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($expiring->isNotEmpty())
                <div class="dsh-subhead">Masa berlaku segera habis</div>
                <ul class="dsh-list">
                    @foreach ($expiring as $item)
                        <li>
                            <x-dsh-link :href="$item['url']" class="dsh-row dsh-row-compact">
                                <x-filament::icon icon="heroicon-m-clock" @class(['dsh-row-lead', 'dsh-text-danger' => $item['expired'], 'dsh-text-warning' => ! $item['expired']]) />
                                <span class="dsh-row-body">
                                    <span class="dsh-item-title">{{ $item['title'] }}</span>
                                </span>
                                <span @class(['dsh-row-age', 'dsh-text-danger' => $item['expired']])>{{ $item['when'] }}</span>
                            </x-dsh-link>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-filament-widgets::widget>
