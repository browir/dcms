<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\LetterNumber;
use App\Models\Unit;
use App\Models\User;
use App\Services\DocumentConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentOfficialLetterPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('documents');

        // Seed roles required by Document model events
        Role::firstOrCreate(['name' => 'kabid']);
        Role::firstOrCreate(['name' => 'super_admin']);
        $role = Role::firstOrCreate(['name' => 'staf']);
        $role->givePermissionTo(collect([
            'ViewAny:Document', 'Create:Document', 'View:Document',
            'ViewAny:LetterNumber', 'Create:LetterNumber',
        ])->map(fn ($name) => Permission::firstOrCreate(['name' => $name])));

        $company = Company::create([
            'name' => 'RSU Syifa Medika Banjarbaru',
            'code' => 'RSUSMBJB',
            'kode_surat' => 'RSUSM',
            'is_active' => true,
        ]);

        $department = Department::create([
            'company_id' => $company->id,
            'name' => 'Pelayanan Medis',
            'code' => 'YANMED',
        ]);

        $unit = Unit::create([
            'department_id' => $department->id,
            'name' => 'Instalasi Rawat Inap',
            'prefix' => '1',
        ]);

        $category = DocumentCategory::create([
            'name' => 'Surat Edaran',
            'code' => 'D',
            'prefix' => 'D',
            'kelompok' => 'surat',
            'company_id' => $company->id,
        ]);

        $this->user = User::create([
            'name' => 'Tester PDF',
            'username' => 'testerpdf',
            'email' => 'tester-pdf@example.test',
            'password' => 'password',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);
        $this->user->assignRole($role);

        // Create a letter number first (required by Document creating event)
        $letter = LetterNumber::create([
            'nomor'         => '001/D.1/RSUSM/VI/2026',
            'nomor_urut'    => 1,
            'jenis_surat'   => 'D',
            'company_id'    => $company->id,
            'department_id' => $department->id,
            'unit_id'       => $unit->id,
            'tanggal_surat' => '2026-06-21',
            'judul_surat'   => 'Surat Edaran Jam Pelayanan',
            'created_by'    => null,
        ]);

        $this->actingAs($this->user);

        $this->document = Document::create([
            'title' => 'Surat Edaran Jam Pelayanan',
            'letter_number_id' => $letter->id,
            'document_type' => 'form',
            'content' => '<p style="text-align: center;"><strong>SURAT EDARAN</strong><br>Nomor: {nomor_surat}</p><p>Yth. Seluruh Karyawan</p><p>Dengan hormat, berikut ketentuan jam kerja...</p>',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
        ]);
    }

    public function test_build_html_for_pdf_contains_letterhead_watermark_and_footer(): void
    {
        $html = DocumentConversionService::buildHtmlForPdf($this->document);

        // Header kop image
        $this->assertStringContainsString('header-kop', $html);

        // Background: watermark palang medis + gelombang footer dalam satu gambar (letter-bg)
        // atau class 'watermark' jika fallback digunakan
        $hasBackground = str_contains($html, 'letter-bg') || str_contains($html, 'watermark');
        $this->assertTrue($hasBackground, 'HTML harus mengandung elemen background surat (letter-bg atau watermark)');

        // Substituted nomor surat (should not contain raw placeholder)
        $this->assertStringNotContainsString('{nomor_surat}', $html);
        $this->assertStringContainsString('001/D.1/RSUSM/VI/2026', $html);

        // Should contain the letter body
        $this->assertStringContainsString('Dengan hormat', $html);
    }

    public function test_download_as_pdf_returns_valid_response(): void
    {
        $response = DocumentConversionService::downloadAsPdf($this->document);

        $this->assertNotNull($response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_stream_pdf_route_serves_inline_pdf(): void
    {
        $response = $this->get(route('documents.pdf.stream', $this->document));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_download_pdf_route_serves_attachment(): void
    {
        $response = $this->get(route('documents.pdf.download', $this->document));

        $response->assertOk();
    }
}
