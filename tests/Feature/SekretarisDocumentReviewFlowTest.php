<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\DocumentSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SekretarisDocumentReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $sekretaris;
    private User $staf;
    private User $kabid;
    private User $direktur;
    private Company $company;
    private Department $department;
    private Unit $unit;
    private DocumentCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('documents');

        $roleSekretaris = Role::firstOrCreate(['name' => 'Sekretaris']);
        $roleKabid = Role::firstOrCreate(['name' => 'kabid']);
        $roleDirektur = Role::firstOrCreate(['name' => 'direktur']);
        $roleStaf = Role::firstOrCreate(['name' => 'staf']);

        $permissions = collect([
            'ViewAny:Document', 'Create:Document', 'View:Document', 'Update:Document',
            'ViewAny:LetterNumber', 'Create:LetterNumber',
        ])->map(fn ($name) => Permission::firstOrCreate(['name' => $name]));

        $roleSekretaris->syncPermissions($permissions);
        $roleStaf->syncPermissions($permissions);

        $this->company = Company::create([
            'name' => 'RSU Syifa Medika Banjarbaru',
            'code' => 'RSUSMBJB',
            'kode_surat' => 'RSUSM',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Sekretariat & Tata Usaha',
            'code' => 'SEKR',
        ]);

        $this->unit = Unit::create([
            'department_id' => $this->department->id,
            'name' => 'Unit Sekretariat',
            'prefix' => '01',
        ]);

        $this->category = DocumentCategory::create([
            'name' => 'Surat Edaran',
            'code' => 'D',
            'prefix' => 'D',
            'kelompok' => 'surat',
            'company_id' => $this->company->id,
        ]);

        $this->sekretaris = User::create([
            'name' => 'Sekretaris Direksi',
            'username' => 'sekretaris',
            'email' => 'sekretaris@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        $this->sekretaris->assignRole($roleSekretaris);

        $this->staf = User::create([
            'name' => 'Staf Pelayanan',
            'username' => 'stafuser',
            'email' => 'staf@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        $this->staf->assignRole($roleStaf);

        $this->kabid = User::create([
            'name' => 'Kepala Bidang',
            'username' => 'kabiduser',
            'email' => 'kabid@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        $this->kabid->assignRole($roleKabid);

        $this->direktur = User::create([
            'name' => 'Direktur Utama',
            'username' => 'direkturuser',
            'email' => 'direktur@example.test',
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        $this->direktur->assignRole($roleDirektur);
    }

    public function test_document_by_sekretaris_submits_directly_to_direktur(): void
    {
        $letter = LetterNumber::create([
            'nomor'         => '001/D.01/RSUSM/XI/2026',
            'nomor_urut'    => 1,
            'jenis_surat'   => 'D',
            'company_id'    => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id'       => $this->unit->id,
            'tanggal_surat' => '2026-11-23',
            'judul_surat'   => 'Surat Tugas Eksternal',
        ]);

        $this->actingAs($this->sekretaris);

        $document = Document::create([
            'title' => 'Surat Tugas Eksternal',
            'letter_number_id' => $letter->id,
            'document_type' => 'form',
            'content' => '<p>Isi surat...</p>',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'user_id' => $this->sekretaris->id,
            'status' => 'draft',
        ]);

        // Submit for review
        $document->submitForReview();

        // Status langsung pending_direktur (bukan pending_kabid!)
        $this->assertEquals(Document::STATUS_PENDING_DIREKTUR, $document->fresh()->status);

        // Notifikasi dikirimkan langsung ke Direktur, bukan ke Kabid
        Notification::assertSentTo($this->direktur, DocumentSubmittedNotification::class);
        Notification::assertNotSentTo($this->kabid, DocumentSubmittedNotification::class);
    }

    public function test_document_by_staf_submits_to_kabid_first(): void
    {
        $letter = LetterNumber::create([
            'nomor'         => '002/D.01/RSUSM/XI/2026',
            'nomor_urut'    => 2,
            'jenis_surat'   => 'D',
            'company_id'    => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id'       => $this->unit->id,
            'tanggal_surat' => '2026-11-23',
            'judul_surat'   => 'Laporan Rutin Staf',
        ]);

        $this->actingAs($this->staf);

        $document = Document::create([
            'title' => 'Laporan Rutin Staf',
            'letter_number_id' => $letter->id,
            'document_type' => 'form',
            'content' => '<p>Isi laporan staf...</p>',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'user_id' => $this->staf->id,
            'status' => 'draft',
        ]);

        $document->submitForReview();

        // Staf reguler harus melalui Kabid (pending_kabid)
        $this->assertEquals(Document::STATUS_PENDING_KABID, $document->fresh()->status);

        Notification::assertSentTo($this->kabid, DocumentSubmittedNotification::class);
        Notification::assertNotSentTo($this->direktur, DocumentSubmittedNotification::class);
    }

    public function test_sekretaris_resubmitted_document_goes_directly_to_direktur(): void
    {
        $letter = LetterNumber::create([
            'nomor'         => '003/D.01/RSUSM/XI/2026',
            'nomor_urut'    => 3,
            'jenis_surat'   => 'D',
            'company_id'    => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id'       => $this->unit->id,
            'tanggal_surat' => '2026-11-23',
            'judul_surat'   => 'Surat Revisi',
        ]);

        $this->actingAs($this->sekretaris);

        $document = Document::create([
            'title' => 'Surat Revisi',
            'letter_number_id' => $letter->id,
            'document_type' => 'form',
            'content' => '<p>Isi revisi...</p>',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $this->unit->id,
            'category_id' => $this->category->id,
            'user_id' => $this->sekretaris->id,
            'status' => 'rejected',
        ]);

        $document->resubmit();

        $this->assertEquals(Document::STATUS_PENDING_DIREKTUR, $document->fresh()->status);
        Notification::assertSentTo($this->direktur, DocumentSubmittedNotification::class);
    }
}

