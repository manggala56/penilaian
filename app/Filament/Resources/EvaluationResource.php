<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource\Pages;
use App\Models\Evaluation;
use App\Models\Participant;
use App\Models\Aspect;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penilaian')
                    ->schema([
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id())
                            ->required(),
                        Forms\Components\Select::make('participant_id')
                            ->label('Peserta')
                            ->relationship('participant', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            // 'disabled' saat 'edit' sudah benar
                            ->disabled(fn (string $context) => $context === 'edit')
                            ->live()
                            // Hook 'afterStateUpdated' ini PENTING untuk perubahan manual
                            ->afterStateUpdated(function ($state, Forms\Set $set, $livewire) {
                                if ($state) {
                                    $participant = Participant::with('category')->find($state);
                                    $categoryId = $participant?->category_id;
                                    $set('category_name', $participant?->category?->name ?? '');
                                    $set('category_id', $categoryId);

                                    // Hanya pre-fill jika ini halaman 'create'
                                    if ($livewire instanceof Pages\CreateEvaluation) {
                                        if ($categoryId) {
                                            $aspects = Aspect::where('category_id', $categoryId)
                                                            ->orderBy('id') // Pastikan urutan konsisten
                                                            ->get();

                                            // Buat data default untuk repeater
                                            $scoresData = $aspects->map(function ($aspect) {
                                                return [
                                                    'aspect_id' => $aspect->id,
                                                    'aspect_name' => $aspect->name,
                                                    'score' => null,
                                                    'comment' => '',
                                                ];
                                            })->toArray();

                                            // Set data ke repeater 'scores'
                                            $set('scores', $scoresData);
                                        } else {
                                            $set('scores', []); // Kosongkan jika kategori tidak ditemukan
                                        }
                                    }
                                } else {
                                    // Kosongkan jika tidak ada peserta
                                    $set('category_name', '');
                                    $set('category_id', null);
                                    $set('scores', []);
                                }
                            })
                            // Hook 'afterStateHydrated' ini PENTING untuk halaman 'edit'
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
                            ->dehydrated(false),
                        Forms\Components\DatePicker::make('evaluation_date')
                            ->label('Tanggal Penilaian')
                            ->required()
                            ->default(now()),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Penilaian')
                    ->schema([
                        Forms\Components\Repeater::make('scores')
    ->relationship('scores')
    ->schema([
        Forms\Components\Hidden::make('aspect_id')
            ->required(),
        Forms\Components\TextInput::make('aspect_name')
            ->label('Aspek')
            ->disabled()
            ->dehydrated(false)
            ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                // Perbaiki fungsi ini untuk lebih reliable
                $aspectId = $get('aspect_id');
                if ($aspectId) {
                    $aspect = Aspect::find($aspectId);
                    $set('aspect_name', $aspect?->name ?? '');
                }
            }),
        Forms\Components\TextInput::make('score')
            ->label('Nilai')
            ->numeric()
            ->minValue(0)
            ->maxValue(function (Forms\Get $get) {
                $aspectId = $get('aspect_id');
                if (!$aspectId) {
                    return 100;
                }

                $aspect = Aspect::find($aspectId);
                return $aspect?->max_score ?? 100;
            })
            ->required()
            ->live()
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
    // ->deletable(false) // Kita mungkin perlu membiarkan ini, tapi mount() akan mengisinya
    ->deletable(false)
    ->live()
    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
        static::updateFinalScore($set, $get);
    })
    // Kondisi hidden ini sudah benar
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
                        // Pastikan max_score tidak nol untuk menghindari division by zero
                        $maxScore = $aspect->max_score > 0 ? $aspect->max_score : 100;
                        $normalizedScore = ($score['score'] / $maxScore) * 100;
                        $totalScore += $normalizedScore * $weight;
                    }
                }
            }
        }

        $set('final_score', round($totalScore, 2));
    }

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
            // Jika menggunakan relasi langsung ke user
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
