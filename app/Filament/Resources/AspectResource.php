<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AspectResource\Pages;
use App\Models\Aspect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AspectResource extends Resource
{
    protected static ?string $model = Aspect::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Aspek Penilaian';

    protected static ?string $navigationGroup = 'Manajemen Lomba';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;
    public static function canViewAny(): bool
    {
        return Auth::user()->role !== 'juri';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Aspek Penilaian')
                    ->schema([
                        Forms\Components\Select::make('competition_id')
                            ->label('Lomba')
                            ->options(\App\Models\Competition::all()->pluck('name', 'id'))
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('category_id', null)),

                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->options(function (Forms\Get $get) {
                                $competitionId = $get('competition_id');
                                if ($competitionId) {
                                    return \App\Models\Category::where('competition_id', $competitionId)->pluck('name', 'id');
                                }
                                return \App\Models\Category::all()->pluck('name', 'id');
                            })
                            ->required()
                            ->live() // Tambahkan live() untuk real-time update
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Otomatis set urutan ketika kategori dipilih
                                if ($state) {
                                    $nextOrder = Aspect::where('category_id', $state)->count() + 1;
                                    $set('order', $nextOrder);
                                }
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Aspek')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('weight')
                            ->label('Bobot (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('max_score')
                            ->label('Nilai Maksimal')
                            ->numeric()
                            ->minValue(1)
                            ->default(100)
                            ->required(),
                        Forms\Components\TextInput::make('order')
                            ->label('Urutan')
                            ->numeric()
                            ->minValue(1)
                            ->default(function (Forms\Get $get) {
                                // Default value berdasarkan count aspek dalam kategori yang dipilih
                                $categoryId = $get('category_id');
                                if ($categoryId) {
                                    return Aspect::where('category_id', $categoryId)->count() + 1;
                                }
                                return 1;
                            })
                            ->hint(function (Forms\Get $get) {
                                $categoryId = $get('category_id');
                                if ($categoryId) {
                                    $currentCount = Aspect::where('category_id', $categoryId)->count();
                                    return "Saat ini ada {$currentCount} aspek dalam kategori ini";
                                }
                                return "Pilih kategori terlebih dahulu";
                            })
                            ->hintColor('primary'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Aspek')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->label('Bobot (%)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_score')
                    ->label('Nilai Maks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        return "{$record->order} (dari " .
                               Aspect::where('category_id', $record->category_id)->count() .
                               " aspek)";
                    }),
            ])
            ->groups([
                Group::make('category.competition.name')
                    ->label('Lomba')
                    ->collapsible(),
                Group::make('category.name')
                    ->label('Kategori')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (Aspect $record): string => 
                        ($record->category->competition->name ?? 'Unknown Competition') . ' - ' . ($record->category->name ?? 'Unknown Category')
                    ),
            ])
            ->defaultGroup('category.name')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('moveUp')
                    ->label('Naik')
                    ->icon('heroicon-o-arrow-up')
                    ->action(function (Aspect $record) {
                        $previous = Aspect::where('category_id', $record->category_id)
                            ->where('order', '<', $record->order)
                            ->orderBy('order', 'desc')
                            ->first();

                        if ($previous) {
                            $currentOrder = $record->order;
                            $record->order = $previous->order;
                            $previous->order = $currentOrder;

                            $record->save();
                            $previous->save();
                        }
                    })
                    ->visible(fn (Aspect $record) => $record->order > 1),

                Tables\Actions\Action::make('moveDown')
                    ->label('Turun')
                    ->icon('heroicon-o-arrow-down')
                    ->action(function (Aspect $record) {
                        $next = Aspect::where('category_id', $record->category_id)
                            ->where('order', '>', $record->order)
                            ->orderBy('order', 'asc')
                            ->first();

                        if ($next) {
                            $currentOrder = $record->order;
                            $record->order = $next->order;
                            $next->order = $currentOrder;

                            $record->save();
                            $next->save();
                        }
                    })
                    ->visible(fn (Aspect $record) =>
                        $record->order < Aspect::where('category_id', $record->category_id)->count()
                    ),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAspects::route('/'),
            'create' => Pages\CreateAspect::route('/create'),
            'edit' => Pages\EditAspect::route('/{record}/edit'),
        ];
    }
}
