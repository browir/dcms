<?php

namespace App\Filament\Admin\Resources\Agendas;

use App\Enum\JenisSurat;
use App\Models\Agenda;
use App\Services\AgendaAnnouncer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;

/** Aksi kegiatan yang dipakai di halaman detail, edit, dan tabel. */
class AgendaActions
{
    /** Ambil nomor surat pemberitahuan (sekali; nomor tidak bisa dibatalkan). */
    public static function issueNotice(): Action
    {
        return Action::make('buatSurat')
            ->label('Buat Surat Pemberitahuan')
            ->icon('heroicon-o-document-plus')
            ->color('gray')
            ->visible(fn (Agenda $record): bool => ! $record->letter_number_id && (auth()->user()?->can('announce', $record) ?? false))
            ->modalHeading('Buat Surat Pemberitahuan')
            ->modalDescription('Nomor surat diambil dari register Nomor Surat saat disimpan dan tidak bisa dibatalkan.')
            ->modalSubmitActionLabel('Terbitkan Nomor & Simpan')
            ->schema([
                Select::make('jenis_surat')
                    ->label('Jenis surat')
                    ->options(JenisSurat::options())
                    ->default(JenisSurat::EDARAN_PENGUMUMAN->value)
                    ->native(false)
                    ->searchable()
                    ->required(),
                Textarea::make('notice_body')
                    ->label('Isi surat')
                    ->helperText('Boleh diubah. Kop, nomor, perihal, dan tanda tangan ditambahkan otomatis.')
                    ->rows(12)
                    ->default(fn (Agenda $record): string => $record->notice_body ?: AgendaAnnouncer::defaultNoticeBody($record))
                    ->required(),
            ])
            ->action(function (Agenda $record, array $data): void {
                $record->forceFill(['notice_body' => $data['notice_body']])->save();
                $letter = app(AgendaAnnouncer::class)->issueNotice($record, JenisSurat::from($data['jenis_surat']), auth()->user());

                Notification::make()->success()->title('Surat pemberitahuan dibuat')->body($letter->nomor)->send();
            });
    }

    public static function printNotice(): Action
    {
        return Action::make('cetakSurat')
            ->label('Cetak Surat')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->visible(fn (Agenda $record): bool => (bool) $record->letter_number_id)
            ->url(fn (Agenda $record): string => route('kegiatan.surat', $record))
            ->openUrlInNewTab();
    }

    /** Umumkan di aplikasi (notifikasi + API integrasi) dan/atau ke grup WhatsApp. */
    public static function announce(): Action
    {
        return Action::make('umumkan')
            ->label(fn (Agenda $record): string => $record->isAnnounced() ? 'Umumkan Ulang' : 'Umumkan')
            ->icon('heroicon-o-megaphone')
            ->color('primary')
            ->visible(fn (Agenda $record): bool => $record->status !== 'cancelled' && (auth()->user()?->can('announce', $record) ?? false))
            ->modalHeading('Umumkan Kegiatan')
            ->modalSubmitActionLabel('Umumkan')
            ->schema(fn (Agenda $record): array => [
                Text::make(self::audienceSummary($record)),
                Toggle::make('in_app')
                    ->label('Umumkan di aplikasi (notifikasi + aplikasi terintegrasi)')
                    ->default(true),
                Toggle::make('whatsapp')
                    ->label('Kirim ke grup WhatsApp')
                    ->helperText(($group = app(AgendaAnnouncer::class)->whatsAppGroup($record))
                        ? "Grup tujuan: {$group}"
                        : 'Grup WhatsApp belum diatur untuk unit/perusahaan ini — atur di Data Master atau N8N_WHATSAPP_GROUP.')
                    ->default(filled($group)),
            ])
            ->action(fn (Agenda $record, array $data) => self::runAnnouncement($record, (bool) $data['in_app'], (bool) $data['whatsapp']));
    }

    /** Jalankan pengumuman dan tampilkan hasilnya ke user. */
    public static function runAnnouncement(Agenda $record, bool $inApp, bool $whatsapp): void
    {
        $announcer = app(AgendaAnnouncer::class);
        $lines = [];

        if ($inApp) {
            $lines[] = 'Notifikasi aplikasi terkirim ke '.$announcer->announceInApp($record).' karyawan.';
        }

        $waFailed = false;

        if ($whatsapp) {
            $result = $announcer->sendToWhatsAppGroup($record);
            $waFailed = ! $result['sent'];
            $lines[] = $result['sent'] ? "Pesan WhatsApp terkirim ke grup {$result['group']}." : 'WhatsApp gagal: '.$result['reason'];
        }

        if ($lines === []) {
            return;
        }

        Notification::make()
            ->title('Pengumuman kegiatan')
            ->body(implode(' ', $lines))
            ->{$waFailed ? 'warning' : 'success'}()
            ->send();
    }

    private static function audienceSummary(Agenda $record): string
    {
        $count = app(AgendaAnnouncer::class)->recipients($record)->count();

        return 'Jangkauan: '.(Agenda::audienceOptions()[$record->audience] ?? $record->audience)." ({$count} karyawan aktif).";
    }
}
