<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUserActivities;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User || ! $user->hasRole('admin') || $user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->study_program_id === null) {
            return $query->whereKey($user->getKey());
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->whereKey($user->getKey())
                ->orWhere(function (Builder $query) use ($user): void {
                    $query
                        ->where('study_program_id', $user->study_program_id)
                        ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'editor'));
                });
        });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'activities' => ListUserActivities::route('/{record}/activities'),
        ];
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->can('ViewAny:User')) {
            return true;
        }

        return false;
    }
}
