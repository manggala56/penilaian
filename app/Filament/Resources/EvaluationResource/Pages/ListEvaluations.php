<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluations extends ListRecords
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
    protected function getEloquentQuery(): Builder
    {
        // 1. Temukan kompetisi aktif
        $activeCompetitions = Competition::where('is_active', true)
                                        ->with('activeStage', 'categories')
                                        ->get();

        if ($activeCompetitions->isEmpty()) {
            // Jika tidak ada kompetisi aktif, jangan tampilkan peserta
            return Participant::query()->whereRaw('1 = 0');
        }

        // 2. Buat query dasar untuk Peserta
        $participantQuery = Participant::query()
            ->with(['category.competition.activeStage', 'evaluations']); // Load relasi

        // 3. Filter peserta berdasarkan kompetisi & tahapan aktif
        //    (Logika ini diambil dari EvaluationResource form Anda)
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
        // Pastikan relasi category, competition, dan activeStage sudah ter-load
        $activeStageId = $record->category?->competition?->active_stage_id;

        if (!$juriId || !$activeStageId) {
            return null;
        }

        // Cek menggunakan relasi 'evaluations' yang sudah di-load
        return $record->evaluations
            ->where('user_id', $juriId)
            ->where('competition_stage_id', $activeStageId)
            ->first();
    }
    public function table(Table $table): Table
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
                // Filter berdasarkan kategori jika diperlukan
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
            ])
            ->actions([
                // Aksi kondisional "Nilai" / "Edit"
                Action::make('evaluate')
                    ->label(fn (Participant $record): string => $this->getExistingEvaluation($record) ? 'Edit Nilai' : 'Beri Nilai')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(function (Participant $record): string {
                        $evaluation = $this->getExistingEvaluation($record);

                        if ($evaluation) {
                            // Jika sudah ada, arahkan ke halaman edit evaluasi
                            return EvaluationResource::getUrl('edit', ['record' => $evaluation->id]);
                        } else {
                            // Jika belum, arahkan ke halaman create evaluasi
                            // Juri harus memilih peserta lagi di dropdown,
                            // tapi dropdown itu sudah difilter untuk menampilkan peserta yang benar.
                            return EvaluationResource::getUrl('create');
                        }
                    }),
            ])
            ->bulkActions([
                // Bulk actions tidak relevan di sini
            ]);
    }

}
