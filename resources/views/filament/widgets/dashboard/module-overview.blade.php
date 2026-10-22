<x-filament-widgets::widget>
    <section class="dsh-section" aria-labelledby="dsh-modules-title">
        <header class="dsh-section-head">
            <div>
                <h2 id="dsh-modules-title" class="dsh-section-title">Ringkasan Layanan</h2>
                <p class="dsh-section-sub">Rapat, notulensi, dokumen, penomoran surat, dan ruangan dalam satu tampilan.</p>
            </div>
            <div class="dsh-actions">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['url'] }}" wire:navigate @class(['dsh-btn', 'dsh-btn-primary' => $action['primary']])>
                        <x-filament::icon :icon="$action['icon']" class="dsh-btn-icon" />
                        <span>{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </header>

        <div class="dsh-modules">
            @foreach ($modules as $module)
                <x-dsh-link :href="$module['url']"
                   class="dsh-module dsh-tone-{{ $module['key'] }}">
                    <div class="dsh-module-top">
                        <span class="dsh-module-icon">
                            <x-filament::icon :icon="$module['icon']" class="dsh-icon" />
                        </span>
                        <span class="dsh-module-label">{{ $module['label'] }}</span>
                    </div>
                    <div class="dsh-module-value">
                        {{ number_format($module['value'], 0, ',', '.') }}
                        @if ($module['alert'])
                            <span class="dsh-dot" aria-hidden="true"></span>
                        @endif
                    </div>
                    <div class="dsh-module-unit">{{ $module['unit'] }}</div>
                    <div class="dsh-module-detail" title="{{ $module['detail'] }}">{{ $module['detail'] }}</div>
                </x-dsh-link>
            @endforeach
        </div>
    </section>
</x-filament-widgets::widget>
