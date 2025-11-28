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

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class StatsOverview extends BaseWidget implements HasActions, HasForms
{
    use InteractsWithPageFilters;
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $pollingInterval = '15s';
    
    protected static string $view = 'filament.widgets.stats-overview';

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

        // Get all approved participants for this competition who are in the active stage
        $participants = Participant::query()
            ->whereHas('category', fn ($q) => $q->where('competition_id', $competitionId))
            ->where('is_approved', true)
            ->where('current_stage_order', $activeStage->stage_order)
            ->with(['category', 'evaluations' => fn ($q) => $q->where('competition_stage_id', $stageId)])
            ->get();

        // Get all active judges
        $judges = \App\Models\Juri::with('categories')->where('is_active', true)->get();
        $universalJudgesCount = $judges->where('can_judge_all_categories', true)->count();

        $evaluatedCount = 0;
        $unevaluatedParticipants = collect();

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
            } else {
                $unevaluatedParticipants->push($participant);
            }
        }

        $unevaluatedCount = $unevaluatedParticipants->count();
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
                ->description('Peserta menunggu penilaian (Klik untuk detail)')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition',
                    'wire:click' => "mountAction('openWaitingList')",
                ]),
        ];
    }

    public function openWaitingList(): Action
    {
        return Action::make('openWaitingList')
            ->label('Peserta Menunggu Penilaian')
            ->modalHeading('Daftar Peserta Belum Lengkap Dinilai')
            ->modalWidth('4xl')
            ->modalContent(function () {
                // Re-fetch logic to ensure fresh data in modal
                $competitionId = $this->filters['competition_id'] ?? null;
                $activeCompetition = $competitionId ? Competition::with('activeStage')->find($competitionId) : Competition::with('activeStage')
                    ->where('is_active', true)
                    ->first();

                if (!$activeCompetition || !$activeCompetition->activeStage) {
                    return view('filament.widgets.waiting-participants', ['participants' => []]);
                }

                $stageId = $activeCompetition->activeStage->id;
                $competitionId = $activeCompetition->id;

                $participants = Participant::query()
                    ->whereHas('category', fn ($q) => $q->where('competition_id', $competitionId))
                    ->where('is_approved', true)
                    ->where('current_stage_order', $activeCompetition->activeStage->stage_order)
                    ->with(['category', 'evaluations' => fn ($q) => $q->where('competition_stage_id', $stageId)])
                    ->get();

                $judges = \App\Models\Juri::with('categories')->where('is_active', true)->get();
                $universalJudgesCount = $judges->where('can_judge_all_categories', true)->count();

                $unevaluatedParticipants = collect();

                foreach ($participants as $participant) {
                    $assignedJudges = collect();
                    
                    // 1. Universal Judges
                    $universalJudges = $judges->where('can_judge_all_categories', true);
                    foreach ($universalJudges as $judge) {
                        $hasEvaluated = $participant->evaluations
                            ->where('competition_stage_id', $stageId)
                            ->where('user_id', $judge->user_id)
                            ->isNotEmpty();
                            
                        $assignedJudges->push([
                            'name' => $judge->name,
                            'status' => $hasEvaluated ? 'Sudah Menilai' : 'Belum Menilai',
                            'color' => $hasEvaluated ? 'success' : 'danger',
                        ]);
                    }

                    // 2. Specific Category Judges
                    $specificJudges = $judges->filter(function ($juri) use ($participant) {
                        return !$juri->can_judge_all_categories && 
                               $juri->categories->contains('id', $participant->category_id);
                    });

                    foreach ($specificJudges as $judge) {
                        $hasEvaluated = $participant->evaluations
                            ->where('competition_stage_id', $stageId)
                            ->where('user_id', $judge->user_id)
                            ->isNotEmpty();

                        $assignedJudges->push([
                            'name' => $judge->name,
                            'status' => $hasEvaluated ? 'Sudah Menilai' : 'Belum Menilai',
                            'color' => $hasEvaluated ? 'success' : 'danger',
                        ]);
                    }

                    $participant->assigned_judges_status = $assignedJudges;

                    // Check if fully evaluated
                    $totalRequiredJudges = $universalJudges->count() + $specificJudges->count();
                    $actualEvaluationsCount = $participant->evaluations
                        ->where('competition_stage_id', $stageId)
                        ->unique('user_id')
                        ->count();

                    if (!($totalRequiredJudges > 0 && $actualEvaluationsCount >= $totalRequiredJudges)) {
                        $unevaluatedParticipants->push($participant);
                    }
                }

                return view('filament.widgets.waiting-participants', [
                    'participants' => $unevaluatedParticipants
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->label('Tutup'));
    }

    public static function canView(): bool
    {
        return Auth::user()->role !== 'juri';
    }
}
