<?php

namespace Database\Seeders;

use App\Enum\JenisSurat;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Data contoh untuk pengembangan lokal (organisasi, ruangan, rapat, dokumen).
 *
 * Jalankan: php artisan db:seed --class=DemoDataSeeder
 * Hanya berjalan di APP_ENV=local.
 */
class DemoDataSeeder extends Seeder
{
    /** @var array<string, JenisSurat> */
    private array $jenisPerKategori = [];

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command->error('❌ DemoDataSeeder hanya untuk APP_ENV=local.');

            return;
        }

        if (Company::where('code', 'DEMO')->exists()) {
            $this->command->warn('Data demo sudah ada, dilewati.');

            return;
        }

        // Jangan kirim notifikasi/email saat membuat data contoh
        Notification::fake();

        $company = Company::create(['name' => 'RS Demo Sehat', 'code' => 'DEMO', 'kode_surat' => 'RSDS']);

        $structure = [
            ['Umum & SDM', 'UMM', ['Kepegawaian' => '1', 'Rumah Tangga' => '2']],
            ['Keuangan', 'KEU', ['Akuntansi' => '3', 'Kasir' => '4']],
            ['Pelayanan Medis', 'YAN', ['Rawat Jalan' => '5', 'Rawat Inap' => '6']],
        ];

        $units = collect();
        foreach ($structure as [$name, $code, $unitNames]) {
            $department = Department::create(['name' => $name, 'code' => $code, 'company_id' => $company->id]);
            foreach ($unitNames as $unitName => $unitCode) {
                $units->push(Unit::create(['name' => $unitName, 'prefix' => $unitCode, 'department_id' => $department->id]));
            }
        }

        $categories = collect([
            ['Surat Keputusan', 'SK', '#2563eb'],
            ['Surat Edaran', 'SE', '#0891b2'],
            ['SOP', 'SOP', '#059669'],
            ['Memo Internal', 'MI', '#d97706'],
            ['Surat Undangan', 'UND', '#7c3aed'],
        ])->map(fn ($c) => DocumentCategory::create([
            'name' => $c[0], 'prefix' => $c[1], 'company_id' => $company->id,
        ]));

        // Jenis surat (kode di nomor) per kategori contoh
        $this->jenisPerKategori = ['SK' => JenisSurat::SURAT_KEPUTUSAN, 'SE' => JenisSurat::EDARAN_PENGUMUMAN, 'SOP' => JenisSurat::SOP, 'MI' => JenisSurat::INTERNAL_MEMO, 'UND' => JenisSurat::SURAT_UNDANGAN];

        $locations = collect([
            ['Ruang Rapat Utama', 'Gedung A Lt. 1', 20],
            ['Ruang Direksi', 'Gedung A Lt. 3', 10],
            ['Aula Serbaguna', 'Gedung B Lt. 1', 80],
            ['Ruang Rapat Lt. 2', 'Gedung A Lt. 2', 12],
        ])->map(fn ($l) => MeetingLocation::create([
            'name' => $l[0], 'address' => $l[1], 'capacity' => $l[2], 'company_id' => $company->id,
        ]));

        $names = ['Rina Kartika', 'Budi Santoso', 'Siti Aminah', 'Andi Wijaya', 'Dewi Lestari', 'Agus Pratama', 'Maya Sari', 'Hendra Gunawan'];
        $staff = collect($names)->map(function ($name, $i) use ($company, $units) {
            $unit = $units[$i % $units->count()];

            return User::create([
                'name' => $name,
                'username' => strtolower(explode(' ', $name)[0]),
                'email' => strtolower(str_replace(' ', '.', $name)).'@demo.test',
                'password' => 'password',
                'company_id' => $company->id,
                'department_id' => $unit->department_id,
                'unit_id' => $unit->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        });

        $admins = User::role('super_admin')->get();
        $everyone = $staff->merge($admins);

        $this->seedMeetings($company, $locations, $staff, $everyone);
        $this->seedDocuments($company, $units, $categories, $staff);

        $this->command->info('✅ Data demo dibuat: 1 perusahaan, 3 departemen, 4 ruangan, '.$staff->count().' pegawai, rapat & dokumen contoh.');
    }

    private function seedMeetings(Company $company, $locations, $staff, $everyone): void
    {
        $today = Carbon::today();
        $now = now();

        // [judul, waktu mulai, durasi jam, status, ada notulen, index ruangan]
        $meetings = [
            ['Evaluasi Kinerja Triwulan III', $today->copy()->subDays(18)->setTime(9, 0), 2, 'completed', true, 0],
            ['Rapat Anggaran Tahun Depan', $today->copy()->subDays(14)->setTime(13, 0), 2, 'completed', true, 1],
            ['Sosialisasi SOP Pelayanan Baru', $today->copy()->subDays(10)->setTime(10, 0), 3, 'completed', true, 2],
            ['Koordinasi Akreditasi', $today->copy()->subDays(7)->setTime(14, 0), 2, 'completed', false, 0],
            ['Rapat Pengadaan Alat Medis', $today->copy()->subDays(4)->setTime(9, 30), 1.5, 'completed', false, 3],
            ['Rapat Mingguan Manajemen', $today->copy()->subDays(2)->setTime(8, 0), 1, 'completed', true, 1],
            ['Pembahasan Jadwal Cuti Bersama', $today->copy()->subDays(1)->setTime(15, 0), 1, 'cancelled', false, 3],
            ['Briefing Pagi Pelayanan', $now->copy()->subMinutes(40), 1.5, 'scheduled', false, 0],
            ['Review Laporan Keuangan Bulanan', $today->copy()->setTime(13, 30), 2, 'scheduled', false, 1],
            ['Rapat Persiapan Audit Internal', $today->copy()->setTime(16, 0), 1, 'scheduled', false, 3],
            ['Rapat Koordinasi Unit Rawat Inap', $today->copy()->addDay()->setTime(9, 0), 2, 'scheduled', false, 0],
            ['Workshop Pengelolaan Arsip', $today->copy()->addDays(2)->setTime(10, 0), 4, 'scheduled', false, 2],
            ['Rapat Direksi', $today->copy()->addDays(3)->setTime(14, 0), 2, 'scheduled', false, 1],
            ['Evaluasi Program Mutu', $today->copy()->addDays(6)->setTime(9, 0), 2, 'scheduled', false, 3],
            ['Rapat Rencana Kerja 2027', $today->copy()->addDays(9)->setTime(13, 0), 3, 'scheduled', false, 2],
        ];

        foreach ($meetings as $i => [$title, $start, $hours, $status, $hasMinutes, $locIndex]) {
            $creator = $staff[$i % $staff->count()];
            $notulis = $staff[($i + 1) % $staff->count()];
            $location = $locations[$locIndex];
            $pic = $staff[($i + 2) % $staff->count()];

            $meeting = Meeting::create([
                'title' => $title,
                'doc_number' => $status === 'completed'
                    ? sprintf('%03d/RPT/DEMO/%s/%d', $i + 1, $this->romanMonth($start->month), $start->year)
                    : null,
                'agenda' => "1. Pembukaan\n2. Pembahasan {$title}\n3. Tindak lanjut",
                'content' => $hasMinutes ? '<p>Rapat membahas '.strtolower($title).' dan menyepakati tindak lanjut.</p>' : null,
                'action_items' => $hasMinutes ? [
                    ['pembahasan' => 'Tindak lanjut hasil rapat', 'action_plan' => 'Menyusun laporan ringkas hasil '.strtolower($title), 'pic_ids' => [$pic->id]],
                    ['pembahasan' => 'Koordinasi lintas unit', 'action_plan' => 'Mengirim rekap ke seluruh unit terkait', 'pic_ids' => [$notulis->id]],
                ] : null,
                'date_time' => $start,
                'end_time' => $start->copy()->addMinutes((int) ($hours * 60)),
                'location' => $location->name,
                'meeting_location_id' => $location->id,
                'status' => $status,
                'company_id' => $company->id,
                'department_id' => $creator->department_id,
                'unit_id' => $creator->unit_id,
                'created_by' => $creator->id,
                'notulis_id' => $notulis->id,
            ]);

            $meeting->participants()->attach(
                $everyone->shuffle()->take(5)->pluck('id')->push($creator->id, $notulis->id)->unique()->all()
            );
        }
    }

    private function seedDocuments(Company $company, $units, $categories, $staff): void
    {
        $titles = [
            'Kebijakan Pelayanan Pasien', 'Pedoman Pengelolaan Arsip', 'Penetapan Tim Mutu', 'Prosedur Pengadaan Barang',
            'Jadwal Jaga Dokter', 'Tata Tertib Pegawai', 'Prosedur Penanganan Keluhan', 'Standar Kebersihan Ruangan',
            'Pengangkatan Kepala Unit', 'Pedoman Keselamatan Kerja', 'Prosedur Rekam Medis', 'Edaran Libur Nasional',
            'Undangan Rapat Kerja Tahunan', 'Memo Penggunaan Kendaraan Dinas', 'Prosedur Kas Kecil', 'SOP Triase IGD',
            'Kebijakan Penggunaan Email Dinas', 'Pedoman Orientasi Pegawai Baru', 'Edaran Protokol Kesehatan', 'Penetapan Tarif Layanan',
            'SOP Sterilisasi Alat', 'Memo Lembur Akhir Tahun', 'Undangan Sosialisasi Akreditasi', 'Prosedur Retur Obat',
        ];

        $statuses = [
            Document::STATUS_APPROVED, Document::STATUS_APPROVED, Document::STATUS_APPROVED, Document::STATUS_APPROVED,
            Document::STATUS_PENDING_KABID, Document::STATUS_PENDING_DIREKTUR, Document::STATUS_DRAFT, Document::STATUS_REJECTED,
            Document::STATUS_APPROVED, Document::STATUS_ARCHIVED,
        ];

        foreach ($titles as $i => $title) {
            $author = $staff[$i % $staff->count()];
            $unit = $units->firstWhere('id', $author->unit_id);
            $status = $statuses[$i % count($statuses)];
            $createdAt = now()->subDays(170 - $i * 7)->setTime(9 + $i % 7, 15);
            $category = $categories[$i % $categories->count()];

            $document = Document::create([
                'title' => $title,
                'description' => 'Dokumen contoh untuk '.strtolower($title).'.',
                'company_id' => $company->id,
                'department_id' => $unit->department_id,
                'unit_id' => $unit->id,
                'category_id' => $category->id,
                'jenis_surat' => $this->jenisPerKategori[$category->prefix],
                'tanggal_surat' => $createdAt->toDateString(),
                'user_id' => $author->id,
                'status' => $status,
                'document_type' => 'file',
                'is_public' => $i % 3 === 0,
                'is_mandatory_read' => $i % 5 === 0,
                'expires_at' => match (true) {
                    $i % 6 === 1 => now()->addDays(10 + $i),
                    $i % 6 === 4 => now()->subDays(5),
                    default => null,
                },
            ]);

            $approvedAt = $status === Document::STATUS_APPROVED ? $createdAt->copy()->addDays(3) : null;

            $document->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addDays(2),
                'approved_at' => $approvedAt,
                'direktur_reviewed_at' => $approvedAt,
            ])->saveQuietly();
        }
    }

    private function romanMonth(int $month): string
    {
        return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$month - 1];
    }
}
