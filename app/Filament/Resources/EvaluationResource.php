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
    // ... (properti model dan navigasi tetap sama) ...
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
                            // Nonaktifkan pilihan peserta di halaman edit
                            ->disabled(fn (string $context) => $context === 'edit')
                            ->live()

                            // --- AWAL PERBAIKAN 1 ---
                            // Hook ini berjalan saat nilai DIPILIH (di halaman create murni)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $livewire) {
                                // Cek $livewire untuk pastikan ini halaman Create
                                if ($livewire instanceof Pages\CreateEvaluation) {
                                    static::fillScoresByParticipant($state, $set, $livewire);
                                }
                            })
                            // Hook ini berjalan saat form DIMUAT
                            ->afterStateHydrated(function ($state, Forms\Set $set, $livewire) {
                                // $state adalah participant_id yang sudah terisi saat form load
                                if (!$state) {
                                    return; // Abaikan jika tidak ada participant_id (create murni)
                                }

                                // 1. Selalu isi info kategori (untuk Edit dan Create via Relasi)
                                $participant = Participant::with('category')->find($state);
                                $categoryId = $participant?->category_id;
                                $set('category_name', $participant?->category?->name ?? '');
                                $set('category_id', $categoryId);

                                // 2. Hanya isi 'scores' jika ini halaman CREATE (via Relasi)
                                if ($livewire instanceof Pages\CreateEvaluation) {
                                    static::fillScoresByParticipant($state, $set, $livewire);
                                }

                                // (Untuk halaman EDIT, 'scores' diisi otomatis oleh ->relationship())
                            }),
                            // --- AKHIR PERBAIKAN 1 ---

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
                                    // Memuat nama aspek saat halaman edit
                                    ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get) {
                                        $aspectId = $get('aspect_id');
                                        // Cek jika aspect_name belum di-set (saat load hal. edit)
                                        if ($aspectId && !$get('aspect_name')) {
                                            $aspect = Aspect::find($aspectId);
                                            $set('aspect_name', $aspect?->name);
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
                            ->deletable(false)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                static::updateFinalScore($set, $get);
                            })
                            ->hidden(fn (Forms\Get $get) => !$get('category_id')), // Sembunyikan jika belum pilih peserta

                        Forms\Components\TextInput::make('final_score')
                            ->label('Nilai Akhir')
                            ->numeric()
                            ->readOnly()
                            ->prefix('Total:'),
                    ]),
            ]);
    }

    // ... (updateFinalScore, table, getEloquentQuery, dll. tetap sama) ...

    // --- AWAL PERBAIKAN 2 ---
    /**
     * Helper untuk mengisi repeater 'scores' berdasarkan participant_id.
     */
    protected static function fillScoresByParticipant($state, Forms\Set $set, $livewire): void
    {
        // $state adalah participant_id
        if ($state) {
            $participant = Participant::with('category')->find($state);
            $categoryId = $participant?->category_id;

            // Set info kategori (selalu diperlukan)
            $set('category_name', $participant?->category?->name ?? '');
            $set('category_id', $categoryId);

            if ($categoryId) {
                // Cek apakah scores sudah terisi (misal: karena gagal validasi)
                // Kita gunakan $livewire->data untuk mendapat state mentah
                $existingScores = $livewire->data['scores'] ?? [];

                // Hanya isi jika repeater kosong (load pertama kali)
                if (empty($existingScores)) {
                    $aspects = Aspect::where('category_id', $categoryId)
                                    ->orderBy('id')
                                    ->get();

                    $scoresData = $aspects->map(function ($aspect) {
                        return [
                            'aspect_id' => $aspect->id,
                            'aspect_name' => $aspect->name, // Kita isi namanya
                            'score' => null,
                            'comment' => '',
                        ];
                    })->toArray();

                    $set('scores', $scoresData);
                }
            } else {
                $set('scores', []); // Kosongkan jika kategori tidak ditemukan
            }
        } else {
            // Kosongkan jika tidak ada peserta
            $set('category_name', '');
            $set('category_id', null);
            $set('scores', []);
        }
    }
    // --- AKHIR PERBAIKAN 2 ---

    protected static function updateFinalScore(Forms\Set $set, Forms\Get $get): void
    {
        // ... (fungsi ini tidak berubah) ...
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

    public static function table(Table $table): Table
    {
        // ... (tidak berubah) ...
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
        // ... (tidak berubah) ...
        $query = parent::getEloquentQuery()->with(['participant.category', 'user']);

        $user = Auth::user();
        if ($user->role === 'juri') {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        // ... (tidak berubah) ...
        return [
            'index' => Pages\ListEvaluations::route('/'),
            'create' => Pages\CreateEvaluation::route('/create'),
            'edit' => Pages\EditEvaluation::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // ... (tidak berubah) ...
        return Auth::user()->role == 'juri';
    }

    public static function canEdit(Model $record): bool
    {
        // ... (tidak berubah) ...
        return Auth::user()->role == 'juri';
    }

    public static function canDelete(Model $record): bool
    {
        // ... (tidak berubah) ...
        return Auth::user()->role == 'juri';
    }

    public static function canDeleteAny(): bool
    {
        // ... (tidak berubah) ...
        return Auth::user()->role == 'juri';
    }
}
