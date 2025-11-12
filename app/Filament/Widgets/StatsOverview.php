<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Evaluation;
use App\Models\Participant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $currentYear = now()->year;

        $totalParticipants = Participant::count();
        $evaluatedParticipants = Evaluation::distinct('participant_id')->count('participant_id');
        $unevaluatedParticipants = $totalParticipants - $evaluatedParticipants;

        return [
            Stat::make('Total Peserta', $totalParticipants)
                ->description('Semua kategori')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Sudah Dinilai', $evaluatedParticipants)
                ->description('Peserta yang sudah dinilai')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Belum Dinilai', $unevaluatedParticipants)
                ->description('Peserta yang belum dinilai')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
