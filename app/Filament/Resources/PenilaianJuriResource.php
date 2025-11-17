<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource;
use App\Filament\Resources\PenilaianJuriResource\Pages;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\Evaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class PenilaianJuriResource extends Resource
{
    protected static ?string $model = Participant::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Penilaian'; // Ini label menu
    protected static ?string $modelLabel = 'Peserta';
    protected static ?string $pluralModelLabel = 'Daftar Peserta untuk Dinilai';
    protected static ?int $navigationSort = 1;
    public static function canViewAny(): bool
    {
        return Auth::user()->role === 'juri';
    }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $juriId = Auth::id();

        $activeCompetitions = Competition::where('is_active', true)
                                        ->with('activeStage', 'categories')
                                        ->get();

        if ($activeCompetitions->isEmpty()) {
            return Participant::query()->whereRaw('1 = 0');
        }

        $participantQuery = Participant::query()
            ->with([
                'category.competition.activeStage',
                'evaluations' => fn ($query) => $query
                                    ->where('user_id', $juriId)
                                    ->with('scores.aspect'), // <-- INI YANG PENTING
            ]);

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


    public static function table(Table $table): Table
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

            Tables\Columns\TextColumn::make('final_score')
                ->label('Nilai Final')
                ->getStateUsing(function (Participant $record): ?string {
                    $activeStageId = $record->category?->competition?->active_stage_id;
                    if (!$activeStageId) return null;

                    $evaluation = $record->evaluations
                        ->where('competition_stage_id', $activeStageId)
                        ->first();

                    return $evaluation ? number_format($evaluation->final_score, 2) : null;
                })
                ->default('-')
                ->alignEnd()
                ->color(function ($state) {
                    if ($state === '-') return 'gray';
                    return 'primary';
                })
                ->weight('bold'),

            Tables\Columns\TextColumn::make('score_summary')
                ->label('Ringkasan Nilai')
                ->getStateUsing(function (Participant $record): string {
                    $activeStageId = $record->category?->competition?->active_stage_id;
                    if (!$activeStageId) return '';

                    $evaluation = $record->evaluations
                        ->where('competition_stage_id', $activeStageId)
                        ->first();

                    if (!$evaluation || $evaluation->scores->isEmpty()) {
                        return '';
                    }

                    $count = $evaluation->scores->count();
                    $min = $evaluation->scores->min('score');
                    $max = $evaluation->scores->max('score');

                    return "{$count} aspek (min: {$min}, max: {$max})";
                })
                ->default('-')
                ->color('gray')
                ->size('sm'),

            Tables\Columns\IconColumn::make('status_penilaian')
                ->label('Status')
                ->boolean()
                ->getStateUsing(function (Participant $record): bool {
                    $activeStageId = $record->category?->competition?->active_stage_id;
                    if (!$activeStageId) return false;

                    $evaluation = $record->evaluations
                        ->where('competition_stage_id', $activeStageId)
                        ->first();

                    return $evaluation !== null;
                })
                ->trueIcon('heroicon-o-check-circle')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
            ])
            ->actions([
                Action::make('evaluate')
                    ->label(function (Participant $record): string {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        return $evaluation ? 'Edit Nilai' : 'Beri Nilai';
                    })
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(function (Participant $record): string {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        if ($evaluation) {
                            // Link ke halaman EDIT dari EvaluationResource
                            return EvaluationResource::getUrl('edit', ['record' => $evaluation->id]);
                        } else {
                            // Link ke halaman CREATE dari EvaluationResource
                            // Kita kirim participant_id via URL
                            return EvaluationResource::getUrl('create', [
                                'participant_id' => $record->id,
                            ]);
                        }
                    }),
            ])
            ->bulkActions([
                // Juri tidak perlu bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenilaianJuris::route('/'),
            // Kita tidak pakai halaman create/edit resource INI
        ];
    }

}
