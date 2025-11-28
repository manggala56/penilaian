<?php

namespace App\Filament\Widgets;

use App\Models\Competition;
use App\Models\Participant;
use App\Models\Juri;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class TabelBelumDinilai extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Menunggu Penilaian';
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return Auth::user()?->role === 'juri';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return $this->getParticipantsQuery();
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peserta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori'),
                Tables\Columns\TextColumn::make('innovation_title')
                    ->label('Judul Inovasi')
                    ->limit(30),
                Tables\Columns\TextColumn::make('current_stage_order')
                    ->label('Stage')
                    ->sortable(),
            ])
            ->emptyStateHeading('Tidak ada peserta yang menunggu penilaian')
            ->emptyStateDescription('Semua peserta telah dinilai atau tidak ada peserta yang memenuhi kriteria.');
    }

    protected function getParticipantsQuery(): Builder
    {
        $juriId = Auth::id();
        
        // Dapatkan profil juri
        $juriProfile = Juri::where('user_id', $juriId)
            ->with(['categories.competition'])
            ->first();

        if (!$juriProfile) {
            return Participant::whereNull('id'); // Return empty query
        }

        // Dapatkan competition_id dari filter
        $selectedCompetitionId = $this->filters['competition_id'] ?? null;

        // Tentukan kompetisi yang akan ditampilkan
        $competitionQuery = Competition::where('is_active', true)
            ->with(['activeStage', 'categories']);

        // Jika juri hanya bisa menilai kategori tertentu, batasi kompetisi berdasarkan kategori juri
        if (!$juriProfile->can_judge_all_categories && $juriProfile->categories->isNotEmpty()) {
            $juriCompetitionIds = $juriProfile->categories->pluck('competition_id')->unique();
            $competitionQuery->whereIn('id', $juriCompetitionIds);
        }

        // Terapkan filter competition_id jika ada
        if ($selectedCompetitionId) {
            $competitionQuery->where('id', $selectedCompetitionId);
        }

        $competitions = $competitionQuery->get();

        if ($competitions->isEmpty()) {
            return Participant::whereNull('id');
        }

        // Kumpulkan stage IDs dan category IDs yang aktif
        $activeStageIds = [];
        $competitionCategoryIds = [];

        foreach ($competitions as $competition) {
            if ($competition->activeStage) {
                $activeStageIds[] = $competition->activeStage->id;
            }
            $competitionCategoryIds = array_merge(
                $competitionCategoryIds, 
                $competition->categories->pluck('id')->toArray()
            );
        }

        // Base query untuk participant
        $query = Participant::query()
            ->with(['category', 'evaluations'])
            ->whereIn('category_id', $competitionCategoryIds);

        // Filter kategori yang boleh dinilai juri
        if (!$juriProfile->can_judge_all_categories && $juriProfile->categories->isNotEmpty()) {
            $allowedCategoryIds = $juriProfile->categories->pluck('id')->toArray();
            $query->whereIn('category_id', $allowedCategoryIds);
        }

        // Filter participant yang berada di stage aktif
        $query->where(function ($q) use ($competitions) {
            foreach ($competitions as $competition) {
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

        // Filter participant yang belum dinilai oleh juri ini di stage aktif
        if (!empty($activeStageIds)) {
            $query->whereDoesntHave('evaluations', function (Builder $q) use ($juriId, $activeStageIds) {
                $q->where('user_id', $juriId)
                    ->whereIn('competition_stage_id', $activeStageIds);
            });
        }

        return $query;
    }
}