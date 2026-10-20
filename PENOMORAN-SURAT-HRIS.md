# Dokumentasi Penomoran Surat (Implementasi HRIS Saat Ini)

Dokumen ini menjelaskan cara kerja penomoran surat **di aplikasi HRIS yang sekarang berjalan** (panel Persuratan, `/surat`). Tujuannya sebagai acuan saat fitur ini dipindahkan ke sistem lain.

Dokumen pendamping: [PENOMORAN-SURAT.md](PENOMORAN-SURAT.md) membahas sistem lama (`audit.syifaglobalgroup.com`) yang menjadi dasar desain ini. Bagian yang sama persis dengan sistem lama hanya diringkas di sini.

## 1. Ringkasan

- Penomoran dikerjakan satu service: `App\Services\Persuratan\NomorSuratService::generate()`.
- Satu-satunya pemanggil adalah tombol **Buat Nomor Surat** di beranda panel Persuratan (`App\Filament\Persuratan\Pages\Dashboard`).
- Data disimpan di database terpisah dengan koneksi `persuratan` (bukan DB HRIS). Isi `DB_PERSURATAN_*` di `.env`.
- Hanya **surat keluar** yang diberi nomor otomatis. Surat masuk tidak melewati generator.
- Dokumen SP dan Berita Acara (modul `Sp`) **tidak memakai** generator ini. Nomornya diketik manual (lihat bagian 8).

## 2. Format Nomor

```
{urut:3 digit}/{kode_jenis}.{kode_unit}/{kode_perusahaan}/{bulan_romawi}/{tahun}
```

Contoh: `001/A.2/SGG/VII/2025`

| Bagian | Sumber | Catatan |
|---|---|---|
| `001` | Counter (bagian 4) | `sprintf('%03d')`. Lewat 999 jadi 4 digit (`1000`), tanpa error. |
| `A` | `JenisSurat::value` | Enum, lihat bagian 3. |
| `2` | `unit.kode` | String sampai 20 karakter. Tidak unik. |
| `SGG` | `perusahaan.kode_surat` | Diambil lewat `unit -> perusahaan`. |
| `VII` | Bulan dari **tanggal surat** | Konstanta `ROMAWI` di service. |
| `2025` | Tahun dari **tanggal surat** | Bukan tahun hari ini. |

Urutan bagian sama untuk semua jenis surat. Tidak ada percabangan per jenis. Pertanyaan terbuka soal pola SP yang berbeda ada di bagian 9.

Kode pembentuk nomor ada di satu baris:

```php
sprintf('%03d/%s.%s/%s/%s/%d', $urut, $jenis->value, $unit->kode, $perusahaan->kode_surat, self::ROMAWI[$tanggal->month - 1], $tanggal->year)
```

## 3. Data Referensi

### 3.1 Jenis surat

Bukan tabel DB, melainkan enum PHP `App\Enum\JenisSurat` (26 case). Nilai enum adalah kode yang masuk ke nomor. Tersimpan sebagai string di `surat.jenis`.

| Kode | Jenis | Kode | Jenis |
|---|---|---|---|
| A | Kebijakan / Peraturan / PP | MCU | Surat MCU |
| B | Pedoman / Panduan Pelaksanaan | SOP | Standard Operational Procedure |
| C | Surat Edaran / Pengumuman | FRM | Formulir |
| D | Internal Memo | Q | Surat Keterangan |
| E | Surat Keputusan | PKWT | Perjanjian Kerja Waktu Tertentu |
| F | Surat Perintah | PKWTT | Perjanjian Kerja Waktu Tidak Tentu |
| G | Surat Tugas | CERT | Sertifikat |
| H | Surat Undangan | SPKK | Surat Penugasan Kerja Klinik |
| I | Surat Kuasa | SKK | Surat Keterangan Kesehatan |
| J | Berita Acara | SP | Surat Peringatan |
| K | Surat Perjanjian / MoU | P | Surat Rekomendasi |
| L | Surat Permohonan Barang / Jasa | N | Surat Tanda Terima |
| M | Surat Balasan | O | Surat Pengantar |

Catatan:
- Tambah jenis baru = tambah `case` dan `label()` di enum. Tidak perlu migrasi.
- Warna label di UI dihitung dari **urutan case** (`warna()`). Jangan mengacak urutan case.
- `tipeSurat()` selalu mengembalikan `keluar`.

