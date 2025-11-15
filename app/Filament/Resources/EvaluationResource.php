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
                            ->default(fn () => Auth::id()) // Set otomatis saat create
                            ->required(),
                        Forms\Components\Select::make('participant_id')
                            ->label('Peserta')
                            ->relationship('participant', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (string $context) => $context === 'edit') // Hanya bisa dipilih saat create
                            ->live() // Aktifkan live untuk reaksi terhadap perubahan
                            ->afterStateUpdated(function ($state, Forms\Set $set, $livewire) {
                                // Ketika participant dipilih (atau diubah)
                                if ($state) {
                                    $participant = Participant::with('category')->find($state);
                                    $categoryId = $participant?->category_id;
                                    $categoryName = $participant?->category?->name ?? '';

                                    // Set kategori terkait
                                    $set('category_name', $categoryName);
                                    $set('category_id', $categoryId);

                                    // Jika ini adalah halaman Create (bukan Edit)
                                    if ($livewire instanceof Pages\CreateEvaluation) {
                                        if ($categoryId) {
                                            // Ambil aspek-aspek berdasarkan kategori peserta
                                            $aspects = Aspect::where('category_id', $categoryId)
                                                            ->orderBy('id')
                                                            ->get();

                                            // Siapkan data untuk repeater 'scores'
                                            $scoresData = $aspects->map(function ($aspect) {
                                                return [
                                                    'aspect_id' => $aspect->id,
                                                    'aspect_name' => $aspect->name,
                                                    'score' => null, // Nilai awal kosong
                                                    'comment' => '', // Komentar awal kosong
                                                ];
                                            })->toArray();

                                            // Isi repeater 'scores' dengan data yang disiapkan
                                            $set('scores', $scoresData);
                                        } else {
                                            // Jika kategori tidak ditemukan, kosongkan scores
                                            $set('scores', []);
                                        }
                                    }
                                } else {
                                    // Jika participant dihapus (misalnya)
                                    $set('category_name', '');
                                    $set('category_id', null);
                                    $set('scores', []);
                                }
                            })
                            // afterStateHydrated penting untuk saat edit, agar tahu kategori & scores awal
                            ->afterStateHydrated(function ($state, Forms\Set $set, string $context) {
                                if ($context === 'edit' && $state) {
                                    $participant = Participant::with('category')->find($state);
                                    $set('category_name', $participant?->category?->name ?? '');
                                    $set('category_id', $participant?->category_id ?? null);
                                }
                            }),
                        Forms\Components\Hidden::make('category_id'), // Field tersembunyi untuk menyimpan ID kategori
                        Forms\Components\TextInput::make('category_name')
                            ->label('Kategori')
                            ->disabled() // Hanya untuk tampilan
                            ->dehydrated(false), // Tidak ikut disimpan ke database
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
                            ->relationship('scores') // Hubungkan ke relasi 'scores' di model Evaluation
                            ->schema([
                                Forms\Components\Hidden::make('aspect_id')
                                    ->required(),
                                Forms\Components\TextInput::make('aspect_name')
                                    ->label('Aspek')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(function (Forms\Get $get) {
                                        // Ambil nama aspek berdasarkan aspect_id jika default diperlukan
                                        $aspectId = $get('aspect_id');
                                        if ($aspectId) {
                                            $aspect = Aspect::find($aspectId);
                                            return $aspect?->name ?? '';
                                        }
                                        return '';
                                    }),
                                Forms\Components\TextInput::make('score')
                                    ->label('Nilai')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(function (Forms\Get $get) {
                                        // Ambil nilai maksimum dari model Aspect
                                        $aspectId = $get('aspect_id');
                                        if (!$aspectId) {
                                            return 100; // Default jika tidak ditemukan
                                        }
                                        $aspect = Aspect::find($aspectId);
                                        return $aspect?->max_score ?? 100;
                                    })
                                    ->required()
                                    ->live() // Aktifkan live untuk perhitungan otomatis
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // Hitung ulang nilai akhir saat nilai berubah
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
                            ->addable(false) // Juri tidak bisa menambah aspek baru
                            ->deletable(false) // Juri tidak bisa menghapus aspek
                            ->live() // Aktifkan live untuk reaksi terhadap perubahan
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                // Hitung ulang nilai akhir saat isi repeater berubah
                                static::updateFinalScore($set, $get);
                            })
                            // Sembunyikan repeater jika tidak ada kategori yang dipilih
                            ->hidden(fn (Forms\Get $get) => !$get('category_id')),
                        Forms\Components\TextInput::make('final_score')
                            ->label('Nilai Akhir')
                            ->numeric()
                            ->readOnly() // Hanya untuk tampilan
                            ->prefix('Total:'),
                    ]),
            ]);
    }

    // Fungsi untuk menghitung nilai akhir berdasarkan bobot
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
                        // Normalisasi skor ke 0-100 berdasarkan max_score aspek
                        $maxScore = $aspect->max_score > 0 ? $aspect->max_score : 100;
                        $normalizedScore = ($score['score'] / $maxScore) * 100;
                        $totalScore += $normalizedScore * $weight;
                    }
                }
            }
        }

        // Bulatkan ke 2 desimal
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
            // Juri hanya bisa melihat penilaian yang dibuatnya sendiri
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

    // Atur hak akses berdasarkan role
    public static function canCreate(): bool
    {
        return Auth::user()->role === 'juri';
    }

    public static function canEdit(Model $record): bool
    {
        // Juri hanya bisa mengedit penilaian miliknya sendiri
        return Auth::user()->role === 'juri' && $record->user_id === Auth::id();
    }

    public static function canDelete(Model $record): bool
    {
        // Juri hanya bisa menghapus penilaian miliknya sendiri
        return Auth::user()->role === 'juri' && $record->user_id === Auth::id();
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->role === 'juri';
    }
}
