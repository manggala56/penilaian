<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ParticipantsPerCategory extends ChartWidget
{
    protected static ?string $heading = 'Peserta per Kategori';

    protected function getData(): array
    {
        $categories = Category::withCount('participants')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Peserta',
                    'data' => $categories->pluck('participants_count')->toArray(),
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#C9CBCF'
                    ],
                ],
            ],
            'labels' => $categories->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
