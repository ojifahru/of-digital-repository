<?php

namespace App\Filament\Widgets;

use App\Models\TriDharma;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TriDharmaPerYear extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Tri Dharma Per Tahun';

    protected ?string $maxHeight = '300px';

    protected ?string $description = 'Distribusi dokumen Tri Dharma per tahun publikasi';

    protected function getData(): array
    {
        // Cache data untuk 5 menit untuk mengurangi query database
        $data = Cache::remember($this->getCacheKey(), 300, function () {
            try {
                return $this->scopedTriDharmaQuery()
                    ->whereNotNull('publish_year')
                    ->selectRaw('publish_year, COUNT(*) as total')
                    ->groupBy('publish_year')
                    ->orderBy('publish_year')
                    ->pluck('total', 'publish_year');
            } catch (\Exception $e) {
                // Log error dan return data kosong
                \Log::error('Error fetching TriDharma per year: '.$e->getMessage());

                return collect();
            }
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Dokumen',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#1d4ed8',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    private function scopedTriDharmaQuery(): Builder
    {
        return $this->scopeTriDharmaQuery(TriDharma::query());
    }

    private function getCacheKey(): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return 'tri_dharma_per_year:none';
        }

        if ($user->canManageAllStudyPrograms()) {
            return 'tri_dharma_per_year:all';
        }

        return 'tri_dharma_per_year:study-program:'.($user->study_program_id ?? 'none');
    }

    private function scopeTriDharmaQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereKey([]);
        }

        return $query->visibleToUser($user);
    }

    protected function getType(): string
    {
        return 'line';
    }

    // Optional: Tambahkan options untuk chart
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
