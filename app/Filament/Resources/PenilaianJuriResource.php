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
                Tables\Columns\TextColumn::make('innovation_title')
                ->description(function (Participant $record): string {
                    return $record->innovation_description ?? 'Tidak ada deskripsi';
                })
                    ->label('Judul Inovasi')
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
                    ->weight('bold')
                    ->color(fn ($state) => $state === '-' ? 'gray' : 'primary'),

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
                Action::make('viewEvaluationDetails')
                    ->label('Detail')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('gray')
                    ->modalHeading(fn (Participant $record) => "Detail Penilaian - {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('lg')
                    ->form(function (Participant $record) {
                        $activeStageId = $record->category?->competition?->active_stage_id;

                        if (!$activeStageId) {
                            return [
                                Forms\Components\Placeholder::make('no_stage')
                                    ->content('Tidak ada tahap kompetisi aktif')
                                    ->columnSpanFull(),
                            ];
                        }

                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        if (!$evaluation) {
                            return [
                                Forms\Components\Placeholder::make('no_evaluation')
                                    ->content('Belum ada penilaian untuk peserta ini')
                                    ->columnSpanFull(),
                            ];
                        }

                        return [
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Placeholder::make('participant_name')
                                        ->label('Nama Peserta')
                                        ->content($record->name),

                                    Forms\Components\Placeholder::make('category')
                                        ->label('Kategori')
                                        ->content($record->category?->name ?? '-'),
                                ])
                                ->columns(2),

                            Forms\Components\Section::make('Detail Nilai Per Aspek')
                                ->schema(
                                    $evaluation->scores->map(function ($score) {
                                        return Forms\Components\Placeholder::make("score_{$score->id}")
                                            ->label($score->aspect?->name ?? 'Aspek Dihapus')
                                            ->content(number_format($score->score, 2))
                                            ->extraAttributes(['class' => 'border-l-4 border-primary-500 pl-3']);
                                    })->toArray()
                                )
                                ->columns(2)
                                ->compact(),

                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Placeholder::make('final_score')
                                        ->label('Nilai Final')
                                        ->content(number_format($evaluation->final_score, 2))
                                        ->extraAttributes([
                                            'class' => 'text-2xl font-bold text-primary-600 text-center'
                                        ]),
                                ])
                                ->compact(),
                        ];
                    })
                    ->visible(function (Participant $record) {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        if (!$activeStageId) return false;

                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        return $evaluation !== null;
                    }),


                Action::make('evaluate')
                    ->label(function (Participant $record): string {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        return $evaluation ? 'Edit Nilai' : 'Beri Nilai';
                    })
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->button()
                    ->url(function (Participant $record): string {
                        $activeStageId = $record->category?->competition?->active_stage_id;
                        $evaluation = $record->evaluations
                            ->where('competition_stage_id', $activeStageId)
                            ->first();

                        if ($evaluation) {
                            return EvaluationResource::getUrl('edit', ['record' => $evaluation->id]);
                        } else {
                            return EvaluationResource::getUrl('create', [
                                'participant_id' => $record->id,
                            ]);
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenilaianJuris::route('/'),
            // Kita tidak pakai halaman create/edit resource INI
        ];
    }

}
