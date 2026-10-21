<x-filament-widgets::widget>
    <div class="dsh-grid dsh-grid-2-1">
        {{-- Agenda rapat & kegiatan unit 7 hari --}}
        <section class="dsh-card" aria-labelledby="dsh-agenda-title">
            <header class="dsh-card-head">
                <span class="dsh-card-icon dsh-tone-meeting"><x-filament::icon icon="heroicon-o-calendar-days" class="dsh-icon" /></span>
                <div class="dsh-card-heading">
                    <h3 id="dsh-agenda-title" class="dsh-card-title">Agenda Rapat & Kegiatan</h3>
                    <p class="dsh-card-sub">Hari ini dan 7 hari ke depan</p>
                </div>
                <a href="{{ route('filament.admin.resources.kegiatan.index') }}" wire:navigate class="dsh-link">Kegiatan</a>
                <a href="{{ route('filament.admin.resources.meetings.index') }}" wire:navigate class="dsh-link">Rapat</a>
            </header>

            @forelse ($agenda as $day => $items)
                <div class="dsh-day">
                    <div class="dsh-day-label">{{ $day }}</div>
                    <ul class="dsh-list">
                        @foreach ($items as $item)
                            <li>
                                <a href="{{ $item['url'] }}" wire:navigate class="dsh-agenda-item dsh-state-{{ $item['state'] }}">
                                    <span class="dsh-agenda-time">{{ $item['time'] }}</span>
                                    <span class="dsh-agenda-body">
                                        <span class="dsh-item-title">
                                            @if ($item['kind'] === 'kegiatan')
                                                <span class="dsh-kind">Kegiatan</span>
                                            @endif
                                            {{ $item['title'] }}
                                        </span>
                                        <span class="dsh-item-meta">
                                            <x-filament::icon icon="heroicon-m-map-pin" class="dsh-meta-icon" />{{ $item['location'] }}
                                            <span class="dsh-sep">·</span>
                                            <x-filament::icon :icon="$item['kind'] === 'kegiatan' ? 'heroicon-m-user-group' : 'heroicon-m-users'" class="dsh-meta-icon" />{{ $item['meta'] }}
                                        </span>
                                    </span>
                                    <span class="dsh-chip dsh-chip-{{ $item['state'] }}">
                                        {{ ['ongoing' => 'Berlangsung', 'done' => 'Selesai', 'scheduled' => 'Terjadwal'][$item['state']] }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="dsh-empty">
                    <x-filament::icon icon="heroicon-o-calendar" class="dsh-empty-icon" />
                    <p>Tidak ada rapat dalam 7 hari ke depan.</p>
                </div>
            @endforelse
        </section>

        {{-- Status ruangan hari ini --}}
        <section id="dsh-rooms" class="dsh-card" aria-labelledby="dsh-rooms-title">
            <header class="dsh-card-head">
                <span class="dsh-card-icon dsh-tone-room"><x-filament::icon icon="heroicon-o-building-office-2" class="dsh-icon" /></span>
                <div class="dsh-card-heading">
                    <h3 id="dsh-rooms-title" class="dsh-card-title">Ruangan Rapat</h3>
                    <p class="dsh-card-sub">Pemakaian hari ini, {{ $dayStartHour }}.00–{{ $dayStartHour + $hourSpan }}.00</p>
                </div>
                @if ($roomsUrl)
                    <a href="{{ $roomsUrl }}" wire:navigate class="dsh-link">Kelola</a>
                @endif
            </header>

            @forelse ($rooms as $room)
                <div class="dsh-room">
                    <div class="dsh-room-head">
                        <span class="dsh-item-title">{{ $room['name'] }}</span>
                        <span @class(['dsh-chip', 'dsh-chip-busy' => $room['inUse'], 'dsh-chip-free' => ! $room['inUse']])>
                            {{ $room['inUse'] ? 'Dipakai' : 'Tersedia' }}
                        </span>
                    </div>
                    <div class="dsh-item-meta">
                        @if ($room['capacity'])
                            <x-filament::icon icon="heroicon-m-users" class="dsh-meta-icon" />{{ $room['capacity'] }} orang
                            <span class="dsh-sep">·</span>
                        @endif
                        {{ $room['todayCount'] }} agenda hari ini
                    </div>
                    <div class="dsh-timeline" role="img" aria-label="Jadwal {{ $room['name'] }} hari ini">
                        @foreach ($room['blocks'] as $block)
                            <span @class(['dsh-block', 'dsh-block-past' => $block['past']])
                                  style="left: {{ $block['left'] }}%; width: {{ $block['width'] }}%"
                                  title="{{ $block['label'] }}"></span>
                        @endforeach
                        @if ($nowPosition !== null)
                            <span class="dsh-now" style="left: {{ $nowPosition }}%" title="Sekarang {{ now()->format('H:i') }}"></span>
                        @endif
                    </div>
                    <div class="dsh-room-status">{{ $room['status'] }}</div>
                </div>
            @empty
                <div class="dsh-empty">
                    <x-filament::icon icon="heroicon-o-building-office" class="dsh-empty-icon" />
                    <p>Belum ada ruangan rapat terdaftar.</p>
                </div>
            @endforelse

            @if ($rooms->isNotEmpty())
                <div class="dsh-hours" aria-hidden="true">
                    @foreach ($hourMarks as $hour)
                        <span style="left: {{ ($hour - $dayStartHour) / $hourSpan * 100 }}%">{{ sprintf('%02d', $hour) }}</span>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament-widgets::widget>
