<?php

namespace App\Filament\Resources\TriDharmas\Pages;

use App\Filament\Resources\TriDharmas\TriDharmaResource;
use App\Models\TriDharma;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListTriDharmas extends ListRecords
{
    protected static string $resource = TriDharmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Tri Dharma')
                ->visible(fn (): bool => Auth::user()?->can('create', TriDharma::class) ?? false),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'published' => Tab::make('Published')
                ->icon('heroicon-o-check-circle')
                ->badge(fn (): int => $this->getScopedTriDharmaQuery()->where('status', 'published')->count())
                ->query(fn (Builder $query) => $query->where('status', 'published')),

            'draft' => Tab::make('Draft')
                ->icon('heroicon-o-pencil-square')
                ->badge(fn (): int => $this->getScopedTriDharmaQuery()->where('status', 'draft')->count())
                ->query(fn (Builder $query) => $query->where('status', 'draft')),

            'all' => Tab::make('Semua')
                ->icon('heroicon-o-archive-box')
                ->badge(fn (): int => $this->getScopedTriDharmaQuery()->count()),
        ];

        // 👑 SUPER ADMIN SAJA
        if (Auth::user()?->hasRole('super_admin')) {
            $tabs['deleted'] = Tab::make('Dihapus')
                ->icon('heroicon-o-trash')
                ->badge(fn (): int => $this->getScopedTriDharmaQuery()->onlyTrashed()->count())
                ->query(fn (Builder $query) => $query->onlyTrashed());
        }

        return $tabs;
    }

    private function getScopedTriDharmaQuery(): Builder
    {
        $query = TriDharma::query();
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereKey([]);
        }

        return $query->visibleToUser($user);
    }
}
