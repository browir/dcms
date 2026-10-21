# Notifikasi DCMS ke WhatsApp via n8n

Panduan ini menjelaskan cara menyambungkan notifikasi DCMS ke HP Anda melalui
**WhatsApp**, menggunakan **n8n** sebagai jembatan (bridge).

## Cara kerja singkat

```
DCMS (Laravel)                       n8n                         HP Anda
──────────────        HTTP POST      ─────────      WhatsApp     ────────
Notification  ───────────────────▶  Webhook  ───────────────▶  Pop-up WA
(via channel 'n8n')                  → format → WhatsApp node
```

Setiap kali ada kejadian penting (dokumen diajukan, disetujui Kabid, keputusan
final Direktur, undangan & pengingat rapat), DCMS mengirim data notifikasi
sebagai **HTTP POST (JSON)** ke sebuah **Webhook n8n**. Di dalam n8n, payload
itu diteruskan ke node WhatsApp sehingga muncul sebagai pesan/pop-up di HP.

Keuntungan pendekatan ini: DCMS tidak perlu tahu detail provider WhatsApp.
Anda bebas mengganti provider (WhatsApp Cloud API, Twilio, dsb.) langsung di
n8n tanpa mengubah kode Laravel.

---

## Bagian 1 — Konfigurasi di DCMS (sudah disiapkan)

Perubahan berikut sudah dibuat di project:

| Item | File |
|------|------|
| Kolom `phone` (nomor WA) pada tabel `users` | `database/migrations/2026_08_20_000000_add_phone_to_users_table.php` |
| `phone` di `$fillable` + `routeNotificationForN8n()` | `app/Models/User.php` |
| Channel pengirim ke webhook n8n | `app/Notifications/Channels/N8nWhatsAppChannel.php` |
| Konfigurasi `services.n8n` | `config/services.php` |
| Channel `n8n` + `toN8n()` pada notifikasi | `app/Notifications/*.php` |

### Langkah yang perlu Anda lakukan

1. **Jalankan migration** untuk menambah kolom `phone`:

   ```bash
   php artisan migrate
   ```

2. **Isi nomor WhatsApp** tiap user (kolom `users.phone`), format internasional.
   Boleh diawali `0` (otomatis diubah ke `62`) atau langsung `62...`.
   Contoh: `081234567890` atau `6281234567890`.

3. **Tambahkan variabel berikut ke file `.env`** (URL diisi dari Bagian 2):

   ```dotenv
   N8N_WEBHOOK_URL=https://n8n-anda.contoh.com/webhook/dcms-whatsapp
   N8N_WEBHOOK_SECRET=ganti-dengan-token-rahasia-anda
   ```

4. **Refresh config cache** (jika Anda memakai config cache):

   ```bash
   php artisan config:clear
   ```

5. **Pastikan queue berjalan** — sebagian notifikasi `ShouldQueue`, jadi WA
   dikirim lewat worker:

   ```bash
   php artisan queue:work
   ```

---

## Bagian 2 — Membuat workflow di n8n

1. Buat **workflow baru** di n8n.
2. Tambahkan node **Webhook**:
   - HTTP Method: `POST`
   - Path: mis. `dcms-whatsapp`
   - Setelah di-*activate*, salin **Production URL** → tempel ke `N8N_WEBHOOK_URL` di `.env`.
3. (Opsional, disarankan) Tambahkan node **IF** untuk memvalidasi header rahasia:
   - Bandingkan `{{$json["headers"]["x-dcms-signature"]}}` dengan nilai `N8N_WEBHOOK_SECRET`.
   - Jika tidak cocok → hentikan (stop & error).
4. Tambahkan node **WhatsApp** (mis. *WhatsApp Business Cloud*, atau HTTP Request
   ke provider WA pilihan Anda):
   - Nomor tujuan: `{{$json["body"]["to"]}}`
   - Isi pesan: `{{$json["body"]["message"]}}`
5. **Activate** workflow.

### Bentuk payload yang dikirim DCMS

```json
{
  "to": "6281234567890",
  "name": "Nama User",
  "channel": "whatsapp",
  "source": "dcms",
  "type": "document_final_decision",
  "title": "Dokumen Disetujui",
  "message": "Halo Nama User, dokumen \"...\" telah DISETUJUI oleh Direktur.",
  "url": "https://dcms.contoh.com/admin/documents/123"
}
```

