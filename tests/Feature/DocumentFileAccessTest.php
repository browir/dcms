<?php

namespace Tests\Feature;

use App\Enum\JenisSurat;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** File dokumen resmi ada di disk privat: hanya user berhak atau link bertanda tangan yang bisa mengunduh. */
class DocumentFileAccessTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Department $department;

    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('documents');
        Storage::disk('documents')->put('documents/SK Rahasia.pdf', '%PDF-1.4 rahasia');

        Role::findOrCreate('kabid', 'web');
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('staf', 'web')->givePermissionTo(Permission::findOrCreate('View:Document', 'web'));

        $this->company = Company::create(['name' => 'PT Syifa Global Group', 'code' => 'SYIFA', 'kode_surat' => 'SGG']);
        $this->department = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $this->company->id]);
        $unit = Unit::create(['name' => 'Kepegawaian', 'prefix' => '2', 'department_id' => $this->department->id]);

        $this->document = Document::create([
            'title' => 'SK Rahasia',
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'unit_id' => $unit->id,
            'category_id' => DocumentCategory::create(['name' => 'SK', 'prefix' => 'SK', 'company_id' => $this->company->id])->id,
            'user_id' => $this->user('penulis', $this->department)->id,
            'jenis_surat' => JenisSurat::SURAT_KEPUTUSAN,
            'tanggal_surat' => '2026-10-21',
            'document_type' => 'file',
            'file_path' => 'documents/SK Rahasia.pdf',
            'is_public' => false,
        ]);
    }

    private function user(string $username, ?Department $department): User
    {
        $user = User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => 'password',
            'company_id' => $this->company->id,
            'department_id' => $department?->id,
        ]);
        $user->assignRole('staf');

        return $user;
    }

    public function test_tamu_tidak_bisa_mengunduh(): void
    {
        $this->get(route('documents.serve', ['path' => 'documents/SK Rahasia.pdf']))->assertRedirect();
    }

    public function test_user_berhak_bisa_mengunduh(): void
    {
        $sameDepartment = $this->user('rekan', $this->department);

        $this->actingAs($sameDepartment)
            ->get(route('documents.serve', ['path' => 'documents/SK Rahasia.pdf']))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_user_departemen_lain_ditolak(): void
    {
        $other = $this->user('lain', Department::create(['name' => 'Keuangan', 'code' => 'KEU', 'company_id' => $this->company->id]));

        $this->actingAs($other)
            ->get(route('documents.serve', ['path' => 'documents/SK Rahasia.pdf']))
            ->assertNotFound();
    }

    public function test_path_yang_bukan_file_dokumen_ditolak(): void
    {
        Storage::disk('documents')->put('documents/lain.pdf', '%PDF');

        $this->actingAs($this->user('rekan', $this->department))
            ->get(route('documents.serve', ['path' => 'documents/lain.pdf']))
            ->assertNotFound();
    }

    public function test_link_bertanda_tangan_untuk_api(): void
    {
        $signed = URL::temporarySignedRoute('documents.serve.signed', now()->addHour(), ['path' => 'documents/SK Rahasia.pdf']);

        $this->get($signed)->assertOk();
        $this->get(route('documents.serve.signed', ['path' => 'documents/SK Rahasia.pdf']))->assertForbidden();
    }
}
