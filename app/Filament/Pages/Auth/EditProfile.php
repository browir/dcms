<?php

namespace App\Filament\Pages\Auth;

use App\Models\Company;
use App\Models\Department;
use App\Models\Unit;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditProfile extends BaseEditProfile
{
    protected string $view = 'filament.custom-edit-profile';

    public static function isSimple(): bool
    {
        return false;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    // Nonaktifkan form actions bawaan Filament — tombol dirender manual
    // di custom view, setelah card 2FA (paling bawah halaman).
    protected function getFormActions(): array
    {
        return [];
    }

    // ── Form ──────────────────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('primary')
                    ->description('Data identitas dan kredensial akun Anda')
                    ->schema([
                        Grid::make(2)->schema([
                            $this->getNameFormComponent()
                                ->label('Nama Lengkap')
                                ->placeholder('Masukkan nama lengkap'),

                            TextInput::make('username')
                                ->label('Nama Pengguna')
                                ->required()
                                ->unique('users', 'username', ignoreRecord: true)
                                ->minLength(3)
                                ->maxLength(255)
                                ->autocomplete('username')
                                ->placeholder('Masukkan nama pengguna'),
                        ]),

                        Grid::make(2)->schema([
                            $this->getEmailFormComponent()
                                ->label('Alamat Email')
                                ->placeholder('Masukkan alamat email'),

                            TextInput::make('phone')
                                ->label('No. Telepon / WhatsApp')
                                ->tel()
                                ->placeholder('contoh: 08123456789')
                                ->maxLength(20),
                        ]),
                    ]),

                Section::make('Organisasi')
                    ->icon('heroicon-o-building-office-2')
                    ->iconColor('indigo')
                    ->description('Struktur organisasi tempat Anda bernaung')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('company_id')
                                ->label('Perusahaan')
                                ->options(Company::where('is_active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('department_id', null);
                                    $set('unit_id', null);
                                }),

                            Select::make('department_id')
                                ->label('Departemen')
                                ->options(function (callable $get) {
                                    $companyId = $get('company_id');
                                    if (! $companyId) {
                                        return [];
                                    }

                                    return Department::where('company_id', $companyId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('unit_id', null))
                                ->disabled(fn (callable $get) => ! $get('company_id')),

                            Select::make('unit_id')
                                ->label('Unit')
                                ->options(function (callable $get) {
                                    $departmentId = $get('department_id');
                                    if (! $departmentId) {
                                        return [];
                                    }

                                    return Unit::where('department_id', $departmentId)
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->disabled(fn (callable $get) => ! $get('department_id')),
                        ]),
                    ]),

                Section::make('Keamanan')
                    ->icon('heroicon-o-lock-closed')
                    ->iconColor('danger')
                    ->description('Perbarui kata sandi Anda. Kosongkan jika tidak ingin mengubah.')
                    ->schema([
                        Grid::make(2)->schema([
                            $this->getPasswordFormComponent()
                                ->label('Kata Sandi Baru')
                                ->placeholder('Masukkan kata sandi baru'),

                            $this->getPasswordConfirmationFormComponent()
                                ->label('Konfirmasi Kata Sandi')
                                ->placeholder('Ulangi kata sandi baru'),
                        ]),
                    ]),

                Section::make('Informasi Akun')
                    ->icon('heroicon-o-information-circle')
                    ->iconColor('gray')
                    ->description('Informasi akun yang tidak dapat diubah secara mandiri')
                    ->schema([
                        Grid::make(3)->schema([
                            Placeholder::make('roles_display')
                                ->label('Peran / Role')
                                ->content(function () {
                                    $roles = auth()->user()->getRoleNames();
                                    if ($roles->isEmpty()) {
                                        return 'Belum memiliki peran';
                                    }

                                    return new \Illuminate\Support\HtmlString(
                                        $roles->map(function ($role) {
                                            $label = e(ucwords(str_replace('_', ' ', $role)));

                                            return '<span style="'
                                                . 'display:inline-flex;align-items:center;'
                                                . 'padding:2px 10px;'
                                                . 'background:rgb(239 246 255);'
                                                . 'color:rgb(29 78 216);'
                                                . 'border:1px solid rgb(191 219 254);'
                                                . 'border-radius:9999px;'
                                                . 'font-size:12px;font-weight:600;'
                                                . 'letter-spacing:0.01em;'
                                                . 'margin-right:4px;margin-bottom:4px;'
                                                . '">' . $label . '</span>';
                                        })->implode('')
                                    );
                                })
                                ->extraAttributes(['class' => 'profile-roles-display'])
                                ->dehydrated(false),

                            Placeholder::make('status_display')
                                ->label('Status Akun')
                                ->content(function () {
                                    $isActive = auth()->user()->is_active;
                                    if ($isActive) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<span style="'
                                            . 'display:inline-flex;align-items:center;gap:5px;'
                                            . 'padding:2px 10px;'
                                            . 'background:rgb(220 252 231);'
                                            . 'color:rgb(21 128 61);'
                                            . 'border:1px solid rgb(187 247 208);'
                                            . 'border-radius:9999px;'
                                            . 'font-size:12px;font-weight:600;'
                                            . '">'
                                            . '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
                                            . 'Aktif</span>'
                                        );
                                    }

                                    return new \Illuminate\Support\HtmlString(
                                        '<span style="'
                                        . 'display:inline-flex;align-items:center;gap:5px;'
                                        . 'padding:2px 10px;'
                                        . 'background:rgb(254 226 226);'
                                        . 'color:rgb(185 28 28);'
                                        . 'border:1px solid rgb(254 202 202);'
                                        . 'border-radius:9999px;'
                                        . 'font-size:12px;font-weight:600;'
                                        . '">'
                                        . '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
                                        . 'Nonaktif</span>'
                                    );
                                })
                                ->dehydrated(false),

                            Placeholder::make('member_since')
                                ->label('Terdaftar Sejak')
                                ->content(fn () => auth()->user()->created_at?->translatedFormat('d F Y, H:i') ?? '—')
                                ->dehydrated(false),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->model($this->getUser())
                    ->statePath('data'),
            ),
        ];
    }

    /**
     * Ensure the Placeholder fields and role-display HTML are allowed through.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove non-database fields
        unset($data['roles_display'], $data['status_display'], $data['member_since']);

        return $data;
    }
}
