<?php

namespace App\Filament\Admin\Pages;

use App\Models\Document;
use App\Models\DocumentReadAcknowledgment;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\DocumentApprovedByKabidNotification;
use App\Notifications\DocumentExpiryReminderNotification;
use App\Notifications\DocumentSubmittedNotification;
use App\Notifications\MandatoryDocumentReminderNotification;
use App\Notifications\MeetingReminderNotification;
use BackedEnum;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use UnitEnum;

class ReminderHub extends Page
{
    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Dokumen';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'Pusat Pengingat (Reminders)';

    protected static ?string $title = 'Pusat Pengingat (Reminder Hub)';

    protected static ?string $slug = 'reminders';

    protected string $view = 'filament.admin.pages.reminder-hub';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('guide')
                ->label('Panduan Pusat Pengingat')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modalHeading('Panduan Penggunaan Pusat Pengingat')
                ->modalIcon('heroicon-o-bell-alert')
                ->modalWidth('2xl')
                ->modalContent(view('filament.admin.pages.reminder-hub-guide-modal'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->can('access_reminder_hub')
            || $user->can('send_mandatory_read_reminder')
            || $user->can('create_personal_reminder')
            || $user->can('create_own_reminder')
            || $user->can('send_meeting_reminder')
            || $user->can('send_expiry_reminder')
            || $user->hasRole(['super_admin', 'direktur', 'kabid', 'manager']);
    }

    public bool $showPersonalModal = false;

    public string $personalTitle = '';

    public string $personalNotes = '';

    public function openPersonalModal(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user && ! $user->can('create_own_reminder') && ! $user->can('create_personal_reminder')) {
            FilamentNotification::make()
                ->title('Akses Ditolak')
                ->danger()
                ->body('Anda tidak memiliki izin (create_own_reminder) untuk membuat pengingat pribadi.')
                ->send();

            return;
        }

        $this->personalTitle = '';
        $this->personalNotes = '';
        $this->showPersonalModal = true;
    }

    public function closePersonalModal(): void
    {
        $this->showPersonalModal = false;
    }

    public function sendPersonalReminder(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if (! $user->can('create_own_reminder') && ! $user->can('create_personal_reminder')) {
            FilamentNotification::make()
                ->title('Akses Ditolak')
                ->danger()
                ->body('Anda tidak memiliki izin (create_own_reminder) untuk membuat pengingat pribadi.')
                ->send();

            return;
        }

        if (empty(trim($this->personalTitle))) {
            FilamentNotification::make()
                ->title('Judul Pengingat Wajib Diisi')
                ->warning()
                ->send();

            return;
        }

        try {
            $user->notify(new \App\Notifications\PersonalReminderNotification(
                trim($this->personalTitle),
                trim($this->personalNotes) ?: 'Tidak ada catatan tambahan.'
            ));

            $this->showPersonalModal = false;
            $this->personalTitle = '';
            $this->personalNotes = '';

            FilamentNotification::make()
                ->title('Pengingat Pribadi Berhasil Dibuat')
                ->success()
                ->body('Pengingat pribadi telah dikirim ke notifikasi lonceng Anda.')
                ->send();
        } catch (\Throwable $e) {
            Log::error('Failed creating PersonalReminderNotification: '.$e->getMessage());
        }
    }

    public function sendMandatoryReadReminders(): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (! $currentUser->can('send_mandatory_read_reminder') && ! $currentUser->hasRole(['super_admin', 'direktur', 'kabid', 'manager'])) {
            FilamentNotification::make()
                ->title('Akses Ditolak')
                ->danger()
                ->body('Anda tidak memiliki izin (send_mandatory_read_reminder) untuk mengirim pengingat dokumen wajib baca.')
                ->send();

            return;
        }

        // Ambil seluruh dokumen wajib baca yang disetujui
        $mandatoryDocs = Document::query()
            ->where('status', Document::STATUS_APPROVED)
            ->where('is_mandatory_read', true)
            ->when(! $currentUser->hasRole('super_admin') && $currentUser->company_id, function ($q) use ($currentUser) {
                $q->where('company_id', $currentUser->company_id);
            })
            ->get();

