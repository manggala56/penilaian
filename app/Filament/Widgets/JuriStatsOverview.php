<?php
namespace App\Filament\Widgets;

    use App\Models\Competition;
    use App\Models\Participant;
    use Filament\Widgets\StatsOverviewWidget as BaseWidget;
    use Filament\Widgets\StatsOverviewWidget\Stat;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Database\Eloquent\Builder;

    class JuriStatsOverview extends BaseWidget
    {
        // Pastikan widget ini hanya muncul untuk Juri
        public static function canView(): bool
        {
            return Auth::user()->role === 'juri';
        }

        protected function getStats(): array
        {
            $juriId = Auth::id();

            $query = $this->getBaseParticipantQuery();
            $totalPeserta = $query->clone()->count();
            $activeStageIds = Competition::where('is_active', true)
                ->pluck('active_stage_id')
                ->filter()
                ->toArray();
            $sudahDinilai = $query->clone()->whereHas('evaluations', function (Builder $q) use ($juriId, $activeStageIds) {
                $q->where('user_id', $juriId)
                ->whereIn('competition_stage_id', $activeStageIds);
            })->count();

            $belumDinilai = $totalPeserta - $sudahDinilai;

            return [
                Stat::make('Total Peserta', $totalPeserta)
                    ->description('Peserta di tahap aktif')
                    ->icon('heroicon-o-users')
                    ->color('primary'),

                Stat::make('Sudah Dinilai', $sudahDinilai)
                    ->description('Selesai dievaluasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),

                Stat::make('Belum Dinilai', $belumDinilai)
                    ->description('Perlu tindakan segera')
                    ->icon('heroicon-o-clock')
                    ->color('danger'),
            ];
        }

        // Helper untuk mendapatkan query peserta aktif (Copy logic dari PenilaianJuriResource)
        private function getBaseParticipantQuery(): Builder
        {
            $activeCompetitions = Competition::where('is_active', true)
                ->with('activeStage', 'categories')
                ->get();

            if ($activeCompetitions->isEmpty()) {
                return Participant::query()->whereRaw('1 = 0');
            }

            $query = Participant::query();

            $query->where(function ($q) use ($activeCompetitions) {
                foreach ($activeCompetitions as $competition) {
                    if ($competition->activeStage) {
                        $stageOrder = $competition->activeStage->stage_order;
                        $categoryIds = $competition->categories->pluck('id');

                        $q->orWhere(function ($subQ) use ($categoryIds, $stageOrder) {
                            $subQ->whereIn('category_id', $categoryIds)
                                ->where('current_stage_order', $stageOrder);
                        });
                    }
                }
            });

            return $query;
        }
    }