- `to` — nomor WA tujuan (sudah dinormalisasi ke format `62...`).
- `message` — teks siap kirim ke WhatsApp.
- `title`, `type`, `url` — metadata tambahan bila ingin memformat pesan lebih kaya di n8n.

Header tambahan: `X-DCMS-Signature: <N8N_WEBHOOK_SECRET>` (jika secret diisi).

### Pengumuman kegiatan ke GRUP WhatsApp

Menu **Kegiatan** bisa mengirim pengumuman ke grup WhatsApp memakai webhook yang
sama. Bedakan di n8n lewat `channel`:

```json
{
  "to": "120363012345678901@g.us",
  "channel": "whatsapp_group",
  "source": "dcms",
  "type": "agenda_announcement",
  "title": "Pemberitahuan Kegiatan",
  "message": "📢 *PEMBERITAHUAN KEGIATAN*\nNomor: 001/C.2/SGG/X/2026\n\n*Pelatihan Arsip*\n🗓 Senin, 26 Oktober 2026, 09.00–12.00 WITA\n📍 Aula Serbaguna\n...",
  "url": "https://dcms.contoh.com/admin/kegiatan/12",
  "letter_number": "001/C.2/SGG/X/2026",
  "notice_pdf_url": "https://dcms.contoh.com/kegiatan/12/surat/unduh?expires=...&signature=..."
}
```

- `to` — **ID grup** (bukan nomor HP). Diambil berurutan dari: Data Master → Unit →
  *Grup WhatsApp* (jika jangkauan unit) → Data Master → Perusahaan → *Grup WhatsApp*
  → `N8N_WHATSAPP_GROUP` di `.env`.
- `notice_pdf_url` — link PDF surat pemberitahuan (bertanda tangan, berlaku 30 hari,
  tanpa login) bila kegiatan punya surat; bisa dilampirkan sebagai dokumen di node WhatsApp.

Di workflow n8n, tambahkan node **IF** `{{$json.body.channel}} == "whatsapp_group"` lalu
arahkan ke node pengirim pesan grup milik provider WA Anda.

---

## Bagian 3 — Notifikasi yang dikirim ke WhatsApp

| Notifikasi | Kapan dikirim |
|------------|----------------|
| `DocumentSubmittedNotification` | Dokumen baru diajukan → ke Kabid |
| `DocumentApprovedByKabidNotification` | Disetujui Kabid → ke Direktur |
| `DocumentFinalDecisionNotification` | Keputusan final Direktur → ke pengaju |
| `MeetingInvitationNotification` | Undangan rapat → ke peserta |
| `MeetingReminderNotification` | Pengingat rapat → ke peserta |

Untuk menambah/menghapus notifikasi WhatsApp: pada file notifikasi terkait,
tambahkan `\App\Notifications\Channels\N8nWhatsAppChannel::class` ke array `via()`
dan sediakan method `toN8n($notifiable)` yang mengembalikan array berisi `message`.

---

## Bagian 4 — Menguji

1. Isi `users.phone` milik Anda dengan nomor WA aktif.
2. Pastikan `N8N_WEBHOOK_URL` sudah benar & workflow n8n *active*.
3. Picu salah satu kejadian (mis. ajukan dokumen / undang rapat), atau via tinker:

   ```bash
   php artisan tinker
   ```
   ```php
   $u = \App\Models\User::whereNotNull('phone')->first();
   $d = \App\Models\Document::first();
   $u->notify(new \App\Notifications\DocumentSubmittedNotification($d));
   ```

4. Cek **Executions** di n8n untuk melihat payload masuk, lalu cek WhatsApp di HP.

### Troubleshooting

- **Tidak ada eksekusi di n8n** → cek `N8N_WEBHOOK_URL`, workflow harus *active*,
  dan lihat `storage/logs/laravel.log` untuk pesan dari `N8nWhatsAppChannel`.
- **Eksekusi masuk tapi WA tidak terkirim** → periksa konfigurasi node WhatsApp/kredensial provider di n8n.
- **Notifikasi tidak terkirim sama sekali** → pastikan `php artisan queue:work` berjalan.
- **Nomor salah** → pastikan `users.phone` terisi & valid; channel mengubah awalan `0` menjadi `62`.