### 3.2 Perusahaan (penerbit) — tabel `perusahaan`

| Kolom | Keterangan |
|---|---|
| `id` | Sengaja **sama dengan id sistem lama** (1, 2, 3, 4, 7). Lihat `PersuratanPerusahaanSeeder`. |
| `kode_surat` | Unik, maksimal 20 karakter. Masuk ke nomor surat. |
| `nama` | Nama penerbit. |
| `hris_company_id` | ULID `m_companies`, nullable, tanpa FK karena beda database. Null = penerbit di luar HRIS. |
| `legacy_id` | Id sistem lama, unik, nullable. |
| `is_active` | Boolean. |

Data awal:

| id | kode_surat | nama |
|---|---|---|
| 1 | `SGG` | PT Syifa Global Group |
| 2 | `RSUSM` | RSU Syifa Medika Banjarbaru |
| 3 | `RSUSM-BRB` | RSU Syifa Medika Barabai |
| 4 | `KOP-SMP` | Koperasi Syifa Medika Persada |
| 7 | `SMP` | PT Syifa Medika Persada |

Syifa Global Med (5) dan Syifa Global Lestari (6) sengaja belum dimasukkan karena kode suratnya belum ada.

Perusahaan penerbit **tidak harus 1:1 dengan perusahaan HRIS**. RSUSM dan PT Syifa Medika Persada satu badan hukum tetapi punya kode dan counter sendiri.

### 3.3 Unit — tabel `unit`

| Kolom | Keterangan |
|---|---|
| `perusahaan_id` | FK ke `perusahaan`. Satu unit dimiliki satu perusahaan. |
| `kode` | Maksimal 20 karakter, **tidak unik**. Masuk ke nomor surat. |
| `nama` | Nama unit. |
| `legacy_id` | Id unit sistem lama. Unik per `(perusahaan_id, legacy_id)`. |
| `hris_unit_id` | ULID unit HRIS, nullable, tanpa FK. |

Aturan:
- Unit yang sama di dua perusahaan = dua baris `unit`.
- **Perusahaan surat tidak disimpan di `surat`**, hanya lewat `surat -> unit -> perusahaan`.
- Unit yang sudah dipakai surat tidak boleh dihapus (FK menolak, plus guard di `Unit::booted()`).
- Unit placeholder `(Tanpa unit)` dengan kode `-` dibuat per perusahaan saat impor data lama, untuk surat lama tanpa unit.

Pengelolaan data perusahaan dan unit lewat resource Filament di `app/Filament/Persuratan/Resources/{Perusahaans,Units}`.

## 4. Counter Nomor Urut

Tabel `nomor_surat_counter`:

```
perusahaan_id  FK -> perusahaan
tahun          unsigned smallint
nomor_terakhir unsigned int default 0
PRIMARY KEY (perusahaan_id, tahun)
```

**Scope counter: `(perusahaan, tahun)` saja.**
- Tidak per unit, tidak per jenis surat, tidak per bulan.
- Semua jenis surat dan semua unit di satu perusahaan berbagi satu urutan.
- Tidak reset tiap bulan. Reset hanya saat tahun berganti.
- Tahun diambil dari **tanggal surat**, bukan tanggal pembuatan. Surat bertanggal 2025 yang dibuat di 2026 memakai counter 2025 dan melanjutkan urutannya.

### 4.1 Algoritma (`NomorSuratService::generate`)

Seluruhnya dalam satu transaksi di koneksi `persuratan`:

1. `perusahaan = unit.perusahaan`, `key = (perusahaan.id, tahun tanggal surat)`.
2. `INSERT IGNORE` baris counter dengan `nomor_terakhir = 0`. Ini menjamin barisnya ada untuk di-lock.
3. `SELECT ... FOR UPDATE` baris counter (`lockForUpdate()`).
4. `urut = nomor_terakhir + 1`, lalu `UPDATE nomor_terakhir = urut`.
5. `INSERT INTO surat` dengan nomor yang sudah diformat.
6. `COMMIT`. Kalau salah satu langkah gagal, rollback total sehingga tidak ada nomor yang bocor atau terlompati.

Row lock inilah yang mencegah nomor kembar saat dua user membuat nomor bersamaan. **Wajib dipertahankan** di sistem baru. DB harus mendukung row-level lock (MySQL/InnoDB atau PostgreSQL).

Jaring pengaman kedua: kolom `surat.nomor` punya `UNIQUE`.

