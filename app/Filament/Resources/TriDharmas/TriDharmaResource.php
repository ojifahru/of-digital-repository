<?php

namespace App\Filament\Resources\TriDharmas;

use App\Filament\Resources\TriDharmas\Pages\CreateTriDharma;
use App\Filament\Resources\TriDharmas\Pages\EditTriDharma;
use App\Filament\Resources\TriDharmas\Pages\ListTriDharmaActivities;
use App\Filament\Resources\TriDharmas\Pages\ListTriDharmas;
use App\Filament\Resources\TriDharmas\Schemas\TriDharmaForm;
use App\Filament\Resources\TriDharmas\Tables\TriDharmasTable;
use App\Models\TriDharma;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TriDharmaResource extends Resource
{
    protected static ?string $model = TriDharma::class;

    protected static string|UnitEnum|null $navigationGroup = 'Repositori Akademik';

    protected static ?string $navigationLabel = 'Tri Dharma';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Tri Dharma';

    protected static ?string $pluralModelLabel = 'Tri Dharma';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    public static function form(Schema $schema): Schema
    {
        return TriDharmaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TriDharmasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereKey([]);
        }

        return $query->visibleToUser($user);
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
            'index' => ListTriDharmas::route('/'),
            'create' => CreateTriDharma::route('/create'),
            'edit' => EditTriDharma::route('/{record}/edit'),
            'activities' => ListTriDharmaActivities::route('/{record}/activities'),
        ];
    }
}
