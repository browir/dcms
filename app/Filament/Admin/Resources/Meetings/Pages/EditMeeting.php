<?php

namespace App\Filament\Admin\Resources\Meetings\Pages;

use App\Filament\Admin\Resources\Meetings\MeetingResource;
use App\Models\Meeting;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    public function getTitle(): string
    {
        return 'Ubah Rapat';
    }

    public function getSubheading(): ?string
    {
        return 'Perbarui detail rapat, catat notulensi, dan lampirkan dokumentasi. Tandai status "Selesai" agar rekap PDF notulensi dibuat otomatis.';
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('tutorial')
                ->label('Panduan')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Petunjuk Ubah Rapat')
                ->modalWidth('4xl')
                ->modalContent(view('filament.tutorial-modal', ['image' => 'tambah.jpg']))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
            ViewAction::make()
                ->label('Lihat')
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->label('Hapus')
                ->icon('heroicon-o-trash'),
        ];
    }

    /**
     * Setelah data rapat & notulensi tersimpan, baru buat file PDF notulensi.
     * Kegagalan pembuatan PDF TIDAK boleh membatalkan penyimpanan data.
     */
    protected function afterSave(): void
    {
        $record = $this->record->refresh();

        $this->notifyNewActionItemPics($record);

        $mode = $this->data['mode_notulen'] ?? 'template';
        $plainContent = trim(strip_tags((string) $record->content));
        $hasActionItems = ! empty($record->action_items);

        // Hanya generate PDF bila: status Selesai + mode template + notulensi ada isinya
        // (baik dari catatan tambahan maupun dari tabel Action Plan & PIC).
        if ($record->status !== 'completed' || $mode !== 'template' || (! $hasActionItems && $plainContent === '')) {
            return;
        }

        try {
            @ini_set('memory_limit', '512M');
            @set_time_limit(120);

            $pdf = Pdf::loadHTML($this->buildNotulensiHtml($record));
            $filename = 'meetings/notulen_'.$record->id.'_'.time().'.pdf';

            Storage::disk('private')->put($filename, $pdf->output());

            $record->updateQuietly(['file_path' => $filename]);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat PDF notulensi rapat #'.$record->id.': '.$e->getMessage(), [
                'exception' => $e,
            ]);

            Notification::make()
                ->title('Notulensi tersimpan, PDF gagal dibuat')
                ->body('Data rapat dan notulensi sudah tersimpan dengan aman. Pembuatan file PDF notulensi gagal — silakan buka lagi rapat ini lalu simpan ulang. Jika tetap gagal, hubungi admin.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    /**
     * Kirim notifikasi ke user yang baru ditunjuk sebagai PIC action plan.
     *
     * Satu user bisa menjadi PIC di beberapa action item dalam satu rapat yang sama —
     * notifikasi tetap cukup dikirim sekali (dideduplikasi lewat `notified_pic_user_ids`,
     * bukan hanya per-submit, jadi menyimpan ulang rapat tanpa perubahan PIC tidak
     * mengirim notifikasi berulang).
     */
    protected function notifyNewActionItemPics(Meeting $record): void
    {
        $currentPicIds = $record->uniqueActionItemPicUserIds();

        if (empty($currentPicIds)) {
            return;
        }

        $alreadyNotified = is_array($record->notified_pic_user_ids) ? $record->notified_pic_user_ids : [];
        $newPicIds = array_diff($currentPicIds, $alreadyNotified);

        if (empty($newPicIds)) {
            return;
        }

        $users = \App\Models\User::whereIn('id', $newPicIds)->get();

        foreach ($users as $user) {
            try {
                $user->notify(new \App\Notifications\ActionItemPicAssignedNotification($record));
            } catch (\Throwable $e) {
                Log::error('Gagal mengirim notifikasi PIC action plan ke '.$user->email.': '.$e->getMessage());
            }
        }

        $record->updateQuietly([
            'notified_pic_user_ids' => array_values(array_unique(array_merge($alreadyNotified, $currentPicIds))),
        ]);
    }

    /**
     * Bangun HTML notulensi untuk dikonversi ke PDF.
     */
    protected function buildNotulensiHtml(Meeting $record): string
    {
        // Logo khusus header PDF notulensi — sengaja terpisah dari logo aplikasi
        // yang dapat diganti lewat menu Pengaturan Aplikasi.
        $logoBase64 = '';
        $logoPath = \App\Support\Branding::pdfLogoPath();
        if (is_file($logoPath)) {
            try {
                $logoBase64 = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
            } catch (\Throwable $e) {
                $logoBase64 = '';
            }
        }

        $formTitle = $record->title ?? '-';
        $formDocNumber = $record->doc_number ?: '-';
        $formAgenda = $record->agenda ?: '-';
        $formLocation = $record->location ?: '-';

        // Format Tanggal Indonesia
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $dateTime = \Carbon\Carbon::parse($record->date_time);
        $formattedDate = $days[$dateTime->dayOfWeek].', '.$dateTime->day.' '.$months[$dateTime->month].' '.$dateTime->year.' / '.$dateTime->format('H.i').' WITA';

        $notulisName = $record->notulis?->name ?? '-';

        $contentFromEditor = (string) $record->content;
        $actionItemsHtml = $this->buildActionItemsHtml($record);

        // Lampiran (Foto/Dokumentasi) — dibaca dari disk "public", tiap gambar dibungkus try/catch
        $attachmentsHtml = '';
        $attachments = is_array($record->attachments) ? $record->attachments : [];

        $imagesHtml = '';
        foreach ($attachments as $attachment) {
            if (! is_string($attachment) || $attachment === '') {
                continue;
            }

            try {
                if (! Storage::disk('public')->exists($attachment)) {
                    continue;
                }

                $imgData = Storage::disk('public')->get($attachment);
                if ($imgData === null || $imgData === '') {
                    continue;
                }

                $extension = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'bmp' => 'image/bmp',
                    default => 'image/jpeg',
                };

                $base64 = 'data:'.$mime.';base64,'.base64_encode($imgData);

                $imagesHtml .= "<div style='margin-bottom: 50px; page-break-inside: avoid; clear: both;'>";
                $imagesHtml .= "<img src='{$base64}' style='max-width: 90%; max-height: 480px; border: 3px solid #f2f2f2; padding: 5px; background: #fff;'>";
                $imagesHtml .= '</div>';
            } catch (\Throwable $e) {
                Log::warning('Lewati lampiran notulensi yang gagal dibaca ('.$attachment.'): '.$e->getMessage());

                continue;
            }
        }

        if ($imagesHtml !== '') {
            $attachmentsHtml .= "<div style='page-break-before: always;'></div>";
            $attachmentsHtml .= "<div style='margin-top: 20px;'>";
            $attachmentsHtml .= "<h4 style='text-transform: uppercase; font-size: 14px; text-align: center; margin-bottom: 40px; color: #000;'>LAMPIRAN / DOKUMENTASI</h4>";
            $attachmentsHtml .= "<div style='text-align: center;'>".$imagesHtml.'</div></div>';
        }

        return "
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
    <style>
        @page { margin: 160px 50px 80px 50px; }
        header { position: fixed; top: -145px; left: -50px; right: -50px; height: 140px; }
        footer { position: fixed; bottom: -60px; left: 0px; right: 0px; height: 60px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 9px; line-height: 1.3; }

        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; line-height: 1.4; color: #333; }

        .header-content { padding: 30px 50px 0 50px; }
        .logo { height: 75px; width: auto; }
        .doc-no { text-align: right; vertical-align: top; font-weight: bold; font-size: 10px; padding-top: 15px; }

        .title-section { text-align: center; margin-top: 20px; margin-bottom: 25px; }
        .title-section h3 { margin: 0; font-size: 14px; text-transform: uppercase; }
        .title-section h4 { margin: 5px 0; font-size: 12px; text-transform: uppercase; font-weight: bold; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 4px 0; vertical-align: top; }
        .label { width: 120px; font-weight: bold; }
        .colon { width: 15px; text-align: left; }

        /* Style untuk tabel yang dibuat di Rich Editor agar rapi di PDF */
        .content-main table { width: 100%; border-collapse: collapse; margin-top: 5px; page-break-inside: auto; }
        .content-main table tr { page-break-inside: avoid; page-break-after: auto; }
        .content-main table th, .content-main table td { border: 1px solid black; padding: 6px; vertical-align: top; }
        .content-main table th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        .content-main table thead { display: table-header-group; }

        .footer-table { width: 100%; font-size: 9px; line-height: 1.3; }
        .footer-left { width: 70%; text-align: left; }
        .footer-right { width: 30%; text-align: right; font-weight: bold; vertical-align: bottom; }
    </style>
</head>
<body style='margin-top: -10px;'>
    <header>
        <div style='height: 15px; background: linear-gradient(to right, #4a148c, #d81b60);'></div>
        <div class='header-content'>
            <table width='100%'>
                <tr>
                    <td><img src='{$logoBase64}' class='logo'></td>
                    <td class='doc-no'>No. Dok: {$formDocNumber}</td>
                </tr>
            </table>
        </div>
    </header>

    <footer>
        <table class='footer-table'>
            <tr>
                <td class='footer-left'>
                    info@syifaglobalgroup.com<br>
                    JL. R.O Ulin No. 93, Kec Banjarbaru Selatan<br>
                    Kota Banjarbaru, Kalimantan Selatan, Indonesia 70712
                </td>
                <td class='footer-right'>
                    www.syifaglobalgroup.com
                </td>
            </tr>
        </table>
        <div style='position:absolute; bottom: -12px; left: -50px; right: -50px; height: 8px; background: linear-gradient(to right, #4a148c, #d81b60);'></div>
    </footer>

    <div class='title-section'>
        <h3>NOTULENSI</h3>
        <h4>{$formTitle}</h4>
    </div>

    <table class='info-table'>
        <tr>
            <td class='label'>Perihal</td>
            <td class='colon'>:</td>
            <td>{$formAgenda}</td>
        </tr>
        <tr>
            <td class='label'>Tempat / Lokasi</td>
            <td class='colon'>:</td>
            <td>{$formLocation}</td>
        </tr>
        <tr>
            <td class='label'>Hari / Tanggal</td>
            <td class='colon'>:</td>
            <td>{$formattedDate}</td>
        </tr>
        <tr>
            <td class='label'>Notulis</td>
            <td class='colon'>:</td>
            <td>{$notulisName}</td>
        </tr>
    </table>

    <div class='content-main'>
        ".$contentFromEditor.$actionItemsHtml.'
    </div>

    '.$attachmentsHtml.'
</body>
</html>
';
    }

    /**
     * Render tabel Action Plan & PIC (disimpan terstruktur di kolom `action_items`)
     * sebagai HTML untuk disisipkan ke PDF notulensi.
     */
    protected function buildActionItemsHtml(Meeting $record): string
    {
        $items = is_array($record->action_items) ? $record->action_items : [];

        if (empty($items)) {
            return '';
        }

        $picNames = \App\Models\User::with(['company', 'department', 'unit'])
            ->whereIn('id', $record->uniqueActionItemPicUserIds())
            ->get()
            ->mapWithKeys(function ($u) {
                $parts = array_filter([$u->company?->name, $u->department?->name, $u->unit?->name]);
                $label = $parts ? $u->name.' — '.implode(' / ', $parts) : $u->name;

                return [$u->id => $label];
            });

        $rows = '';
        foreach (array_values($items) as $index => $item) {
            $pembahasan = e((string) ($item['pembahasan'] ?? ''));
            $actionPlan = e((string) ($item['action_plan'] ?? ''));
            $picLabel = collect((array) ($item['pic_ids'] ?? []))
                ->map(fn ($id) => $picNames->get((int) $id, '-'))
                ->implode(', ');

            $rows .= "<tr>
                <td style='text-align:center;'>".($index + 1)."</td>
                <td>{$pembahasan}</td>
                <td>{$actionPlan}</td>
                <td>".e($picLabel ?: '-').'</td>
            </tr>';
        }

        return "
            <table width='100%' border='1' style='border-collapse: collapse; margin-top: 10px;'>
                <thead>
                    <tr style='background-color: #f2f2f2;'>
                        <th style='width: 30px;'>NO</th>
                        <th style='width: 180px;'>PEMBAHASAN</th>
                        <th>ACTION PLAN</th>
                        <th style='width: 100px;'>PIC</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>
        ";
    }
}