### 4.2 Yang tidak ada di sistem ini

- Tidak ada reservasi nomor massal (fitur "generate massal" sistem lama tidak dibawa).
- Tidak ada edit surat yang meregenerasi nomor. Nomor yang sudah terbit tidak berubah.
- Tidak ada pembatalan atau void nomor. Nomor yang terpakai tidak dikembalikan ke counter, jadi urutan bisa bolong kalau baris surat dihapus manual.

## 5. Tabel `surat`

Skema akhir setelah semua migrasi di `database/migrations/persuratan/`:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | PK |
| `legacy_id` | uint, null, unik | Id surat sistem lama |
| `nomor` | string, **unik** | Nomor lengkap hasil format |
| `tipe_surat` | enum `masuk`/`keluar` | Default `keluar` |
| `status` | enum `Tersimpan`/`Draft` | Default `Tersimpan` |
| `nomor_urut` | uint, null | Bagian urut saja. Null untuk surat masuk. |
| `jenis` | string(10) | Kode enum `JenisSurat` |
| `unit_id` | FK `unit`, wajib | Perusahaan diturunkan dari sini |
| `tanggal` | date, index | Tanggal surat |
| `judul_surat` | string, null | Wajib diisi lewat form |
| `perihal` | string, null | Opsional |
| `deskripsi` | text, null | Opsional |
| `dibuat_oleh_id` | string(26), null | ULID user HRIS pembuat |
| `dibuat_oleh_nama` | string, null | Snapshot nama pembuat |
| `legacy_user_id` | uint, null, index | Id user sistem lama (`users.id` di DB persuratan) |

Nama pembuat dibaca dari `users` hasil impor (`legacy_user_id`) lebih dulu, lalu cadangan `dibuat_oleh_nama`.

## 6. Form Buat Nomor Surat

Field di modal (Dashboard):

| Field | Wajib | Catatan |
|---|---|---|
| Jenis Surat | Ya | Pilihan dari enum, ditampilkan `kode — label` |
| Unit | Ya | Pilihan `kode_perusahaan · kode_unit — nama`, perusahaan otomatis mengikuti unit |
| Tanggal Surat | Ya | Default hari ini. Menentukan bulan, tahun, dan counter. |
| Judul Surat | Ya | Maksimal 255 |
| Perihal | Tidak | Maksimal 255 |
| Deskripsi | Tidak | Teks bebas |

Hasil: nomor ditampilkan ke user lewat `hasilNomor`.

Perilaku yang perlu diketahui:
- Pembuat surat **bebas memilih unit apa pun**. Tidak ada pengunci ke unit milik user, dan tidak ada validasi relevansi unit dengan surat.
- Tidak ada pengecekan hak per jenis surat atau per perusahaan. Akses panel `/surat` saat ini dibatasi ke Admin/Super Admin (`User::canAccessPanel`).

## 7. Pencarian dan Riwayat

Beranda juga menampilkan riwayat surat (10 per halaman, terbaru dulu berdasarkan `tanggal`).
- Mode normal: filter perusahaan, unit, jenis.
- Mode cari (kata kunci terisi): mencari di **semua** surat pada kolom `nomor`, `judul_surat`, `perihal`, `dibuat_oleh_nama`, dan nama lengkap pembuat. Filter diabaikan selama mencari.

## 8. Surat Masuk dan Dokumen SP / Berita Acara

- **Surat masuk**: tidak memakai generator. Hanya ada lewat impor data lama (`tipe_surat = masuk`, nomor eksternal apa adanya, `nomor_urut` null). Belum ada form pembuatan surat masuk di HRIS.
- **SP dan Berita Acara** (`App\Models\Sp\EmployeeSp`, `SpReport`): kolom `nomor` diisi **manual** oleh user (string maksimal 255, wajib untuk SP, opsional untuk Berita Acara). Tidak ada counter dan tidak terhubung ke tabel `surat`. Kalau kosong, `SpReport::documentNumber()` memakai cadangan `BA-{id}`.
  - Akibatnya nomor SP/BA **tidak muncul** di daftar dan pencarian panel Persuratan, dan counter tidak tahu nomor yang sudah dipakai di sana.
  - Kalau sistem baru ingin satu sumber nomor, perlu keputusan: SP/BA memanggil generator yang sama, atau tetap manual.

## 9. Migrasi Data dari Sistem Lama

Perintah: `php artisan persuratan:import-legacy [--dry-run]`

