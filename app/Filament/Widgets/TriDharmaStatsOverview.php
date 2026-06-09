<?php

namespace App\Filament\Widgets;

use App\Models\Author;
use App\Models\TriDharma;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TriDharmaStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        // Single query untuk semua data TriDharma (lebih efisien)
        $triDharmaCounts = $this->scopedTriDharmaQuery()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count
            ")
            ->first();

        $authorCount = $this->getScopedAuthorCount();

        return [
            Stat::make('Total Dokumen', $triDharmaCounts->total ?? 0)
                ->icon('heroicon-o-document-text')
                ->description('Seluruh dokumen Tri Dharma')
                ->color('primary')
                ->descriptionIcon('heroicon-m-arrow-trending-up'),

            Stat::make('Total Authors', $authorCount)
                ->icon('heroicon-o-user-group')
                ->description('Penulis dalam cakupan akses')
                ->color('secondary'),

            Stat::make('Published', $triDharmaCounts->published_count ?? 0)
                ->icon('heroicon-o-check-circle')
                ->description('Dokumen yang telah dipublikasi')
                ->color('success')
                ->descriptionIcon('heroicon-m-check-badge'),

            Stat::make('Draft', $triDharmaCounts->draft_count ?? 0)
                ->icon('heroicon-o-pencil-square')
                ->description('Dokumen dalam proses')
                ->color('warning')
                ->descriptionIcon('heroicon-m-clock'),
        ];
    }

    private function scopedTriDharmaQuery(): Builder
    {
        return $this->scopeTriDharmaQuery(TriDharma::query());
    }

    private function getScopedAuthorCount(): int
    {
        $user = Auth::user();

        if ($user instanceof User && $user->canManageAllStudyPrograms()) {
            return Author::query()->count();
        }

        if (! $user instanceof User) {
            return 0;
        }

        return Author::query()
            ->whereHas('triDharmas', fn (Builder $query) => $this->scopeTriDharmaQuery($query))
            ->count();
    }

    private function scopeTriDharmaQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereKey([]);
        }

        return $query->visibleToUser($user);
    }
}
