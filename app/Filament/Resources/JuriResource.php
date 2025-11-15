<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JuriResource\Pages;
use App\Models\Juri;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JuriResource extends Resource
{
    protected static ?string $model = Juri::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Manajemen Juri';

    protected static ?string $navigationGroup = 'Manajemen Penilaian';

    protected static ?string $recordTitleAttribute = 'user.name';
    protected static ?int $navigationSort = 5 ;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun Juri Baru')
                    ->description('Buat akun user baru yang akan ditugaskan sebagai juri.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama User')
                            ->required()
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->visibleOn('create'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email User')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(table: User::class, column: 'email')
                            ->dehydrated(false)
                            ->visibleOn('create'),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->dehydrated(false)
                            ->visibleOn('create'),
                    ])
                    ->visibleOn('create')
                    ->columns(2),

                // Section Informasi Juri
                Forms\Components\Section::make('Informasi Juri')
                    ->schema([
                        // User info (hanya edit)
                        Forms\Components\Select::make('user_id')
                            ->label('User Juri')
                            ->relationship('user', 'name')
                            ->visibleOn('edit')
                            ->disabled(),

                        // Toggle universal juri
                        Forms\Components\Toggle::make('can_judge_all_categories')
                            ->label('Bisa Menilai Semua Kategori')
                            ->default(false)
                            ->live()
                            ->helperText('Jika diaktifkan, juri ini dapat menilai peserta dari semua kategori')
                            ->columnSpanFull(),

                        // Multi category selection (hanya untuk non-universal)
                        Forms\Components\Select::make('category_ids')
                            ->label('Kategori yang Dinilai')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(fn (Forms\Get $get): bool =>
                                !$get('can_judge_all_categories')
                            )
                            ->hidden(fn (Forms\Get $get): bool =>
                                $get('can_judge_all_categories')
                            )
                            ->dehydrated(fn (Forms\Get $get): bool =>
                                !$get('can_judge_all_categories')
                            )
                            ->helperText(fn (Forms\Get $get): string =>
                                $get('can_judge_all_categories')
                                    ? ''
                                    : 'Pilih kategori yang akan dinilai oleh juri ini (bisa multiple)'
                            ),

                        Forms\Components\Textarea::make('expertise')
                            ->label('Bidang Keahlian')
                            ->rows(3)
                            ->placeholder('Contoh: Teknologi, Seni, Pendidikan, dll.'),

                        Forms\Components\TextInput::make('max_evaluations')
                            ->label('Maksimal Penilaian')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Kosongkan untuk tidak terbatas')
                            ->helperText('Jumlah maksimal peserta yang dapat dinilai oleh juri ini'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan untuk menonaktifkan juri sementara'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Juri')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category_name')
                    ->label('Kategori')
                    ->description(fn ($record): string =>
                        $record->can_judge_all_categories
                            ? ''
                            : '(' . $record->categories->count() . ' kategori)'
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('can_judge_all_categories')
                    ->label('Universal Juri')
                    ->boolean()
                    ->tooltip(fn ($record): string => $record->can_judge_all_categories
                        ? 'Dapat menilai semua kategori'
                        : 'Hanya menilai kategori tertentu'),

                Tables\Columns\TextColumn::make('expertise')
                    ->label('Keahlian')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('max_evaluations')
                    ->label('Maks Nilai')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_evaluations_count')
                    ->label('Sudah Dinilai')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Filter by categories (many-to-many)
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->label('Kategori')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('can_judge_all_categories')
                    ->label('Tipe Juri')
                    ->options([
                        true => 'Universal (Semua Kategori)',
                        false => 'Kategori Spesifik',
                    ]),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Aktif',
                        false => 'Tidak Aktif',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('evaluations')
                    ->label('Lihat Penilaian')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn ($record) => EvaluationResource::getUrl('index', ['tableFilters[juri][value]' => $record->id])),

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
            ->with(['user', 'categories', 'evaluations'])
            ->withCount(['evaluations as current_evaluations_count'])
            ->orderBy('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJuris::route('/'),
            'create' => Pages\CreateJuri::route('/create'),
            'edit' => Pages\EditJuri::route('/{record}/edit'),
        ];
    }
}
