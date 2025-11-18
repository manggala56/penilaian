<?php

namespace App\Filament\Widgets;

use App\Models\Competition;
use App\Models\Evaluation;
use App\Models\Participant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $activeCompetition = Competition::with('activeStage')
            ->where('is_active', true)
            ->first();

        if (!$activeCompetition || !$activeCompetition->activeStage) {
            return [
                Stat::make('Status', 'Tidak ada tahapan aktif')
                    ->color('gray'),
            ];
        }

        $activeStage = $activeCompetition->activeStage;
        $stageId = $activeStage->id;
        $competitionId = $activeCompetition->id;
        $evaluatedCount = Evaluation::where('competition_stage_id', $stageId)
            ->distinct('participant_id')
            ->count();
        $unevaluatedCount = Participant::query()
        ->whereHas('category', function (Builder $query) use ($competitionId) {
            $query->where('competition_id', $competitionId);
        })
        ->where('is_approved', true)
        ->whereDoesntHave('evaluations', function (Builder $query) use ($stageId) {
            $query->where('competition_stage_id', $stageId);
        })
        ->count();
        $qualifyingCount = $activeStage->qualifying_count;

        return [
            Stat::make('Tahapan Saat Ini', $activeStage->name)
                ->description("Urutan ke-{$activeStage->stage_order}")
                ->descriptionIcon('heroicon-m-flag')
                ->color('primary'),

            Stat::make('Progres Penilaian', "$evaluatedCount Peserta")
                ->description("Telah dinilai di tahap ini")
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),

            Stat::make('Kuota Lolos', "$qualifyingCount Peserta")
                ->description("Akan lanjut ke tahap berikutnya")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),
            Stat::make('Belum Dinilai', "$unevaluatedCount Peserta")
            ->description('Peserta menunggu penilaian')
            ->descriptionIcon('heroicon-m-clock')
            ->color('danger'),
        ];
    }

    public static function canView(): bool
    {
        return Auth::user()->role !== 'juri';
    }
}
