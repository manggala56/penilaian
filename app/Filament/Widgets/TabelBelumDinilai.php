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
use App\Filament\Resources\EvaluationResource;

class TabelBelumDinilai extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Menunggu Penilaian';
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return Auth::user()->role === 'juri';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->getFilteredQuery()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peserta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori'),
                Tables\Columns\TextColumn::make('innovation_title')
                    ->label('Judul Inovasi')
                    ->limit(30),
            ])
            ->actions([
            ]);
    }

    private function getFilteredQuery(): Builder
    {
        $juriId = Auth::id();
        $activeStageIds = Competition::where('is_active', true)->pluck('active_stage_id')->toArray();

        $query = $this->getBaseParticipantQuery();

        return $query->whereDoesntHave('evaluations', function (Builder $q) use ($juriId, $activeStageIds) {
            $q->where('user_id', $juriId)
                ->whereIn('competition_stage_id', $activeStageIds);
        });
    }

    // Helper yang sama (Copy Paste)
    private function getBaseParticipantQuery(): Builder
    {
        $juriId = Auth::id();
        $juriProfile = Juri::where('user_id', $juriId)->with('categories')->first();

        $activeCompetitions = Competition::where('is_active', true)->with('activeStage', 'categories')->get();
        if ($activeCompetitions->isEmpty()) return Participant::query()->whereRaw('1 = 0');

        $query = Participant::query();
        if ($juriProfile && !$juriProfile->can_judge_all_categories) {
            $allowedCategoryIds = $juriProfile->categories->pluck('id')->toArray();
            $query->whereIn('category_id', $allowedCategoryIds);
        }
        $query->where(function ($q) use ($activeCompetitions) {
            foreach ($activeCompetitions as $competition) {
                if ($competition->activeStage) {
                    $stageOrder = $competition->activeStage->stage_order;
                    $categoryIds = $competition->categories->pluck('id');
                    $q->orWhere(function ($subQ) use ($categoryIds, $stageOrder) {
                        $subQ->whereIn('category_id', $categoryIds)->where('current_stage_order', $stageOrder);
                    });
                }
            }
        });
        return $query;
    }
}
