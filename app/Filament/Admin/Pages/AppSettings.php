<?php

namespace App\Filament\Admin\Pages;

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

    /**
     * URL logo saat ini dengan cache-buster berdasarkan waktu modifikasi file.
     */
    public function currentLogoUrl(): string
    {
        return asset('images/logo.png').'?v='.$this->logoVersion();
    }

    /**
     * Token cache-buster: waktu modifikasi berkas logo.
     */
    public function logoVersion(): int
    {
        $path = public_path('images/logo.png');

        return is_file($path) ? (int) filemtime($path) : 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->uploadLogoAction(),
        ];
    }

    public function uploadLogoAction(): Action
    {
        return Action::make('uploadLogo')
            ->label('Unggah / Ganti Logo')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->modalHeading('Ganti Logo Aplikasi')
            ->modalDescription('Logo akan otomatis dipakai di seluruh aplikasi: favicon, halaman login, email undangan, notifikasi, dan ikon PWA. Header PDF notulensi memakai logo terpisah dan tidak ikut berubah.')
            ->modalSubmitActionLabel('Simpan & Ganti Logo')
            ->form([
                FileUpload::make('logo')
                    ->label('File Logo Baru')
                    ->image()
                    ->imageEditor()
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

    /**
     * Proses file yang diunggah menjadi PNG dan timpa public/images/logo.png.
     */
    protected function applyLogo(string $relPath): void
    {
        $disk = Storage::disk('local');
        $source = $disk->path($relPath);

        try {
            if (! is_file($source)) {
                throw new \RuntimeException('Berkas unggahan tidak ditemukan.');
            }

            $info = @getimagesize($source);
            $mime = $info['mime'] ?? null;

            $image = match ($mime) {
                'image/png' => @imagecreatefrompng($source),
                'image/jpeg' => @imagecreatefromjpeg($source),
                'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
                default => false,
            };

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

            $target = public_path('images/logo.png');

            if (! is_dir(dirname($target))) {
                @mkdir(dirname($target), 0755, true);
            }

            // Simpan cadangan logo lama.
            if (is_file($target)) {
                @copy($target, public_path('images/logo-previous.png'));
            }

            if (! imagepng($image, $target)) {
                throw new \RuntimeException('Gagal menulis berkas logo. Periksa izin folder public/images.');
            }

            imagedestroy($image);

            clearstatcache(true, $target);

            // Perbarui pratinjau & favicon di layar tanpa reload.
            $this->dispatch('logo-updated', version: (string) ($this->logoVersion()));

            Notification::make()
                ->success()
                ->title('Logo berhasil diperbarui')
                ->body('Logo baru langsung dipakai di seluruh aplikasi.')
                ->send();
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui logo aplikasi: '.$e->getMessage(), ['exception' => $e]);

            Notification::make()
                ->danger()
                ->title('Gagal memperbarui logo')
                ->body($e->getMessage())
                ->send();
        } finally {
            $disk->delete($relPath);
        }
    }
}
