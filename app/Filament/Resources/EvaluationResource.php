<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource\Pages;
use App\Models\Evaluation;
use App\Models\Participant;
use App\Models\Aspect;
use App\Models\Competition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class EvaluationResource extends Resource
{
    protected static ?string $model = Evaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Penilaian';
    protected static ?string $navigationGroup = 'Manajemen Penilaian';
    protected static ?int $navigationSort = 4;
    public static function shouldRegisterNavigation(): bool
    {
        // Hanya tampilkan di navigasi jika BUKAN juri
        return Auth::user()->role !== 'juri';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penilaian')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id())
                            ->required(),
                        Forms\Components\Hidden::make('competition_stage_id'),
                        Forms\Components\Select::make('participant_id')
                            ->label('Peserta')

                            ->options(function () {
                                $activeCompetitions = Competition::where('is_active', true)
                                                                ->with('activeStage', 'categories') // Load relasi yg dibutuhkan
                                                                ->get();

                                if ($activeCompetitions->isEmpty()) {
                                    return [];
                                }

                                $participantQuery = Participant::query();

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

                                return $participantQuery->pluck('name', 'id');
                            })
                            // ->relationship('participant', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (string $context) => $context === 'edit')
                            ->hidden(fn (string $context) => $context === 'edit') // <-- TAMBAHKAN INI
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, $livewire) {
                                if ($state) {
                                    $participant = Participant::with(['category.competition'])->find($state);

                                    $categoryId = $participant?->category_id;
                                    $activeStageId = $participant?->category?->competition?->active_stage_id;

                                    $set('competition_stage_id', $activeStageId); // Ini sudah benar
                                    $set('category_name', $participant?->category?->name ?? '');
                                    $set('category_id', $categoryId);

                                    if ($livewire instanceof Pages\CreateEvaluation) {
                                        if ($categoryId) {
                                            $aspects = Aspect::where('category_id', $categoryId)
                                                            ->orderBy('id')
                                                            ->get();
                                            $scoresData = $aspects->map(function ($aspect) {
                                                return [
                                                    'aspect_id' => $aspect->id,
                                                    'aspect_name' => $aspect->name . ' (' . $aspect->weight . '%)',
                                                    'score' => null,
                                                    'comment' => '',
                                                ];
                                            })->toArray();
                                            $set('scores', $scoresData);
                                        } else {
                                            $set('scores', []);
                                        }
                                    }
                                } else {
                                    $set('competition_stage_id', null);
                                    $set('category_name', '');
                                    $set('category_id', null);
                                    $set('scores', []);
                                }
                            })
                            ->afterStateHydrated(function ($state, Forms\Set $set, string $context) {
                                if ($context === 'edit' && $state) {
                                    $participant = Participant::with('category')->find($state);
                                    $set('category_name', $participant?->category?->name ?? '');
                                    $set('category_id', $participant?->category_id ?? null);
                                }
                            }),
                        Forms\Components\Hidden::make('category_id'),
                        Forms\Components\TextInput::make('category_name')
                        ->label('Kategori')
                        ->disabled()
                        ->dehydrated(false)
                        ->hidden(fn (string $context) => $context === 'edit'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->default('')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Penilaian')
                    ->schema([
                        Forms\Components\Repeater::make('scores')
                            ->schema([
                                Forms\Components\Hidden::make('aspect_id')
                                    ->required(),
                                Forms\Components\TextInput::make('aspect_name')
                                    ->label('Aspek')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        $aspectId = $get('aspect_id');
                                        if ($aspectId) {
                                            $aspect = Aspect::find($aspectId);
                                            $set('aspect_name', $aspect ? "{$aspect->name} ({$aspect->weight}%)" : '');
                                        }
                                    }),
                                Forms\Components\TextInput::make('score')
                                    ->label('Nilai')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(function (Forms\Get $get) {
                                        $aspectId = $get('aspect_id');
                                        if (!$aspectId) { return 100; }
                                        $aspect = Aspect::find($aspectId);
                                        return $aspect?->max_score ?? 100;
                                    })
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        static::updateFinalScore($set, $get);
                                    }),
                                Forms\Components\Textarea::make('comment')
                                    ->label('Komentar')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->required()
                            ->minItems(1)
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                static::updateFinalScore($set, $get);
                            })
                            ->hidden(fn (Forms\Get $get) => empty($get('scores')) && !$get('category_id')),

                        Forms\Components\TextInput::make('final_score')
                            ->label('Nilai Akhir')
                            ->numeric()
                            ->readOnly()
                            ->prefix('Total:'),
                    ]),
            ]);
    }

    protected static function updateFinalScore(Forms\Set $set, Forms\Get $get): void
    {
        $scores = $get('scores');
        $totalScore = 0;

        if (is_array($scores)) {
            foreach ($scores as $score) {
                if (isset($score['aspect_id'], $score['score'])) {
                    $aspect = Aspect::find($score['aspect_id']);
                    if ($aspect) {
                        $weight = $aspect->weight / 100;
                        $maxScore = $aspect->max_score > 0 ? $aspect->max_score : 100;
                        $normalizedScore = ($score['score'] / $maxScore) * 100;
                        $totalScore += $normalizedScore * $weight;
                    }
                }
            }
        }

        $set('final_score', round($totalScore, 2));
    }

    // Fungsi table() dan sisanya biarkan sama seperti aslinya

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('participant.name')
                    ->label('Peserta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('participant.category.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Juri')
                    ->sortable(),
                Tables\Columns\TextColumn::make('evaluation_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_score')
                    ->label('Nilai Akhir')
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('competitionStage.name')
                    ->label('Tahapan')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('participant.category')
                    ->relationship('participant.category', 'name')
                    ->label('Kategori'),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->label('Juri'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['participant.category', 'user']);

        $user = Auth::user();
        if ($user->role === 'juri') {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluations::route('/'),
            'create' => Pages\CreateEvaluation::route('/create'),
            'edit' => Pages\EditEvaluation::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return Auth::user()->role == 'juri';
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->role == 'juri';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->role == 'juri';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->role == 'juri';
    }
}
