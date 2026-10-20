<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

/**
 * Membuat public/css/dcms-dark.css dari CSS kustom yang ditulis untuk mode terang.
 *
 * CSS kustom DCMS (blok <style> di view & AdminPanelProvider, plus atribut style="")
 * memakai warna hard-coded. Command ini mencari latar terang, border terang, dan teks
 * gelap, lalu menulis pasangan aturan `html.dark ...` dengan warna gelap yang setara.
 * Jalankan ulang setiap kali CSS kustom mode terang diubah:
 *
 *   php artisan theme:build-dark
 *
 * Kasus yang tidak bisa dipetakan otomatis (gradien halaman, topbar, dll.) ada di
 * resources/views/filament/theme-dark.blade.php.
 */
class BuildDarkThemeCss extends Command
{
    protected $signature = 'theme:build-dark {--output=public/css/dcms-dark.css}';

    protected $description = 'Membuat stylesheet dark mode dari CSS kustom mode terang.';

    /** Sumber CSS yang dipindai (relatif ke base path). */
    private const SOURCES = [
        'app/Providers/Filament/AdminPanelProvider.php',
        'app/Providers/Filament/ReviewerPanelProvider.php',
        'resources/views/filament',
        'resources/views/livewire',
        'resources/views/vendor/filament-panels',
        'resources/views/vendor/filament-widgets',
    ];

    /** Dilewati: arsip lama dan file override dark itu sendiri. */
    private const EXCLUDE = [
        'resources/views/filament/backup',
        'resources/views/filament/theme-dark.blade.php',
    ];

    private const SURFACE = '#111a2e';

    private const SOFT = '#17233a';

    private const BORDER = '#26344d';

    private const BORDER_STRONG = '#334155';

    /** Latar terang → latar gelap. */
    private array $backgrounds = [];

    /** Border terang → border gelap. */
    private array $borders = [];

    /** Teks gelap → teks terang. */
    private array $texts = [];

    public function handle(): int
    {
        $this->buildPalette();

        $rules = [];
        $inline = [];

        foreach ($this->sourceFiles() as $path) {
            $content = File::get($path);

            foreach ($this->styleBlocks($content) as $css) {
                $this->collectRules($this->parse($this->clean($css)), [], $rules);
            }

            $this->collectInline($content, $inline);
        }

        $output = $this->render($rules, $inline);
        $target = base_path($this->option('output'));
        File::ensureDirectoryExists(dirname($target));
        File::put($target, $output);

        $this->info(sprintf('✅ %s ditulis: %d aturan CSS, %d aturan inline.', $this->option('output'), count($rules), count($inline)));

        return self::SUCCESS;
    }

    private function buildPalette(): void
    {
        $map = function (array $colors, string $to, array &$into) {
            foreach ($colors as $c) {
                $into[$c] = $to;
            }
        };

        // Permukaan
        $map(['#ffffff', 'white'], self::SURFACE, $this->backgrounds);
        $map(['#fafafa', '#fafcff', '#f8faff', '#f8fafc', '#f9fafb', '#f1f5f9', '#f3f4f6', '#f0f4f8', '#f4f6fa', '#f5f7fa', '#f6f8fb', '#f7f9fc', '#eef2f7', '#edf2f7', '#e2e8f0', '#e5e7eb', '#f4f4f5', '#f5f5f5'], self::SOFT, $this->backgrounds);
        // Latar berwarna lembut (chip, badge, callout)
        $map(['#eff6ff', '#dbeafe', '#e0f2fe', '#bae6fd', '#bfdbfe', '#e0e7ff', '#eef2ff', '#f0f9ff', '#ebf5ff', '#f0f7ff', '#e8f0fe'], '#172a4d', $this->backgrounds);
        $map(['#ecfdf5', '#d1fae5', '#dcfce7', '#f0fdf4', '#bbf7d0', '#a7f3d0'], '#0f2e25', $this->backgrounds);
        $map(['#fef2f2', '#fee2e2', '#fff1f2', '#ffe4e6', '#fecaca', '#fecdd3'], '#3a1a1f', $this->backgrounds);
        $map(['#fffbeb', '#fef3c7', '#fef9c3', '#fff7ed', '#ffedd5', '#fde68a', '#fefce8'], '#33270f', $this->backgrounds);
        $map(['#f5f3ff', '#ede9fe', '#faf5ff', '#f3e8ff', '#ddd6fe'], '#261d45', $this->backgrounds);
        $map(['#fdf2f8', '#fce7f3'], '#3a1a2e', $this->backgrounds);
        $map(['#ecfeff', '#cffafe', '#f0fdfa', '#ccfbf1'], '#0f2a30', $this->backgrounds);

        $map(['#e2e8f0', '#e5e7eb', '#f1f5f9', '#edf2f7', '#eef2f7', '#e8edf3', '#dbe3ef', '#e0e7ff', '#f3f4f6', '#e4e4e7', '#f4f4f5', '#dbeafe', '#bfdbfe'], self::BORDER, $this->borders);
        $map(['#cbd5e1', '#d1d5db', '#d4d4d8', '#94a3b8'], self::BORDER_STRONG, $this->borders);

        $map(['#0f172a', '#111827', '#1e293b', '#1f2937', '#0b1220', '#020617', '#000000', '#000', '#18181b', '#27272a', '#09090b'], '#e5e7eb', $this->texts);
        $map(['#334155', '#374151', '#475569', '#4b5563', '#3f3f46', '#52525b'], '#cbd5e1', $this->texts);
        $map(['#64748b', '#6b7280', '#71717a'], '#94a3b8', $this->texts);
        $map(['#1e3a8a', '#1e40af', '#1d4ed8', '#2563eb', '#0b2545', '#133b6b', '#1e3a5f', '#0c4a6e', '#075985', '#0369a1'], '#93c5fd', $this->texts);
        $map(['#065f46', '#047857', '#166534', '#15803d', '#064e3b', '#14532d', '#059669'], '#6ee7b7', $this->texts);
        $map(['#991b1b', '#b91c1c', '#9f1239', '#be123c', '#7f1d1d', '#dc2626'], '#fca5a5', $this->texts);
        $map(['#92400e', '#b45309', '#78350f', '#a16207', '#854d0e', '#9a3412', '#c2410c'], '#fcd34d', $this->texts);
        $map(['#5b21b6', '#6d28d9', '#4c1d95', '#7c3aed'], '#c4b5fd', $this->texts);
        $map(['#0e7490', '#155e75', '#0f766e', '#115e59', '#134e4a'], '#67e8f9', $this->texts);
        $map(['#9d174d', '#be185d', '#831843'], '#f9a8d4', $this->texts);
    }