        if ($mandatoryDocs->isEmpty()) {
            FilamentNotification::make()
                ->title('Tidak Ada Dokumen Wajib Baca')
                ->warning()
                ->body('Tidak ditemukan dokumen aktif berstatus wajib baca saat ini.')
                ->send();

            return;
        }

        $sentCount = 0;

        foreach ($mandatoryDocs as $doc) {
            // Ambil daftar user id yang sudah mengonfirmasi membaca
            $readUserIds = DocumentReadAcknowledgment::where('document_id', $doc->id)->pluck('user_id')->toArray();

            // Target penerima: user yang berhak melihat dokumen tetapi belum membaca
            $targetUsers = User::query()
                ->when(! $currentUser->hasRole('super_admin') && $currentUser->company_id, fn ($q) => $q->where('company_id', $currentUser->company_id))
                ->when(! $doc->is_public && $doc->department_id, fn ($q) => $q->where('department_id', $doc->department_id))
                ->whereNotIn('id', $readUserIds)
                ->get();

            if ($targetUsers->isNotEmpty()) {
                try {
                    Notification::send($targetUsers, new MandatoryDocumentReminderNotification($doc));
                    $sentCount += $targetUsers->count();
                } catch (\Throwable $e) {
                    Log::error('Failed sending MandatoryDocumentReminderNotification: '.$e->getMessage());
                }
            }
        }

