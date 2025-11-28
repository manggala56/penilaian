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
                        Forms\Components\Placeholder::make('weight_quota')
                            ->label('Kuota Bobot')
                            ->content(function (Forms\Get $get, $record) {
                                $categoryId = $get('category_id');
                                if (!$categoryId) {
                                    return 'Pilih kategori terlebih dahulu';
                                }

                                $totalWeight = Aspect::where('category_id', $categoryId)
                                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                    ->sum('weight');
                                
                                $remaining = 100 - $totalWeight;
                                $color = $remaining < 0 ? 'text-danger-600' : 'text-success-600';

                                return new \Illuminate\Support\HtmlString(
                                    "Total saat ini: <strong>{$totalWeight}%</strong><br>" .
                                    "Sisa kuota: <strong class='{$color}'>{$remaining}%</strong>"
                                );
                            })
                            ->columnSpanFull(),

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
                            ->required()
                            ->live()
                            ->rules([
                                function (Forms\Get $get, $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $categoryId = $get('category_id');
                                        if (!$categoryId) return;

                                        $totalWeight = Aspect::where('category_id', $categoryId)
                                            ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                            ->sum('weight');
                                        
                                        if (($totalWeight + $value) > 100) {
                                            $remaining = 100 - $totalWeight;
                                            $fail("Total bobot melebihi 100%. Sisa kuota: {$remaining}%");
                                        }
                                    };
                                },
                            ]),
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
                    ->searchable()
                    ->visibleFrom('md'),
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
                    ->sortable()
                    ->visibleFrom('sm'),
                Tables\Columns\TextColumn::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        return "{$record->order} (dari " .
                               Aspect::where('category_id', $record->category_id)->count() .
                               " aspek)";
                    })
                    ->visibleFrom('lg'),
            ])
            ->groups([
                Group::make('category.competition.name')
                    ->label('Lomba')
                    ->collapsible(),
                Group::make('category.name')
                    ->label('Kategori')
                    ->collapsible()
                    ->getTitleFromRecordUsing(function (Aspect $record): string {
                        $category = $record->category()->withTrashed()->first();
                        $competition = $category?->competition()->withTrashed()->first();
                        
                        return ($competition?->name ?? 'Unknown Competition') . ' - ' . ($category?->name ?? 'Unknown Category');
                    }),
            ])
            ->defaultGroup('category.name')
            ->filters([
                // Tables\Filters\TrashedFilter::make(), // Removed manual filter, let SoftDeletingScope handle it
                Tables\Filters\Filter::make('filter')
                    ->form([
                        Forms\Components\Select::make('competition_id')
                            ->label('Lomba')
                            ->options(function () {
                                $query = \App\Models\Competition::query();
                                if (Auth::user()->role === 'superadmin') {
                                    $query->withTrashed();
                                }
                                return $query->pluck('name', 'id');
                            })
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('category_id', null)),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->options(function (Forms\Get $get) {
                                $competitionId = $get('competition_id');
                                $query = \App\Models\Category::query();
                                
                                if (Auth::user()->role === 'superadmin') {
                                    $query->withTrashed();
                                }

                                if ($competitionId) {
                                    $query->where('competition_id', $competitionId);
                                }
                                
                                return $query->pluck('name', 'id');
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['competition_id'],
                                fn (Builder $query, $competitionId) => $query->whereHas('category', fn ($q) => $q->withTrashed()->where('competition_id', $competitionId))
                            )
                            ->when(
                                $data['category_id'],
                                fn (Builder $query, $categoryId) => $query->where('category_id', $categoryId)
                            );
                    })
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
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
                Tables\Actions\DeleteAction::make()
                    ->label('Arsipkan')
                    ->modalDescription('Apakah Anda yakin ingin mengarsipkan aspek ini? Aspek yang diarsipkan tidak akan terlihat oleh Juri.'),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Ensure we only show aspects that have active categories
        // Unless we are explicitly looking at trashed items (handled by SoftDeletingScope)
        
        // Fix for "Active Aspect but Deleted Category" (Orphaned)
        // We want to hide these from the default view.
        $query->whereHas('category', function ($q) {
            // This closure filters the relationship. 
            // By default whereHas checks for non-deleted related models if SoftDeletes is on.
            // So this line effectively hides aspects with deleted categories.
        });

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAspects::route('/'),
            'create' => Pages\CreateAspect::route('/create'),
            'edit' => Pages\EditAspect::route('/{record}/edit'),
        ];
    }

    public static function getSoftDeletingScope(): ?string
    {
        if (Auth::user()->role === 'superadmin') {
            return \Illuminate\Database\Eloquent\SoftDeletingScope::class;
        }
        return null;
    }

    public static function canRestore(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->role === 'superadmin';
    }

    public static function canForceDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->role === 'superadmin';
    }
}
