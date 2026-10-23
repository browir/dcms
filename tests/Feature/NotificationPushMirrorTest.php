<?php

namespace Tests\Feature;

use App\Listeners\MirrorDatabaseNotificationToPush;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Bus;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

/** Notifikasi lonceng ikut dikirim sebagai web push ke perangkat yang berlangganan. */
class NotificationPushMirrorTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create(['name' => 'Staf', 'username' => 'staf', 'email' => 'staf@example.test', 'password' => 'password']);
    }

    private function bell(array $via = ['database']): Notification
    {
        return new class($via) extends Notification
        {
            public function __construct(private array $channels) {}

            public function via(object $notifiable): array
            {
                return $this->channels;
            }

            public function toArray(object $notifiable): array
            {
                return [
                    'title' => 'Surat masuk: Undangan rapat',
                    'message' => 'Dari Dinas Kesehatan · 001/SM/SGG/X/2026',
                    'actions' => [['label' => 'Buka', 'url' => 'https://dcms.test/admin/surat-masuk/5']],
                ];
            }
        };
    }

    public function test_notifikasi_lonceng_dijadwalkan_jadi_push_setelah_response(): void
    {
        config(['webpush.vapid.public_key' => 'test-public-key']);
        Bus::fake();

        $this->user()->notify($this->bell());

        Bus::assertDispatchedAfterResponse(CallQueuedClosure::class);
    }

    public function test_tanpa_vapid_atau_sudah_ada_push_sendiri_tidak_dikirim_ulang(): void
    {
        Bus::fake();

        config(['webpush.vapid.public_key' => null]);
        $this->user()->notify($this->bell());

        config(['webpush.vapid.public_key' => 'test-public-key']);
        User::query()->first()->notify($this->bell(['database', WebPushChannel::class]));

        Bus::assertNotDispatchedAfterResponse(CallQueuedClosure::class);
    }

    public function test_isi_push_mengikuti_judul_pesan_dan_link_notifikasi(): void
    {
        $push = MirrorDatabaseNotificationToPush::pushFor([
            'title' => 'Dokumen menunggu persetujuan',
            'body' => '<b>SOP Triase IGD</b>',
            'actions' => [['url' => 'https://dcms.test/admin/documents/16?tab=diskusi']],
        ]);

        $this->assertSame('Dokumen menunggu persetujuan', $push->title);
        $this->assertSame('SOP Triase IGD', $push->body);
        $this->assertSame('/admin/documents/16?tab=diskusi', $push->url);
    }
}
