<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\SignatureSketch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Signature;
use App\Models\User;
use App\Services\DocumentConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SignatureSketchAndSignerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'manage_signature_sketch', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    public function test_prepare_letter_content_replaces_custom_signer_and_title(): void
    {
        $templateContent = '<p>Isi surat...</p><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>{jabatan},</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>';

        $rendered = DocumentConversionService::prepareLetterContent(
            $templateContent,
            'Surat Tugas',
            '001/ST/XI/2026',
            '2026-11-24',
            'dr. Budi Santoso, Sp.B.',
            '198501012010011001',
            'Plt. Direktur'
        );

        $this->assertStringContainsString('dr. Budi Santoso, Sp.B.', $rendered);
        $this->assertStringContainsString('198501012010011001', $rendered);
        $this->assertStringContainsString('Plt. Direktur', $rendered);
        $this->assertStringNotContainsString('{nama_direktur}', $rendered);
        $this->assertStringNotContainsString('{nip_direktur}', $rendered);
    }

    public function test_align_signature_block_with_custom_signer_title(): void
    {
        $html = '<p>Isi surat resmi...</p><p>Banjarbaru, 24 November 2026</p><p><strong>Plt. Direktur,</strong></p><p><br><br></p><p><strong><u>dr. Ahmad Fauzi</u></strong></p><p>NIP. 12345678</p>';

        $aligned = DocumentConversionService::alignSignatureBlockToRight($html);

        $this->assertStringContainsString('letter-signature-table', $aligned);
        $this->assertStringContainsString('letter-signature-block', $aligned);
        $this->assertStringContainsString('dr. Ahmad Fauzi', $aligned);
        $this->assertStringContainsString('Plt. Direktur', $aligned);
    }

    public function test_signature_sketch_page_can_access_permission(): void
    {
        $company = Company::create(['name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSM', 'is_active' => true]);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $pegawaiRole = Role::firstOrCreate(['name' => 'pegawai', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create(['company_id' => $company->id]);
        $superAdmin->assignRole($superAdminRole);

        $authorizedUser = User::factory()->create(['company_id' => $company->id]);
        $authorizedUser->givePermissionTo('manage_signature_sketch');

        $regularUser = User::factory()->create(['company_id' => $company->id]);
        $regularUser->assignRole($pegawaiRole);

        // Super Admin access
        $this->actingAs($superAdmin);
        $this->assertTrue(SignatureSketch::canAccess());

        // User with custom permission
        $this->actingAs($authorizedUser);
        $this->assertTrue(SignatureSketch::canAccess());

        // Regular user without permission
        $this->actingAs($regularUser);
        $this->assertFalse(SignatureSketch::canAccess());

        // HTTP GET verification (verifies sidebar and navigation render with no icon conflict)
        $this->actingAs($superAdmin);
        $response = $this->get('/admin/signature-sketch');
        $response->assertSuccessful();
    }

    public function test_signature_model_creation_and_active_toggle(): void
    {
        $company = Company::create(['name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSM', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $sig1 = Signature::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'name' => 'dr. Puga Sharaz Wangi, M.A.R.S.',
            'nip' => '01042019232',
            'position' => 'Direktur',
            'signature_path' => 'signatures/sig1.png',
            'is_active' => true,
        ]);

        $this->assertTrue($sig1->is_active);

        $sig2 = Signature::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'name' => 'dr. Budi Santoso',
            'nip' => '123456',
            'position' => 'Plt. Direktur',
            'signature_path' => 'signatures/sig2.png',
            'is_active' => false,
        ]);

        $this->assertFalse($sig2->is_active);
    }

    public function test_document_model_saves_and_uses_signer_fields_in_pdf(): void
    {
        $company = Company::create(['name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSM', 'is_active' => true]);
        $department = \App\Models\Department::create(['name' => 'Direksi', 'code' => 'DIR', 'company_id' => $company->id]);
        $unit = \App\Models\Unit::create(['name' => 'Sekretariat', 'code' => 'SEK', 'department_id' => $department->id]);
        $category = DocumentCategory::create(['name' => 'Surat Edaran', 'company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'unit_id' => $unit->id]);

        $doc = Document::create([
            'title' => 'Surat Edaran Jam Kerja',
            'code_number' => '050/SE/RSUSM/XI/2026',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'document_type' => 'form',
            'status' => 'approved',
            'signer_name' => 'dr. Siti Rahmah, Sp.A.',
            'signer_nip' => '199002022015032002',
            'signer_title' => 'Wakil Direktur Pelayanan',
            'content' => '<p>Diberitahukan jam kerja baru.</p><br><table class="letter-signature-table" style="width: 100%; border: none;"><tbody><tr><td style="width: 58%; border: none;">&nbsp;</td><td class="letter-signature-block" style="width: 42%; border: none; text-align: left;">Banjarbaru, {tanggal}<br><strong>{jabatan},</strong><br><br><br><br><strong><u>{nama_direktur}</u></strong><br>NIP. {nip_direktur}</td></tr></tbody></table>',
        ]);

        $this->assertEquals('dr. Siti Rahmah, Sp.A.', $doc->signer_name);
        $this->assertEquals('199002022015032002', $doc->signer_nip);
        $this->assertEquals('Wakil Direktur Pelayanan', $doc->signer_title);

        $html = DocumentConversionService::buildHtmlForPdf($doc);
        $this->assertStringContainsString('dr. Siti Rahmah, Sp.A.', $html);
        $this->assertStringContainsString('199002022015032002', $html);
        $this->assertStringContainsString('Wakil Direktur Pelayanan', $html);
    }

    public function test_signature_sketch_only_lists_and_manages_creator_own_signatures(): void
    {
        $company = Company::create(['name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSM', 'is_active' => true]);
        $userA = User::factory()->create(['company_id' => $company->id]);
        $userB = User::factory()->create(['company_id' => $company->id]);

        $sigA = Signature::create([
            'user_id' => $userA->id,
            'company_id' => $company->id,
            'name' => 'Tanda Tangan User A',
            'position' => 'Kepala Unit A',
            'signature_path' => 'signatures/sigA.png',
            'is_active' => true,
        ]);

        $sigB = Signature::create([
            'user_id' => $userB->id,
            'company_id' => $company->id,
            'name' => 'Tanda Tangan User B',
            'position' => 'Kepala Unit B',
            'signature_path' => 'signatures/sigB.png',
            'is_active' => true,
        ]);

        // User A tests
        $this->actingAs($userA);
        $pageA = new SignatureSketch();
        $userASignatures = $pageA->getSignaturesProperty();

        $this->assertTrue($userASignatures->contains('id', $sigA->id));
        $this->assertFalse($userASignatures->contains('id', $sigB->id));

        // User A mencoba mengaktifkan tanda tangan User B -> ditolak
        $pageA->setActiveSignature($sigB->id);
        $this->assertTrue($sigA->fresh()->is_active);

        // User A mencoba menghapus tanda tangan User B -> ditolak
        $pageA->deleteSignature($sigB->id);
        $this->assertNotNull(Signature::find($sigB->id));

        // User A menghapus tanda tangannya sendiri -> berhasil
        $pageA->deleteSignature($sigA->id);
        $this->assertNull(Signature::find($sigA->id));
    }

    public function test_document_stamp_option_injects_hospital_stamp_into_pdf(): void
    {
        $company = Company::create(['name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSM', 'is_active' => true]);
        $department = \App\Models\Department::create(['name' => 'Direksi', 'code' => 'DIR', 'company_id' => $company->id]);
        $unit = \App\Models\Unit::create(['name' => 'Sekretariat', 'code' => 'SEK', 'department_id' => $department->id]);
        $category = DocumentCategory::create(['name' => 'Surat Edaran', 'company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'unit_id' => $unit->id]);

        $docWithStamp = Document::create([
            'title' => 'Surat Resmi Berstempel',
            'code_number' => '100/SE/RSUSM/XI/2026',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'document_type' => 'form',
            'status' => 'approved',
            'has_stamp' => true,
            'signer_name' => 'dr. Puga Sharaz Wangi, M.A.R.S.',
            'signer_title' => 'Direktur',
            'signer_nip' => '01042019232',
            'content' => '<p>Isi surat resmi...</p><p>Banjarbaru, 24 November 2026</p><p>Direktur,</p><p><br><br></p><p><strong><u>dr. Puga Sharaz Wangi, M.A.R.S.</u></strong></p><p>NIP. 01042019232</p>',
        ]);

        $html = DocumentConversionService::buildHtmlForPdf($docWithStamp);

        $this->assertStringContainsString('letter-sig-stamp-container', $html);
        $this->assertStringContainsString('letter-stamp-img', $html);
        $this->assertStringContainsString('Stempel Resmi', $html);
    }

    public function test_signature_can_only_be_used_by_creator_account_on_document_pdf(): void
    {
        $company = Company::create(['name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSM', 'is_active' => true]);
        $department = \App\Models\Department::create(['name' => 'Direksi', 'code' => 'DIR', 'company_id' => $company->id]);
        $unit = \App\Models\Unit::create(['name' => 'Sekretariat', 'code' => 'SEK', 'department_id' => $department->id]);
        $category = DocumentCategory::create(['name' => 'Surat Edaran', 'company_id' => $company->id]);
        
        $userA = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'unit_id' => $unit->id]);
        $userB = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'unit_id' => $unit->id]);

        $sigA = Signature::create([
            'user_id' => $userA->id,
            'company_id' => $company->id,
            'name' => 'dr. User A',
            'position' => 'Direktur',
            'signature_path' => 'signatures/sig_user_a.png',
            'is_active' => true,
        ]);

        // Simpan file dummy agar is_file valid
        \Illuminate\Support\Facades\Storage::disk('public')->put('signatures/sig_user_a.png', 'dummy-data');

        // Kasus 1: Dokumen dibuat oleh User A sendiri -> tanda tangan digital disematkan
        $docUserA = Document::create([
            'title' => 'Surat User A',
            'code_number' => '101/SE/RSUSM/XI/2026',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'user_id' => $userA->id,
            'created_by' => $userA->id,
            'document_type' => 'form',
            'status' => 'approved',
            'signature_id' => $sigA->id,
            'signer_name' => 'dr. User A',
            'signer_title' => 'Direktur',
            'content' => '<p>Isi surat...</p><p>Banjarbaru, 24 November 2026</p><p>Direktur,</p><p><br><br></p><p><strong><u>dr. User A</u></strong></p>',
        ]);

        $htmlA = DocumentConversionService::buildHtmlForPdf($docUserA);
        $this->assertStringContainsString('alt="Tanda Tangan"', $htmlA);

        // Kasus 2: Dokumen dibuat oleh User B memakai signature User A -> ditolak/diabaikan
        $docUserB = Document::create([
            'title' => 'Surat User B',
            'code_number' => '102/SE/RSUSM/XI/2026',
            'company_id' => $company->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'user_id' => $userB->id,
            'created_by' => $userB->id,
            'document_type' => 'form',
            'status' => 'approved',
            'signature_id' => $sigA->id, // Tanda tangan milik User A
            'signer_name' => 'dr. User A',
            'signer_title' => 'Direktur',
            'content' => '<p>Isi surat...</p><p>Banjarbaru, 24 November 2026</p><p>Direktur,</p><p><br><br></p><p><strong><u>dr. User A</u></strong></p>',
        ]);

        $htmlB = DocumentConversionService::buildHtmlForPdf($docUserB);
        // User B TIDAK boleh bisa membubuhkan tanda tangan User A
        $this->assertStringNotContainsString('alt="Tanda Tangan"', $htmlB);
    }

    public function test_document_template_has_stamp_toggle(): void
    {
        $company = Company::create(['name' => 'RSU Syifa Medika Banjarbaru', 'code' => 'RSUSM', 'is_active' => true]);
        $category = DocumentCategory::create(['name' => 'Surat Edaran', 'company_id' => $company->id]);

        $template = \App\Models\DocumentTemplate::create([
            'name' => 'Template Surat Edaran Berstempel',
            'code' => 'SE-STEMPEL',
            'company_id' => $company->id,
            'category_id' => $category->id,
            'template_content' => '<p>Konten template...</p>',
            'template_fields' => [],
            'has_stamp' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($template->has_stamp);
    }
}
