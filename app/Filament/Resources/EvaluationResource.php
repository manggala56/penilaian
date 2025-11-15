<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource\Pages;
use App\Models\Evaluation;
use App\Models\Participant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Database\Eloquent\Model;

class EvaluationResource extends Resource
{
    protected static ?string $model = Evaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Penilaian';

    protected static ?string $navigationGroup = 'Manajemen Penilaian';
    protected static ?int $navigationSort = 4 ;

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
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $participant = Participant::find($state);
                                    $set('category_name', $participant?->category?->name ?? '');
                                }
                            }),
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
                                Forms\Components\Select::make('aspect_id')
                                    ->label('Aspek')
                                    ->relationship('aspect', 'name')
                                    ->required()
                                    ->disabled(fn ($context) => $context === 'edit')
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        static::updateFinalScore($set, $get);
                                    }),
                                                        Forms\Components\TextInput::make('score')
                                    ->label('Nilai')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(fn ($get) => \App\Models\Aspect::find($get('aspect_id'))?->max_score ?? 100)
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
                            ->reorderable()
                            ->addActionLabel('Tambah Aspek Penilaian')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                static::updateFinalScore($set, $get);
                            }),

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
                    $aspect = \App\Models\Aspect::find($score['aspect_id']);
                    if ($aspect) {
                        $weight = $aspect->weight / 100;
                        $normalizedScore = ($score['score'] / $aspect->max_score) * 100;
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
        return parent::getEloquentQuery()
            ->with(['participant.category', 'user']);
            $user = Auth::user();
            $query = parent::getEloquentQuery();
            if ($user->role === 'juri') {
                $juri = \App\Models\juri::where('user_id', $user->id)->first();

                if ($juri) {
                    $query->where('juri_id', $juri->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
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
