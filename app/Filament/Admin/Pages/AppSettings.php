<?php

namespace App\Filament\Admin\Pages;

use App\Support\Branding;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
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

    public function previewUrl(): string
    {
        return Branding::previewUrl();
    }

    public function hasCustomLogo(): bool
    {
        return Branding::hasCustomLogo();
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
            ->modalDescription('Logo dipakai di favicon, halaman login, email undangan, notifikasi, dan ikon PWA. Header PDF notulensi memakai logo terpisah dan tidak ikut berubah. Logo tersimpan di storage sehingga tidak hilang saat deploy.')
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
                    ->helperText('Format PNG dengan latar transparan sangat disarankan. Maks. 4 MB. Gambar dinormalkan ke PNG (maks. 1024px) secara otomatis.'),
            ])
            ->action(function (array $data): void {
                $relPath = is_array($data['logo'] ?? null) ? reset($data['logo']) : ($data['logo'] ?? null);

                if (! $relPath) {
                    Notification::make()->danger()->title('Tidak ada file yang diunggah')->send();

                    return;
                }

                $localDisk = Storage::disk('local');
                $source = $localDisk->path((string) $relPath);

                try {
                    if (Branding::storeUploadedLogo($source)) {
                        $this->dispatch('logo-updated', version: (string) Branding::logoVersion());

                        Notification::make()
                            ->success()
                            ->title('Logo berhasil diperbarui')
                            ->body('Logo baru langsung dipakai di seluruh aplikasi dan tetap tersimpan setelah deploy.')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Gagal memproses gambar')
                            ->body('Pastikan berkas berupa PNG, JPG, atau WEBP yang valid dan folder storage dapat ditulis.')
                            ->send();
                    }
                } finally {
                    $localDisk->delete((string) $relPath);
                }
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
                Branding::removeCustomLogo();

                $this->dispatch('logo-updated', version: (string) Branding::logoVersion());

                Notification::make()->success()->title('Logo dikembalikan ke bawaan')->send();
            });
    }
}
