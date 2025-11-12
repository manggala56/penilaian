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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Juri')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(
                                User::where('role', 'juri')
                                    ->get()
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    $set('expertise', $user->expertise ?? '');
                                }
                            }),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
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
                    ->getStateUsing(fn ($record) => $record->current_evaluations_count)
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
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
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
                    ->url(fn ($record) => EvaluationResource::getUrl('index', ['tableFilters[user][value]' => $record->user_id])),
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
            ->with(['user', 'category'])
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
