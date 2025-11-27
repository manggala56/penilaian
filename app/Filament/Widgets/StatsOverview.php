<?php

namespace App\Filament\Widgets;

use App\Models\Competition;
use App\Models\Evaluation;
use App\Models\Participant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $competitionId = $this->filters['competition_id'] ?? null;
        $activeCompetition = $competitionId ? Competition::with('activeStage')->find($competitionId) : Competition::with('activeStage')
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

        // Get all approved participants for this competition
        $participants = Participant::query()
            ->whereHas('category', fn ($q) => $q->where('competition_id', $competitionId))
            ->where('is_approved', true)
            ->with(['category', 'evaluations' => fn ($q) => $q->where('competition_stage_id', $stageId)])
            ->get();

        // Get all active judges
        $judges = \App\Models\Juri::with('categories')->where('is_active', true)->get();
        $universalJudgesCount = $judges->where('can_judge_all_categories', true)->count();

        $evaluatedCount = 0;

        foreach ($participants as $participant) {
            // Count specific judges for this participant's category
            $specificJudgesCount = $judges->filter(function ($juri) use ($participant) {
                return !$juri->can_judge_all_categories && 
                       $juri->categories->contains('id', $participant->category_id);
            })->count();

            $totalRequiredJudges = $universalJudgesCount + $specificJudgesCount;

            // Count unique judges who have evaluated this participant in this stage
            $actualEvaluationsCount = $participant->evaluations
                ->where('competition_stage_id', $stageId)
                ->unique('user_id')
                ->count();

            if ($totalRequiredJudges > 0 && $actualEvaluationsCount >= $totalRequiredJudges) {
                $evaluatedCount++;
            }
        }

        $unevaluatedCount = $participants->count() - $evaluatedCount;
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
