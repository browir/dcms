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

        // Footer slogan image
        $this->assertStringContainsString('footer-slogan', $html);

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

    public function test_signature_block_is_aligned_to_the_right(): void
    {
        // Simulasi konten yang dihasilkan editor: tabel 2 kolom dengan kolom pertama kosong
        $rawContent = '<p>Demikian surat tugas ini diberikan...</p>'
            . '<table><tbody><tr><td rowspan="1" colspan="1"></td><td rowspan="1" colspan="1">Banjarbaru, {tanggal}<br><strong>Direktur,</strong><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>';

        $prepared = DocumentConversionService::prepareLetterContent($rawContent, 'Surat Tugas', '005/G.IT/SGG/XI/2026', '2026-11-23');

        // Pastikan tabel tanda tangan diubah menjadi class letter-signature-table
        $this->assertStringContainsString('letter-signature-table', $prepared);
        $this->assertStringContainsString('letter-signature-block', $prepared);

        // Pastikan kolom spacer 58% ada di sisi kiri dan kolom tanda tangan 42% ada di sisi kanan
        $this->assertStringContainsString('width: 58%', $prepared);
        $this->assertStringContainsString('width: 42%', $prepared);
        $this->assertStringContainsString('dr. Puga Sharaz Wangi, M.A.R.S.', $prepared);
    }

    public function test_standalone_signature_paragraph_is_also_aligned_to_the_right(): void
    {
        $rawContent = '<p>Demikian surat ini dibuat.</p>'
            . '<p>Banjarbaru, 23 November 2026<br><strong>Direktur,</strong><br><br><br><strong><u>dr. Puga Sharaz Wangi</u></strong><br>NIP. 01042019232</p>';

        $prepared = DocumentConversionService::prepareLetterContent($rawContent, 'Surat Resmi', '001/D/2026');

        $this->assertStringContainsString('letter-signature-table', $prepared);
        $this->assertStringContainsString('width: 58%', $prepared);
        $this->assertStringContainsString('width: 42%', $prepared);
    }

    public function test_tables_have_borders_and_header_meta_tables_are_borderless(): void
    {
        $rawContent = '<table><tbody><tr><td style="width:60%;">Nomor : 001/D/2026<br>Perihal : Undangan</td><td style="width:40%;">Banjarbaru, 23 November 2026</td></tr></tbody></table>'
            . '<p>Dengan ini memberikan tugas kepada:</p>'
            . '<table><tbody><tr><th>Nama</th><th>Jabatan</th></tr><tr><td>Budi</td><td>Staf</td></tr></tbody></table>';

        $prepared = DocumentConversionService::prepareLetterContent($rawContent, 'Surat Undangan', '001/D/2026');

        // Header meta table diberi class letter-meta-table
        $this->assertStringContainsString('letter-meta-table', $prepared);

        // PDF HTML memuat border CSS untuk tabel
        $html = DocumentConversionService::buildHtmlForPdf($this->document);
        $this->assertStringContainsString('border: 1px solid #333', $html);
        $this->assertStringContainsString('table.letter-meta-table', $html);
    }

    public function test_combined_spt_and_sppd_contains_both_pages_and_page_break(): void
    {
        $rawCombinedContent = '<p style="text-align: center;"><strong>SURAT PERINTAH TUGAS</strong><br>Nomor: {nomor_surat}</p>'
            . '<p>Dengan ini memberikan tugas...</p>'
            . '<table class="letter-signature-table"><tbody><tr><td style="width: 58%;">&nbsp;</td><td class="letter-signature-block">Banjarbaru, {tanggal}<br>Direktur</td></tr></tbody></table>'
            . '<div style="page-break-before: always; break-before: page;"></div>'
            . '<p style="text-align: center;"><strong>SURAT PERINTAH PERJALANAN DINAS (SPPD)</strong><br>Nomor: {nomor_surat}</p>'
            . '<table><tbody><tr><td>Pejabat pemberi perintah</td><td>Direktur</td></tr></tbody></table>'
            . '<table class="letter-signature-table"><tbody><tr><td style="width: 58%;">&nbsp;</td><td class="letter-signature-block">Banjarbaru, {tanggal}<br>Direktur</td></tr></tbody></table>';

        $prepared = DocumentConversionService::prepareLetterContent(
            $rawCombinedContent,
            'Surat Perintah Tugas (SPT) & SPPD',
            '005/G.IT/SGG/XI/2026',
            '2026-11-23'
        );

        // Memuat Surat Perintah Tugas dan Surat Perintah Perjalanan Dinas
        $this->assertStringContainsString('SURAT PERINTAH TUGAS', $prepared);
        $this->assertStringContainsString('SURAT PERINTAH PERJALANAN DINAS (SPPD)', $prepared);

        // Memuat page break pemisah halaman
        $this->assertStringContainsString('page-break-before: always', $prepared);

        // Nomor surat terisi di kedua halaman
        $this->assertEquals(2, substr_count($prepared, '005/G.IT/SGG/XI/2026'));
    }
}
