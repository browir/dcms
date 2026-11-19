<?php

namespace App\Filament\Pages\Auth;

use App\Models\Company;
use App\Models\Department;
use App\Models\Unit;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    protected string $view = 'filament.custom-register';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getUsernameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCompanyFormComponent(),
                $this->getDepartmentFormComponent(),
                $this->getUnitFormComponent(),
            ])
            ->statePath('data');
    }

    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        $user->assignRole('user');

        return $user;
    }

    protected function getPasswordFormComponent(): \Filament\Schemas\Components\Component
    {
        return parent::getPasswordFormComponent()
            ->rule(\Illuminate\Validation\Rules\Password::min(8)->letters()->mixedCase()->numbers())
            ->helperText('Minimal 8 karakter, kombinasi huruf besar, kecil, dan angka.');
    }

    protected function getUsernameFormComponent(): TextInput
    {
        return TextInput::make('username')
            ->label('Nama pengguna')
            ->required()
            ->unique('users', 'username')
            ->minLength(3)
            ->maxLength(255)
            ->autocomplete('username');
    }

    protected function getCompanyFormComponent(): Select
    {
        return Select::make('company_id')
            ->label('Perusahaan')
            ->options(Company::pluck('name', 'id'))
            ->searchable()
            ->preload()
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($set) {
                $set('department_id', null);
                $set('unit_id', null);
            });
    }

    protected function getDepartmentFormComponent(): Select
    {
        return Select::make('department_id')
            ->label('Departemen')
            ->options(function ($get) {
                $companyId = $get('company_id');
                if (! $companyId) {
                    return [];
                }

                return Department::where('company_id', $companyId)->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->reactive()
            ->afterStateUpdated(fn ($set) => $set('unit_id', null))
            ->disabled(fn ($get) => ! $get('company_id'));
    }

    protected function getUnitFormComponent(): Select
    {
        return Select::make('unit_id')
            ->label('Unit')
            ->options(function ($get) {
                $departmentId = $get('department_id');
                if (! $departmentId) {
                    return [];
                }

                return Unit::where('department_id', $departmentId)->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->reactive()
            ->disabled(fn ($get) => ! $get('department_id'));
    }
}