- Membaca tabel `legacy_*` yang di-dump apa adanya ke DB `persuratan`: `legacy_jenis_surat`, `legacy_departemen`, `legacy_unit`, `legacy_surat` (wajib), `legacy_nomor_surat_counter` dan `legacy_kode_surat_unit` (opsional).
- Satu transaksi. `--dry-run` menjalankan semuanya lalu rollback dan hanya mencetak laporan.
- Idempoten lewat `legacy_id`.
- **Nomor kembar** tidak diimpor dan dilaporkan.
- Counter hasil impor = `max(counter lama, nomor urut tertinggi di surat)` per `(perusahaan, tahun)`. Selisih dilaporkan. Nomor urut diambil dari nomor surat keluar dengan regex `^(\d+)/`.
- Unit yang perusahaannya berbeda dari perusahaan surat dibuatkan baris unit tambahan di perusahaan surat.
- Surat tanpa unit atau unit tak dikenal masuk ke unit `(Tanpa unit)`.

## 10. Checklist Pemindahan ke Sistem Lain

Yang harus ikut pindah, berurutan:

1. **Data referensi**: `perusahaan` (id dipertahankan), `unit` (dengan `perusahaan_id`), dan daftar jenis surat (26 kode di bagian 3.1).
2. **Counter**: pindahkan isi `nomor_surat_counter` apa adanya, atau hitung ulang sebagai `MAX(nomor_urut)` per `(perusahaan, tahun)`. Ambil yang terbesar supaya nomor berikutnya tidak bentrok.
3. **Arsip surat**: tabel `surat` beserta `nomor`, `nomor_urut`, `jenis`, `unit_id`, `tanggal`, pembuat. Pertahankan `UNIQUE(nomor)`.
4. **Logika generate**: format nomor (bagian 2), scope counter `(perusahaan, tahun)` (bagian 4), dan transaksi dengan row lock (bagian 4.1). Pakai tanggal surat, bukan tanggal sistem.
5. **Uji konkurensi**: buat nomor paralel dari beberapa proses pada perusahaan dan tahun yang sama. Hasil harus berurutan tanpa kembar dan tanpa lubang.

Keputusan yang masih terbuka (bukan bagian migrasi 1:1):

- **Pola urutan kode per jenis surat.** Sistem lama dan HRIS memakai satu format untuk semua jenis. Ada temuan dokumen fisik Surat Perintah dengan urutan `kode_unit.kode_jenis` (terbalik). Perlu konfirmasi pemilik SOP. Kalau memang berbeda per jenis, sistem baru butuh pemetaan pola per jenis, bukan satu template global.
- **Penerbit vs tujuan** untuk surat lintas entitas (holding menerbitkan surat untuk karyawan unit bisnis lain). Skema saat ini hanya punya satu `unit_id`, jadi hanya bisa mencatat satu sisi.
- **Penguncian unit ke profil user.** Saat ini bebas pilih (bagian 6).
- **Kode unit tidak unik.** Pertimbangkan constraint keunikan per perusahaan kalau sistem baru ingin nomor lebih mudah ditelusuri, setelah data lama dibersihkan.
- **Perusahaan yang belum punya kode surat** (Syifa Global Med, Syifa Global Lestari): isi kode sebelum dipakai.
- **SP dan Berita Acara**: manual atau lewat generator (bagian 8).
- **Overflow urut** lewat 999 menghasilkan 4 digit. Putuskan apakah itu diterima.

## 11. Lokasi Kode

| Hal | Lokasi |
|---|---|
| Service penomoran | `app/Services/Persuratan/NomorSuratService.php` |
| Enum jenis surat | `app/Enum/JenisSurat.php` |
| Model | `app/Models/Persuratan/{Surat,Unit,Perusahaan,User}.php` |
| UI buat nomor dan riwayat | `app/Filament/Persuratan/Pages/Dashboard.php`, `resources/views/filament/persuratan/pages/dashboard.blade.php` |
| Master perusahaan dan unit | `app/Filament/Persuratan/Resources/` |
| Panel | `app/Providers/Filament/PersuratanPanelProvider.php` (path `/surat`) |
| Migrasi skema | `database/migrations/persuratan/` |
| Seeder perusahaan | `database/seeders/PersuratanPerusahaanSeeder.php` |
| Impor data lama | `app/Console/Commands/ImportPersuratanLegacy.php` |
