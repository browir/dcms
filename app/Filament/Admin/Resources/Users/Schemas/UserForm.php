<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\Company;
use App\Models\Department;
use App\Models\Unit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('email')->label('Alamat Email')->email()->required(),
            TextInput::make('username')->required()->maxLength(255),
            TextInput::make('nip')
                ->label('NIP')
                ->helperText('Dipakai untuk mencocokkan akun ini dengan akun SSO karyawan.')
                ->maxLength(50)
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn($state) => filled($state))
                ->required(fn(string $context): bool => $context === 'create'),
            Select::make('company_id')
                ->label('Perusahaan')
                ->options(Company::where('is_active', true)->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable()
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
                        return Department::where('is_active', true)->pluck('name', 'id');
                    }

                    return Department::where('company_id', $companyId)->where('is_active', true)->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->nullable()
                ->live()
                ->afterStateUpdated(function (callable $set) {
                    $set('unit_id', null);
                }),
            Select::make('unit_id')
                ->label('Unit')
                ->options(function (callable $get) {
                    $departmentId = $get('department_id');
                    if (! $departmentId) {
                        return Unit::where('is_active', true)->pluck('name', 'id');
                    }

                    return Unit::where('department_id', $departmentId)->where('is_active', true)->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('roles')->label('Peran')->relationship('roles', 'name')->multiple()->preload()->searchable(),
            Toggle::make('is_active')->label('Aktif')->default(true)->required(),
        ]);
    }
}
