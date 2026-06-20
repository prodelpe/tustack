<?php

namespace App\Filament\Widgets;

use App\Models\Technology;
use Filament\Widgets\ChartWidget;

class TopSearchedTechnologiesWidget extends ChartWidget
{
    protected static ?string $heading = 'Top Searched Technologies';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '220px';

    protected function getData(): array
    {
        $technologies = Technology::query()
            ->orderByDesc('searches_count')
            ->pluck('searches_count', 'name');

        return [
            'datasets' => [
                [
                    'label'           => '',
                    'data'            => $technologies->values()->toArray(),
                    'backgroundColor' => '#6366f1',
                    'borderWidth'     => 0,
                ],
            ],
            'labels' => $technologies->keys()->toArray(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
