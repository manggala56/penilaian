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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class JuriResource extends Resource
{
    protected static ?string $model = Juri::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Manajemen Juri';

    protected static ?string $navigationGroup = 'Manajemen Penilaian';

    protected static ?string $recordTitleAttribute = 'user.name';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return Auth::user()->role !== 'juri';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section untuk membuat user baru (hanya pada create)
                Forms\Components\Section::make('Informasi Akun Juri')
                    ->description('Buat akun user baru untuk juri.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique('users', 'email')
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->dehydrated(false)
                            ->rule('confirmed'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->visible(fn ($operation) => $operation === 'create'),

                // Section Informasi User (hanya pada edit)
                Forms\Components\Section::make('Informasi User')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('Nama')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('user.email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->visible(fn ($operation) => $operation === 'edit'),

                // Section Informasi Juri
                Forms\Components\Section::make('Informasi Juri')
                    ->schema([
                        // Toggle universal juri
                        Forms\Components\Toggle::make('can_judge_all_categories')
                            ->label('Bisa Menilai Semua Kategori')
                            ->default(false)
                            ->live()
                            ->helperText('Jika diaktifkan, juri ini dapat menilai peserta dari semua kategori'),

                        // Multi category selection (hanya untuk non-universal)
                        Forms\Components\Select::make('categories')
                            ->label('Kategori yang Dinilai')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(fn (Forms\Get $get): bool =>
                                !$get('can_judge_all_categories')
                            )
                            ->visible(fn (Forms\Get $get): bool =>
                                !$get('can_judge_all_categories')
                            )
                            ->helperText('Pilih kategori yang akan dinilai oleh juri ini'),

                        Forms\Components\Textarea::make('expertise')
                            ->label('Bidang Keahlian')
                            ->rows(3)
                            ->placeholder('Contoh: Teknologi, Seni, Pendidikan, dll.')
                            ->columnSpanFull(),

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
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('category_names')
                    ->label('Kategori')
                    ->html()
                    ->description(fn (Juri $record): string =>
                        $record->can_judge_all_categories
                            ? 'Semua Kategori'
                            : '(' . $record->categories_count . ' kategori)'
                    ),

                Tables\Columns\IconColumn::make('can_judge_all_categories')
                    ->label('Universal')
                    ->boolean()
                    ->tooltip(fn (Juri $record): string =>
                        $record->can_judge_all_categories
                            ? 'Dapat menilai semua kategori'
                            : 'Hanya menilai kategori tertentu'
                    ),

                Tables\Columns\TextColumn::make('expertise')
                    ->label('Keahlian')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('max_evaluations')
                    ->label('Maks Nilai')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string =>
                        $state ?: 'Tidak Terbatas'
                    ),

                Tables\Columns\TextColumn::make('current_evaluations_count')
                    ->label('Sudah Dinilai')
                    ->sortable()
                    ->color(fn (Juri $record): string =>
                        $record->max_evaluations && $record->current_evaluations_count >= $record->max_evaluations
                            ? 'danger'
                            : 'success'
                    ),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->label('Kategori')
                    ->multiple()
                    ->preload()
                    ->searchable(),

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
                    ->url(fn (Juri $record): string =>
                        EvaluationResource::getUrl('index', [
                            'tableFilters[juri_id][value]' => $record->id
                        ])
                    )
                    ->visible(fn (): bool =>
                        class_exists(EvaluationResource::class)
                    ),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-mark')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'categories'])
            ->withCount([
                'categories as categories_count',
                'evaluations as current_evaluations_count'
            ]);
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
