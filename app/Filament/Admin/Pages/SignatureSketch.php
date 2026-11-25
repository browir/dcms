<?php

namespace App\Filament\Admin\Pages;

use App\Models\Company;
use App\Models\Signature;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class SignatureSketch extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static string|\UnitEnum|null $navigationGroup = 'Dokumen';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Sketch Tanda Tangan';

    protected static ?string $title = 'Sketch & Tanda Tangan Pejabat';

    protected static ?string $slug = 'signature-sketch';

    protected string $view = 'filament.admin.pages.signature-sketch';

    public ?array $data = [];

    public ?string $signature_data = null; // Base64 data from signature pad

    public $uploaded_file = null; // File upload alternative

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin') || $user->can('manage_signature_sketch');
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $companyId = $user?->company_id;

        $activeSig = Signature::where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        $defaultName = $activeSig?->name ?? $user?->name ?? '';
        $defaultNip = $activeSig?->nip ?? $user?->nip ?? '';
        $defaultPos = $activeSig?->position ?? ($user?->hasRole('direktur') ? 'Direktur' : ($user?->unit?->name ?? 'Pegawai'));

        $this->form->fill([
            'name' => $defaultName,
            'nip' => $defaultNip,
            'position' => $defaultPos,
            'company_id' => $companyId,
            'is_active' => true,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('profile_template')
                    ->label('Profil Pejabat (Isi Otomatis)')
                    ->placeholder('Pilih profil pejabat untuk mengisi data otomatis...')
                    ->options(function () {
                        $opts = [
                            'puga' => 'dr. Puga Sharaz Wangi, M.A.R.S. — Direktur (NIP: 01042019232)',
                        ];
                        /** @var User|null $user */
                        $user = Auth::user();
                        if ($user && $user->name !== 'dr. Puga Sharaz Wangi, M.A.R.S.') {
                            $userNip = $user->nip ? " (NIP: {$user->nip})" : '';
                            $userPos = $user->hasRole('direktur') ? 'Direktur' : ($user->unit?->name ?? $user->department?->name ?? 'Pegawai');
                            $opts['self'] = "{$user->name} — {$userPos}{$userNip} (Profil Akun Saya)";
                        }
                        $opts['custom'] = 'Profil Lain / Input Manual...';
                        return $opts;
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->dehydrated(false)
                    ->prefixIcon('heroicon-o-sparkles')
                    ->helperText('Pilih salah satu profil pejabat untuk langsung mengisi Nama, NIP, dan Jabatan secara otomatis.')
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state === 'puga') {
                            $set('name', 'dr. Puga Sharaz Wangi, M.A.R.S.');
                            $set('nip', '01042019232');
                            $set('position', 'Direktur');
                            $pugaCompany = Company::where('name', 'like', '%RSU Syifa Medika Banjarbaru%')->value('id');
                            if ($pugaCompany) {
                                $set('company_id', $pugaCompany);
                            }
                        } elseif ($state === 'self') {
                            /** @var User|null $user */
                            $user = Auth::user();
                            if ($user) {
                                $set('name', $user->name);
                                $set('nip', $user->nip ?? '');
                                $set('position', $user->hasRole('direktur') ? 'Direktur' : ($user->unit?->name ?? $user->department?->name ?? 'Pegawai'));
                                if ($user->company_id) {
                                    $set('company_id', $user->company_id);
                                }
                            }
                        } elseif ($state === 'custom') {
                            $set('name', '');
                            $set('nip', '');
                            $set('position', '');
                        }
                    }),

                TextInput::make('name')
                    ->label('Nama Lengkap & Gelar Pejabat')
                    ->placeholder('Contoh: dr. Puga Sharaz Wangi, M.A.R.S.')
                    ->required()
                    ->prefixIcon('heroicon-o-user')
                    ->helperText('Nama ini akan menjadi template penandatangan pada surat resmi.'),

                TextInput::make('nip')
                    ->label('NIP / Nomor Pegawai')
                    ->placeholder('Contoh: 01042019232')
                    ->prefixIcon('heroicon-o-identification'),

                TextInput::make('position')
                    ->label('Jabatan')
                    ->placeholder('Contoh: Direktur / Plt. Direktur')
                    ->required()
                    ->default('Direktur')
                    ->prefixIcon('heroicon-o-briefcase'),

                Select::make('company_id')
                    ->label('Perusahaan / Unit')
                    ->placeholder('Semua Perusahaan')
                    ->options(fn () => Company::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->prefixIcon('heroicon-o-building-office'),


            ])
            ->statePath('data');
    }

    public function saveSignature(): void
    {
        $formData = $this->form->getState();

        $name = $formData['name'];
        $nip = $formData['nip'] ?? null;
        $position = $formData['position'];
        $companyId = $formData['company_id'] ?? null;
        $isActive = true; // selalu aktif, hanya ada 1 tanda tangan per akun

        $signaturePath = null;

        if ($this->uploaded_file) {
            // Simpan file unggahan
            $this->validate([
                'uploaded_file' => 'image|max:3072',
            ], [
                'uploaded_file.image' => 'File tanda tangan harus berupa gambar (PNG/JPG).',
                'uploaded_file.max' => 'Ukuran file tanda tangan maksimal 3MB.',
            ]);

            $filename = 'sig_' . time() . '_' . uniqid() . '.' . $this->uploaded_file->getClientOriginalExtension();
            $signaturePath = $this->uploaded_file->storeAs('signatures', $filename, 'public');
        } elseif (! empty($this->signature_data)) {
            // Simpan gambar dari canvas (base64 data URL)
            $data = $this->signature_data;
            if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
                $data = substr($data, strpos($data, ',') + 1);
                $type = strtolower($type[1]);
                $decoded = base64_decode($data);

                if ($decoded === false) {
                    Notification::make()
                        ->title('Gagal Memproses Goresan')
                        ->body('Data goresan tanda tangan tidak valid.')
                        ->danger()
                        ->send();

                    return;
                }

                $filename = 'sig_' . time() . '_' . uniqid() . '.png';
                Storage::disk('public')->put('signatures/' . $filename, $decoded);
                $signaturePath = 'signatures/' . $filename;
            }
        }

        if (! $signaturePath) {
            Notification::make()
                ->title('Tanda Tangan Belum Ada')
                ->body('Harap goreskan tanda tangan pada canvas sketch atau unggah file gambar tanda tangan.')
                ->warning()
                ->send();

            return;
        }

        // Hapus semua tanda tangan lama milik akun ini (1 akun = 1 tanda tangan)
        $oldSignatures = Signature::where('user_id', Auth::id())->get();
        foreach ($oldSignatures as $old) {
            if ($old->signature_path && Storage::disk('public')->exists($old->signature_path)) {
                Storage::disk('public')->delete($old->signature_path);
            }
            $old->delete();
        }

        $signature = Signature::create([
            'user_id'        => Auth::id(),
            'company_id'     => $companyId,
            'name'           => $name,
            'nip'            => $nip,
            'position'       => $position,
            'signature_path' => $signaturePath,
            'is_active'      => true, // selalu aktif, karena hanya ada 1
        ]);

        // Simpan juga ke user yang login jika relevan
        /** @var User|null $user */
        $user = Auth::user();
        if ($user && ($user->name === $name || $user->hasRole('direktur'))) {
            $user->update(['signature_path' => $signaturePath]);
        }

        // Reset input canvas & upload
        $this->signature_data = null;
        $this->uploaded_file = null;
        $this->dispatch('signature-saved');

        Notification::make()
            ->title('Tanda Tangan Berhasil Disimpan')
            ->body("Tanda tangan untuk {$signature->name} ({$signature->position}) telah berhasil disimpan pada akun Anda.")
            ->success()
            ->send();
    }

    public function setActiveSignature(int $id): void
    {
        $sig = Signature::where('user_id', Auth::id())->find($id);
        if (! $sig) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda hanya dapat mengelola tanda tangan milik akun Anda sendiri.')
                ->danger()
                ->send();

            return;
        }

        Signature::query()
            ->where('user_id', Auth::id())
            ->when($sig->company_id, fn ($q) => $q->where('company_id', $sig->company_id))
            ->update(['is_active' => false]);

        $sig->update(['is_active' => true]);

        Notification::make()
            ->title('Status Tanda Tangan Diperbarui')
            ->body("Tanda tangan {$sig->name} sekarang menjadi tanda tangan aktif utama Anda.")
            ->success()
            ->send();
    }

    public function deleteSignature(int $id): void
    {
        $sig = Signature::where('user_id', Auth::id())->find($id);
        if (! $sig) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda hanya dapat menghapus tanda tangan milik akun Anda sendiri.')
                ->danger()
                ->send();

            return;
        }

        if ($sig->signature_path && Storage::disk('public')->exists($sig->signature_path)) {
            Storage::disk('public')->delete($sig->signature_path);
        }

        $sig->delete();

        Notification::make()
            ->title('Tanda Tangan Dihapus')
            ->body('Data tanda tangan berhasil dihapus.')
            ->success()
            ->send();
    }

    public function getSignaturesProperty()
    {
        return Signature::with(['company', 'user'])
            ->where('user_id', Auth::id())
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();
    }
}