    /** @return iterable<string> */
    private function sourceFiles(): iterable
    {
        foreach (self::SOURCES as $source) {
            $path = base_path($source);

            if (is_file($path)) {
                yield $path;

                continue;
            }

            if (! is_dir($path)) {
                continue;
            }

            foreach ((new Finder)->files()->in($path)->name('*.php')->sortByName() as $file) {
                $relative = str_replace('\\', '/', substr($file->getRealPath(), strlen(base_path()) + 1));

                if (collect(self::EXCLUDE)->contains(fn ($ex) => str_starts_with($relative, $ex))) {
                    continue;
                }

                yield $file->getRealPath();
            }
        }
    }

    /** @return array<int, string> */
    private function styleBlocks(string $content): array
    {
        preg_match_all('#<style[^>]*>(.*?)</style>#si', $content, $m);

        return $m[1];
    }

    private function clean(string $css): string
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $css = preg_replace('#\{\{.*?\}\}#s', '__BLADE__', $css);
        $css = preg_replace('#@(php|if|foreach|endif|endforeach|else)\b[^\n]*#', '', $css);

        return $css;
    }

    /**
     * Parser CSS sederhana: menghasilkan daftar [prelude, body|children].
     *
     * @return array<int, array{0: string, 1: string|array}>
     */
    private function parse(string $css): array
    {
        $nodes = [];
        $len = strlen($css);
        $i = 0;
        $prelude = '';

        while ($i < $len) {
            $ch = $css[$i];

            if ($ch === '{') {
                $depth = 1;
                $start = ++$i;

                while ($i < $len && $depth > 0) {
                    $depth += $css[$i] === '{' ? 1 : ($css[$i] === '}' ? -1 : 0);
                    $i++;
                }

                $body = substr($css, $start, $i - $start - 1);
                $prelude = trim($prelude);

                if (preg_match('#^@(media|supports|container)#i', $prelude)) {
                    $nodes[] = [$prelude, $this->parse($body)];
                } elseif ($prelude !== '' && $prelude[0] !== '@') {
                    $nodes[] = [$prelude, $body];
                }

                $prelude = '';

                continue;
            }

            if ($ch === ';' && str_starts_with(trim($prelude), '@')) {
                $prelude = '';
                $i++;

                continue;
            }

            if ($ch === '}') {
                $prelude = '';
                $i++;

                continue;
            }

            $prelude .= $ch;
            $i++;
        }

        return $nodes;
    }

    private function collectRules(array $nodes, array $wrappers, array &$rules): void
    {
        foreach ($nodes as [$prelude, $body]) {
            if (is_array($body)) {
                $this->collectRules($body, [...$wrappers, $prelude], $rules);

                continue;
            }

            $declarations = $this->darkDeclarations($body);

            if ($declarations === []) {
                continue;
            }

            $selector = $this->prefixSelector($prelude);

            if ($selector === null) {
                continue;
            }

            $key = implode(' | ', $wrappers).'#'.$selector;
            $rules[$key] = [
                'wrappers' => $wrappers,
                'selector' => $selector,
                'declarations' => array_merge($rules[$key]['declarations'] ?? [], $declarations),
            ];
        }
    }

    /** @return array<string, string> property => value */
    private function darkDeclarations(string $body): array
    {
        $result = [];

        foreach (preg_split('/;(?![^(]*\))/', $body) as $declaration) {
            if (! str_contains($declaration, ':') || str_contains($declaration, '__BLADE__')) {
                continue;
            }

            [$prop, $value] = array_map('trim', explode(':', $declaration, 2));
            $prop = strtolower($prop);
            $value = trim(preg_replace('/!important/i', '', $value));

            if ($prop === '' || $value === '' || str_starts_with($prop, '--')) {
                continue;
            }

            $mapped = $this->mapValue($prop, $value);

            if ($mapped !== null) {
                $result[$prop] = $mapped;
            }
        }

        return $result;
    }

    private function mapValue(string $prop, string $value): ?string
    {
        $palette = match (true) {
            in_array($prop, ['background', 'background-color', 'background-image'], true) => $this->backgrounds,
            str_starts_with($prop, 'border') || $prop === 'outline' || $prop === 'outline-color' => $this->borders,
            in_array($prop, ['color', '-webkit-text-fill-color', 'caret-color'], true) => $this->texts,
            default => null,
        };

        if ($palette === null) {
            return null;
        }

        // Gambar / url tidak disentuh, gradien dipetakan per warna.
        if (str_contains($value, 'url(')) {
            return null;
        }

        $kind = match (true) {
            $palette === $this->backgrounds => 'background',
            $palette === $this->borders => 'border',
            default => 'text',
        };

        $changed = false;

        // Shade palet Filament (var(--color-50), var(--primary-700), ...): tint terang → transparan,
        // shade gelap untuk teks → shade terang.
        $value = preg_replace_callback('/var\(--([a-z]+)-(50|100|200|300|600|700|800|900|950)\)/', function ($m) use ($kind, &$changed) {
            [$all, $name, $shade] = $m;
            $base = "var(--{$name}-500)";

            $replacement = match ($kind) {
                'background' => match ($shade) {
                    '50', '100' => "color-mix(in srgb, {$base} 14%, transparent)",
                    '200' => "color-mix(in srgb, {$base} 24%, transparent)",
                    default => null,
                },
                'border' => in_array($shade, ['50', '100', '200', '300'], true) ? "color-mix(in srgb, {$base} 40%, transparent)" : null,
                default => in_array($shade, ['600', '700', '800', '900', '950'], true) ? "var(--{$name}-300)" : null,
            };

            if ($replacement === null) {
                return $all;
            }

            $changed = true;

            return $replacement;
        }, $value);

        // Putih hampir opak (panel melayang, kartu kaca) → permukaan gelap. Putih transparan
        // tipis (efek kaca di topbar biru) sengaja dibiarkan.
        if ($kind === 'background') {
            $value = preg_replace_callback('/rgba\(\s*255\s*,\s*255\s*,\s*255\s*,\s*(0?\.\d+|1)\s*\)/', function ($m) use (&$changed) {
                if ((float) $m[1] < 0.8) {
                    return $m[0];
                }

                $changed = true;

                return 'rgba(17, 26, 46, '.$m[1].')';
            }, $value);
        }

        $mapped = preg_replace_callback('/#[0-9a-fA-F]{3,8}\b|\bwhite\b|\bblack\b/', function ($m) use ($palette, $kind, &$changed) {
            $color = $this->normalize($m[0]);
            $dark = $palette[$color] ?? $this->derive($color, $kind);

            if ($dark !== null) {
                $changed = true;

                return $dark;
            }

            return $m[0];
        }, $value);

        return $changed ? $mapped : null;
    }

    /**
     * Fallback untuk warna yang tidak ada di palet: dipetakan dari kecerahan & hue-nya,
     * sehingga varian off-white / tinted baru tetap ikut gelap.
     */
    private function derive(string $hex, string $kind): ?string
    {
        if (! preg_match('/^#([0-9a-f]{6})([0-9a-f]{2})?$/', $hex, $m)) {
            return null;
        }

        [$r, $g, $b] = array_map(fn ($i) => hexdec(substr($m[1], $i, 2)) / 255, [0, 2, 4]);
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;
        $s = $d == 0 ? 0 : $d / (1 - abs(2 * $l - 1));
        $h = 0;

        if ($d != 0) {
            $h = match ($max) {
                $r => 60 * fmod(($g - $b) / $d, 6),
                $g => 60 * (($b - $r) / $d + 2),
                default => 60 * (($r - $g) / $d + 4),
            };
        }

        $h = (int) round($h < 0 ? $h + 360 : $h);
        $alpha = isset($m[2]) ? round(hexdec($m[2]) / 255, 2) : null;
        $wrap = fn (string $hsl) => $alpha === null ? $hsl : str_replace(')', " / {$alpha})", $hsl);

        return match ($kind) {
            'background' => match (true) {
                $l < 0.88 => null,
                $s < 0.3 => $wrap($l >= 0.995 ? 'hsl(222 45% 12%)' : 'hsl(220 40% 16%)'),
                default => $wrap("hsl({$h} 45% 17%)"),
            },
            'border' => match (true) {
                $l < 0.78 => null,
                $s < 0.3 => $wrap('hsl(218 33% 23%)'),
                default => $wrap("hsl({$h} 35% 30%)"),
            },
            default => match (true) {
                $l <= 0.3 && $s < 0.3 => $wrap('hsl(220 13% 91%)'),
                $l <= 0.3 => $wrap("hsl({$h} 85% 80%)"),
                $l <= 0.5 && $s < 0.3 => $wrap('hsl(215 20% 65%)'),
                default => null,
            },
        };
    }

    private function normalize(string $color): string
    {
        $color = strtolower($color);

        if ($color === 'black') {
            return '#000000';
        }

        if (preg_match('/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $color, $m)) {
            return "#{$m[1]}{$m[1]}{$m[2]}{$m[2]}{$m[3]}{$m[3]}";
        }

        return $color;
    }

    private function prefixSelector(string $selectorList): ?string
    {
        $parts = [];

        foreach (preg_split('/,(?![^(]*\))/', $selectorList) as $selector) {
            $selector = trim(preg_replace('/\s+/', ' ', $selector));

            if ($selector === '' || str_contains($selector, '__BLADE__') || preg_match('/^(from|to|\d+%)$/', $selector)) {
                continue;
            }

            if (str_contains($selector, '.dark')) {
                continue;
            }

            $parts[] = match (true) {
                str_starts_with($selector, ':root') => 'html.dark'.substr($selector, 5),
                preg_match('/^html\b/', $selector) === 1 => 'html.dark'.substr($selector, 4),
                default => 'html.dark '.$selector,
            };
        }

        return $parts === [] ? null : implode(",\n", $parts);
    }

    private function collectInline(string $content, array &$inline): void
    {
        preg_match_all('/style="([^"]*)"/i', $content, $m);

        foreach ($m[1] as $style) {
            foreach (preg_split('/;(?![^(]*\))/', $style) as $declaration) {
                $declaration = trim($declaration);

                if (! str_contains($declaration, ':') || str_contains($declaration, '{{')) {
                    continue;
                }

                [$prop, $value] = array_map('trim', explode(':', $declaration, 2));
                $mapped = $this->mapValue(strtolower($prop), trim(preg_replace('/!important/i', '', $value)));

                if ($mapped !== null) {
                    $inline[$declaration] = [strtolower($prop), $mapped];
                }
            }
        }
    }

    private function render(array $rules, array $inline): string
    {
        $out = "/*\n * DIBUAT OTOMATIS oleh `php artisan theme:build-dark` — jangan diedit manual.\n"
            ." * Override manual ada di resources/views/filament/theme-dark.blade.php.\n */\n\n";

        $grouped = collect($rules)->groupBy(fn ($r) => implode(' | ', $r['wrappers']));

        foreach ($grouped as $wrapperKey => $group) {
            $wrappers = $group->first()['wrappers'];
            $indent = str_repeat('    ', count($wrappers));

            foreach ($wrappers as $depth => $wrapper) {
                $out .= str_repeat('    ', $depth)."{$wrapper} {\n";
            }

            foreach ($group as $rule) {
                $selector = str_replace("\n", "\n{$indent}", $rule['selector']);
                $out .= "{$indent}{$selector} {\n";

                foreach ($rule['declarations'] as $prop => $value) {
                    $out .= "{$indent}    {$prop}: {$value} !important;\n";
                }

                $out .= "{$indent}}\n";
            }

            for ($depth = count($wrappers) - 1; $depth >= 0; $depth--) {
                $out .= str_repeat('    ', $depth)."}\n";
            }

            $out .= "\n";
        }

        $out .= "/* ── Atribut style=\"\" ── */\n";

        foreach ($inline as $declaration => [$prop, $value]) {
            $needle = str_replace('"', '\\"', $declaration);
            $out .= "html.dark [style*=\"{$needle}\"] { {$prop}: {$value} !important; }\n";
        }

        return $out;
    }
}