        FilamentNotification::make()
            ->title('Pengingat Wajib Baca Terkirim')
            ->success()
            ->body("Sebanyak {$sentCount} notifikasi pengingat wajib baca berhasil dikirimkan.")
            ->send();
    }

    public function sendPendingReviewReminders(): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // 1. Dokumen pending kabid
        $pendingKabidDocs = Document::where('status', Document::STATUS_PENDING_KABID)
            ->when(! $currentUser->hasRole('super_admin') && $currentUser->company_id, fn ($q) => $q->where('company_id', $currentUser->company_id))
            ->get();

        // 2. Dokumen pending direktur
        $pendingDirekturDocs = Document::where('status', Document::STATUS_PENDING_DIREKTUR)
            ->when(! $currentUser->hasRole('super_admin') && $currentUser->company_id, fn ($q) => $q->where('company_id', $currentUser->company_id))
            ->get();

        $sentCount = 0;

        // Kirim pengingat ke Kabid
        foreach ($pendingKabidDocs as $doc) {
            $kabids = User::role('kabid')
                ->where('department_id', $doc->department_id)
                ->get();

            if ($kabids->isNotEmpty()) {
                try {
                    Notification::send($kabids, new DocumentSubmittedNotification($doc));
                    $sentCount += $kabids->count();
                } catch (\Throwable $e) {
                    Log::error('Failed sending DocumentSubmittedNotification reminder: '.$e->getMessage());
                }
            }
        }

        // Kirim pengingat ke Direktur
        foreach ($pendingDirekturDocs as $doc) {
            $direkturs = User::role('direktur')
                ->when($doc->company_id, fn ($q) => $q->where('company_id', $doc->company_id))
                ->get();

            if ($direkturs->isNotEmpty()) {
                try {
                    Notification::send($direkturs, new DocumentApprovedByKabidNotification($doc));
                    $sentCount += $direkturs->count();
                } catch (\Throwable $e) {
                    Log::error('Failed sending DocumentApprovedByKabidNotification reminder: '.$e->getMessage());
                }
            }
        }

        FilamentNotification::make()
            ->title('Pengingat Review Terkirim')
            ->success()
            ->body("Pengingat persetujuan dokumen berhasil dikirim ke {$sentCount} reviewer.")
            ->send();
    }

    public function sendUpcomingMeetingReminders(): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (! $currentUser->can('send_meeting_reminder') && ! $currentUser->hasRole(['super_admin', 'direktur', 'kabid', 'manager'])) {
            FilamentNotification::make()
                ->title('Akses Ditolak')
                ->danger()
                ->body('Anda tidak memiliki izin (send_meeting_reminder) untuk mengirim pengingat rapat.')
                ->send();

            return;
        }

        $upcomingMeetings = Meeting::query()
            ->where('status', 'scheduled')
            ->where('date_time', '>=', now())
            ->where('date_time', '<=', now()->addDays(7))
            ->when(! $currentUser->hasRole('super_admin') && $currentUser->company_id, fn ($q) => $q->where('company_id', $currentUser->company_id))
            ->with(['participants', 'creator'])
            ->get();

        if ($upcomingMeetings->isEmpty()) {
            FilamentNotification::make()
                ->title('Tidak Ada Rapat Mendatang')
                ->warning()
                ->body('Tidak ada jadwal rapat mendatang dalam 7 hari ke depan.')
                ->send();

            return;
        }

        $sentCount = 0;

        foreach ($upcomingMeetings as $meeting) {
            $participants = $meeting->participants;
            if ($meeting->creator && ! $participants->contains('id', $meeting->created_by)) {
                $participants->push($meeting->creator);
            }

            if ($participants->isNotEmpty()) {
                try {
                    Notification::send($participants, new MeetingReminderNotification($meeting));
                    $meeting->update(['reminder_sent_at' => now()]);
                    $sentCount += $participants->count();
                } catch (\Throwable $e) {
                    Log::error('Failed sending MeetingReminderNotification: '.$e->getMessage());
                }
            }
        }

        FilamentNotification::make()
            ->title('Pengingat Rapat Terkirim')
            ->success()
            ->body("Pengingat jadwal rapat berhasil dikirim ke {$sentCount} peserta.")
            ->send();
    }

    public function sendExpiryReminders(): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (! $currentUser->can('send_expiry_reminder') && ! $currentUser->hasRole(['super_admin', 'direktur', 'kabid', 'manager'])) {
            FilamentNotification::make()
                ->title('Akses Ditolak')
                ->danger()
                ->body('Anda tidak memiliki izin (send_expiry_reminder) untuk mengirim pengingat kedaluwarsa.')
                ->send();

            return;
        }

        $expiringDocs = Document::query()
            ->where('status', Document::STATUS_APPROVED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(30))
            ->when(! $currentUser->hasRole('super_admin') && $currentUser->company_id, fn ($q) => $q->where('company_id', $currentUser->company_id))
            ->get();

        if ($expiringDocs->isEmpty()) {
            FilamentNotification::make()
                ->title('Tidak Ada Dokumen Kedaluwarsa')
                ->info()
                ->body('Tidak ada dokumen yang mendekati masa kedaluwarsa saat ini.')
                ->send();

            return;
        }

        $sentCount = 0;

        foreach ($expiringDocs as $doc) {
            $daysLeft = max(0, (int) now()->diffInDays($doc->expires_at, false));

            // Tentukan penerima berdasarkan visibilitas dokumen
            // (sama dengan aturan siapa yang bisa melihat dokumen ini)
            $recipients = User::query()
                ->when($doc->company_id, fn ($q) => $q->where('company_id', $doc->company_id))
                ->when(
                    // Dokumen non-publik dan terikat ke departemen → hanya user departemen itu
                    ! $doc->is_public && $doc->department_id,
                    fn ($q) => $q->where('department_id', $doc->department_id)
                )
                // Selalu sertakan pembuat dokumen meskipun beda departemen
                ->orWhere('id', $doc->user_id)
                ->distinct()
                ->get();

            if ($recipients->isNotEmpty()) {
                try {
                    Notification::send($recipients, new DocumentExpiryReminderNotification($doc, $daysLeft));
                    $doc->update(['review_reminder_sent_at' => today()]);
                    $sentCount += $recipients->count();
                } catch (\Throwable $e) {
                    Log::error('Failed sending DocumentExpiryReminderNotification: '.$e->getMessage());
                }
            }
        }

        FilamentNotification::make()
            ->title('Pengingat Kedaluwarsa Terkirim')
            ->success()
            ->body("Pengingat masa berlaku dokumen berhasil dikirim ke {$sentCount} penerima.")
            ->send();
    }
}
