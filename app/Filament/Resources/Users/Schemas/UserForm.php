<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([

                // ================= USER INFO =================
                Section::make('Informasi User')
                    ->schema([

                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->autocomplete('name'),

                        TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),

                // ================= ROLE =================
                Section::make('Role & Akses')
                    ->schema([

                        Select::make('roles')
                            ->label('Role')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: function (Builder $query): Builder {
                                    if (self::currentScopedAdminShouldManageOnlyEditor()) {
                                        return $query->where('name', 'editor');
                                    }

                                    return $query;
                                },
                            )
                            ->preload()
                            ->searchable()
                            ->live()
                            ->helperText(function (): ?string {
                                if (self::currentScopedAdminShouldManageOnlyEditor()) {
                                    return 'Admin hanya bisa membuat user dengan role editor.';
                                }

                                return null;
                            })
                            ->disabled(fn (): bool => self::currentScopedAdmin() instanceof User && self::isEditingSelf())
                            ->validatedWhenNotDehydrated(false)
                            ->required(),

                        Select::make('study_program_id')
                            ->label('Program Studi')
                            ->relationship(
                                'studyProgram',
                                'name',
                                fn (Builder $query): Builder => self::scopeStudyProgramQuery($query),
                            )
                            ->default(fn (): ?int => self::currentScopedAdmin()?->study_program_id)
                            ->searchable()
                            ->preload()
                            ->required(fn (callable $get): bool => self::selectedRolesRequireStudyProgram($get('roles')))
                            ->disabled(fn (): bool => self::currentScopedAdmin() instanceof User)
                            ->dehydrated()
                            ->helperText(function (): ?string {
                                if (self::currentScopedAdmin() instanceof User) {
                                    return 'Program studi dikunci mengikuti akun admin yang membuat atau mengubah user.';
                                }

                                return 'Wajib untuk role admin dan editor.';
                            }),
                    ]),

                // ================= PASSWORD =================
                Section::make('Keamanan')
                    ->columns(2)
                    ->schema([

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->maxLength(255)
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                            ->confirmed()
                            ->autocomplete('new-password')
                            ->revealable()
                            ->suffixAction(
                                Action::make('generatePassword')
                                    ->icon('heroicon-o-key')
                                    ->tooltip('Generate password otomatis')
                                    ->action(function (callable $set) {
                                        $password = Str::password(12); // Laravel helper
                                        $set('password', $password);
                                    })
                            ),

                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->autocomplete('new-password')
                            ->revealable(),
                    ]),
            ]);
    }

    private static function currentScopedAdmin(): ?User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if (! $user->hasRole('admin') || $user->hasRole('super_admin')) {
            return null;
        }

        return $user;
    }

    private static function currentScopedAdminShouldManageOnlyEditor(): bool
    {
        return self::currentScopedAdmin() instanceof User && ! self::isEditingSelf();
    }

    private static function isEditingSelf(): bool
    {
        $user = Auth::user();
        $record = request()->route('record');

        if (! $user instanceof User || blank($record)) {
            return false;
        }

        if ($record instanceof User) {
            return $record->getKey() === $user->getKey();
        }

        return (string) $record === (string) $user->getKey();
    }

    private static function scopeStudyProgramQuery(Builder $query): Builder
    {
        $query->whereNull('deleted_at');
        $user = self::currentScopedAdmin();

        if (! $user instanceof User) {
            return $query;
        }

        if ($user->study_program_id === null) {
            return $query->whereKey([]);
        }

        return $query->whereKey($user->study_program_id);
    }

    private static function selectedRolesRequireStudyProgram(mixed $roles): bool
    {
        $roleIds = collect(is_array($roles) ? $roles : [$roles])
            ->filter(fn (mixed $role): bool => filled($role))
            ->map(fn (mixed $role): int => (int) $role)
            ->all();

        if ($roleIds === []) {
            return false;
        }

        return Role::query()
            ->whereKey($roleIds)
            ->whereIn('name', ['admin', 'editor'])
            ->exists();
    }
}
