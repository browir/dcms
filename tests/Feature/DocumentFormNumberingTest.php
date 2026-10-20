<?php

namespace Tests\Feature;

use App\Enum\JenisSurat;
use App\Filament\Admin\Resources\Documents\Pages\CreateDocument;
use App\Filament\Admin\Resources\LetterNumbers\Pages\ListLetterNumbers;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Alur di panel admin: ambil nomor surat dulu, lalu isi dokumen resminya ke nomor itu. */
class DocumentFormNumberingTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    private DocumentCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-19 10:00');
        Notification::fake();
        Storage::fake('documents');

        Role::firstOrCreate(['name' => 'kabid']);
        Role::firstOrCreate(['name' => 'super_admin']);
        // Staf biasa: hanya izin dokumen — izin nomor surat mengikuti izin membuat dokumen.
        $role = Role::firstOrCreate(['name' => 'staf']);
        $role->givePermissionTo(collect(['ViewAny:Document', 'Create:Document'])
            ->map(fn ($name) => Permission::firstOrCreate(['name' => $name])));

        $company = Company::create(['name' => 'PT Syifa Global Group', 'code' => 'SYIFA', 'kode_surat' => 'SGG']);
        $department = Department::create(['name' => 'Umum', 'code' => 'UMM', 'company_id' => $company->id]);
        $this->unit = Unit::create(['name' => 'Kepegawaian', 'prefix' => '2', 'department_id' => $department->id]);
        $this->category = DocumentCategory::create(['name' => 'SOP', 'prefix' => 'SOP', 'company_id' => $company->id]);

        $user = User::create([
            'name' => 'Penulis',
            'username' => 'penulis',
            'email' => 'penulis@example.test',
            'password' => 'password',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);
    }

    public function test_ambil_nomor_lalu_isi_dokumen_ke_nomor_itu(): void
    {
        // 1) Ambil nomor surat di halaman Nomor Surat.
        Livewire::test(ListLetterNumbers::class)
            ->callAction('create', [
                'jenis_surat' => JenisSurat::SOP->value,
                'unit_id' => $this->unit->id,
                'tanggal_surat' => '2026-07-15',
                'judul_surat' => 'SOP Pengarsipan',
                'perihal' => 'Pengelolaan arsip unit',
            ])
            ->assertHasNoActionErrors();

        $letter = LetterNumber::query()->sole();
        $this->assertSame('001/SOP.2/SGG/VII/2026', $letter->nomor);

        // 2) "Isi Dokumen": form terbuka dengan nomor terpilih dan field yang mengikuti nomor terisi.
        Livewire::withQueryParams(['nomor_surat' => $letter->id])
            ->test(CreateDocument::class)
            ->assertSchemaStateSet([
                'letter_number_id' => $letter->id,
                'jenis_surat' => JenisSurat::SOP->value,
                'tanggal_surat' => '2026-07-15',
                'title' => 'SOP Pengarsipan',
            ])
            ->fillForm([
                'document_type' => 'file',
                'category_id' => $this->category->id,
                'file_path' => UploadedFile::fake()->create('sop.pdf', 20, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $document = Document::query()->sole();

        $this->assertSame($letter->id, $document->letter_number_id);
        $this->assertSame('001/SOP.2/SGG/VII/2026', $document->code_number);
        $this->assertSame(0, LetterNumber::query()->available()->count());
    }

    public function test_dokumen_tidak_bisa_disimpan_tanpa_nomor_surat(): void
    {
        Livewire::test(CreateDocument::class)
            ->fillForm([
                'document_type' => 'file',
                'title' => 'Tanpa nomor',
                'category_id' => $this->category->id,
                'file_path' => UploadedFile::fake()->create('x.pdf', 20, 'application/pdf'),
            ])
            ->call('create')
            ->assertHasFormErrors(['letter_number_id' => 'required']);

        $this->assertSame(0, Document::query()->count());
    }
}
