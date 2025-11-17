<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use App\Models\Competition;
use App\Models\Evaluation;
use App\Models\Participant;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListEvaluations extends ListRecords
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // Dihapus
        ];
    }

    protected function getEloquentQuery(): Builder
    {
        $activeCompetitions = Competition::where('is_active', true)
                                        ->with('activeStage', 'categories')
                                        ->get();

        if ($activeCompetitions->isEmpty()) {
            return Participant::query()->whereRaw('1 = 0');
        }

        $participantQuery = Participant::query()
            ->with(['category.competition.activeStage', 'evaluations']);

        $participantQuery->where(function ($query) use ($activeCompetitions) {
            foreach ($activeCompetitions as $competition) {
                if ($competition->activeStage) {
                    $stageOrder = $competition->activeStage->stage_order;
                    $categoryIds = $competition->categories->pluck('id');

                    $query->orWhere(function ($q) use ($categoryIds, $stageOrder) {
                        $q->whereIn('category_id', $categoryIds)
                          ->where('current_stage_order', $stageOrder);
                    });
                }
            }
        });

        return $participantQuery;
    }

    private function getExistingEvaluation(Participant $record): ?Evaluation
    {
        $juriId = Auth::id();
        $activeStageId = $record->category?->competition?->active_stage_id;

        if (!$juriId || !$activeStageId) {
            return null;
        }

        return $record->evaluations
            ->where('user_id', $juriId)
            ->where('competition_stage_id', $activeStageId)
            ->first();
    }

    /**
     * Override definisi Table untuk menampilkan kolom Peserta
     * PASTIKAN TYPE HINT DI SINI BENAR
     */
    public function table(Table $table): Table // <--- INI ADALAH TANDA TANGAN YANG BENAR
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                Tables\Columns\IconColumn::make('status_penilaian')
                    ->label('Sudah Dinilai')
                    ->boolean()
                    ->getStateUsing(fn (Participant $record): bool => $this->getExistingEvaluation($record) !== null)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
            ])
            ->actions([
                Action::make('evaluate')
                    ->label(fn (Participant $record): string => $this->getExistingEvaluation($record) ? 'Edit Nilai' : 'Beri Nilai')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(function (Participant $record): string {
                        $evaluation = $this->getExistingEvaluation($record);

                        if ($evaluation) {
                            return EvaluationResource::getUrl('edit', ['record' => $evaluation->id]);
                        } else {
                            // Arahkan ke 'create' tapi dengan data peserta
                            // Kita akan tangani ini di halaman Create
                            return EvaluationResource::getUrl('create', [
                                'participant_id' => $record->id,
                            ]);
                        }
                    }),
            ])
            ->bulkActions([
                // Bulk actions tidak relevan di sini
            ]);
    }
}
