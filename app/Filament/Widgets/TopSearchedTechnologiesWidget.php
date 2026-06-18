<?php

namespace App\Filament\Widgets;

use App\Models\Technology;
use Filament\Widgets\ChartWidget;

class TopSearchedTechnologiesWidget extends ChartWidget
{
    protected static ?string $heading = 'Top Searched Technologies';

    protected int|string|array $columnSpan = 'half';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $technologies = Technology::query()
            ->where('searches_count', '>', 0)
            ->orderByDesc('searches_count')
            ->limit(8)
            ->pluck('searches_count', 'name');

        $colors = [
            '#6366f1', '#f59e0b', '#10b981', '#3b82f6',
            '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6',
        ];

        return [
            'datasets' => [
                [
                    'data'            => $technologies->values()->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $technologies->count()),
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => $technologies->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
