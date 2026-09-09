<?php

namespace App\Filament\Admin\Pages;

use App\Support\Branding;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AppSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 90;

    protected static ?string $navigationLabel = 'Logo Aplikasi';

    protected static ?string $title = 'Pengaturan Aplikasi';

    protected string $view = 'filament.admin.pages.app-settings';

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public function logoVersion(): int
    {
        return Branding::logoVersion();
    }

    public function hasCustomLogo(): bool
    {
        return Branding::hasCustomLogo();
    }

    /**
     * URL pratinjau logo — berkas kecil (maks. 240px) yang di-cache di disk
     * "public" supaya halaman tidak perlu mengunduh & men-decode logo asli.
     * Tidak menyentuh database / cache Laravel.
     */
    public function previewUrl(): string
    {
        $disk = Storage::disk('public');
        $source = Branding::logoPath();

        if (! is_file($source)) {
            return Branding::logoUrl();
        }

        $previewAbs = storage_path('app/public/'.Branding::CUSTOM_LOGO_PREVIEW);
        $fresh = is_file($previewAbs) && filemtime($previewAbs) >= filemtime($source);

        if (! $fresh) {
            $this->generatePreview($source, $previewAbs);
        }

        return is_file($previewAbs)
            ? asset('storage/'.Branding::CUSTOM_LOGO_PREVIEW).'?v='.@filemtime($previewAbs)
            : Branding::logoUrl();
    }

    protected function generatePreview(string $source, string $previewAbs): void
    {
        try {
            if (! is_dir(dirname($previewAbs))) {
                @mkdir(dirname($previewAbs), 0755, true);
            }

            $image = $this->readImage($source);

            if (! $image) {
                return;
            }

            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            $w = imagesx($image);
            $h = imagesy($image);
            $max = 240;

            if ($w > $max || $h > $max) {
                $r = min($max / $w, $max / $h);
                $nw = (int) round($w * $r);
                $nh = (int) round($h * $r);
                $thumb = imagecreatetruecolor($nw, $nh);
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                imagecopyresampled($thumb, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($image);
                $image = $thumb;
            }

            imagepng($image, $previewAbs);
            imagedestroy($image);
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat pratinjau logo: '.$e->getMessage());
        }
    }

    protected function getHeaderActions(): array
    {
        return array_values(array_filter([
            $this->uploadLogoAction(),
            Branding::hasCustomLogo() ? $this->resetLogoAction() : null,
        ]));
    }

    public function uploadLogoAction(): Action
    {
        return Action::make('uploadLogo')
            ->label('Unggah / Ganti Logo')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalHeading('Ganti Logo Aplikasi')
            ->modalDescription('Logo akan dipakai di favicon, halaman login, email undangan, notifikasi, dan ikon PWA. Header PDF notulensi memakai logo terpisah dan tidak ikut berubah. Logo tersimpan di storage sehingga tidak hilang saat deploy.')
            ->modalSubmitActionLabel('Simpan & Ganti Logo')
            ->form([
                FileUpload::make('logo')
                    ->label('File Logo Baru')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(4096)
                    ->disk('local')
                    ->directory('logo-uploads')
                    ->visibility('private')
                    ->required()
                    ->helperText('Format PNG dengan latar transparan sangat disarankan. Maks. 4 MB. Gambar akan dinormalkan ke PNG secara otomatis.'),
            ])
            ->action(function (array $data): void {
                $relPath = is_array($data['logo'] ?? null) ? reset($data['logo']) : ($data['logo'] ?? null);

                if (! $relPath) {
                    Notification::make()->danger()->title('Tidak ada file yang diunggah')->send();

                    return;
                }

                $this->applyLogo((string) $relPath);
            });
    }

    public function resetLogoAction(): Action
    {
        return Action::make('resetLogo')
            ->label('Kembalikan Logo Bawaan')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Kembalikan ke logo bawaan?')
            ->modalDescription('Logo kustom akan dihapus dan aplikasi kembali memakai logo bawaan.')
            ->action(function (): void {
                $disk = Storage::disk('public');
                $disk->delete([
                    Branding::CUSTOM_LOGO,
                    Branding::CUSTOM_LOGO_PREVIOUS,
                    Branding::CUSTOM_LOGO_PREVIEW,
                ]);

                $this->dispatch('logo-updated', version: (string) Branding::logoVersion());

                Notification::make()->success()->title('Logo dikembalikan ke bawaan')->send();
            });
    }

    /**
     * Proses berkas yang diunggah menjadi PNG lalu simpan sebagai logo kustom
     * di storage/app/public/branding/logo.png (persist saat deploy).
     */
    protected function applyLogo(string $relPath): void
    {
        $localDisk = Storage::disk('local');
        $publicDisk = Storage::disk('public');
        $source = $localDisk->path($relPath);

        try {
            if (! is_file($source)) {
                throw new \RuntimeException('Berkas unggahan tidak ditemukan.');
            }

            $image = $this->readImage($source);

            if (! $image) {
                throw new \RuntimeException('Format gambar tidak didukung. Gunakan PNG, JPG, atau WEBP.');
            }

            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            // Perkecil bila terlalu besar (sisi terpanjang maks. 1024px).
            $width = imagesx($image);
            $height = imagesy($image);
            $max = 1024;

            if ($width > $max || $height > $max) {
                $ratio = min($max / $width, $max / $height);
                $newWidth = (int) round($width * $ratio);
                $newHeight = (int) round($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            $targetAbs = storage_path('app/public/'.Branding::CUSTOM_LOGO);

            if (! is_dir(dirname($targetAbs))) {
                @mkdir(dirname($targetAbs), 0755, true);
            }

            // Simpan cadangan logo kustom sebelumnya (bila ada).
            if (is_file($targetAbs)) {
                @copy($targetAbs, storage_path('app/public/'.Branding::CUSTOM_LOGO_PREVIOUS));
            }

            if (! imagepng($image, $targetAbs)) {
                throw new \RuntimeException('Gagal menulis berkas logo. Periksa izin folder storage/app/public/branding.');
            }

            imagedestroy($image);

            clearstatcache(true, $targetAbs);

            // Paksa pratinjau dibuat ulang dari logo baru.
            $publicDisk->delete(Branding::CUSTOM_LOGO_PREVIEW);

            // Perbarui pratinjau & favicon di layar tanpa reload.
            $this->dispatch('logo-updated', version: (string) Branding::logoVersion());

            Notification::make()
                ->success()
                ->title('Logo berhasil diperbarui')
                ->body('Logo baru langsung dipakai di seluruh aplikasi dan tetap tersimpan setelah deploy.')
                ->send();
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui logo aplikasi: '.$e->getMessage(), ['exception' => $e]);

            Notification::make()
                ->danger()
                ->title('Gagal memperbarui logo')
                ->body($e->getMessage())
                ->send();
        } finally {
            $localDisk->delete($relPath);
        }
    }

    /**
     * @return \GdImage|false
     */
    protected function readImage(string $source)
    {
        $mime = @getimagesize($source)['mime'] ?? null;

        return match ($mime) {
            'image/png' => @imagecreatefrompng($source),
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
    }
}
