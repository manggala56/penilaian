<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use App\Models\CompetitionStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class StagesRelationManager extends RelationManager
{
    protected static string $relationship = 'stages';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Tahapan Lomba';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Tahap')
                    ->placeholder('Misal: Penyisihan 100 Besar')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull()
                    ->nullable(),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required(),

                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->afterOrEqual('start_date'),

                Forms\Components\TextInput::make('qualifying_count')
                    ->label('Jumlah Lolos / Juara')
                    ->helperText('Jumlah peserta yang lolos dari tahap ini, atau jumlah juara jika ini tahap final.')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1),

                Forms\Components\TextInput::make('stage_order')
                    ->label('Urutan Tahap')
                    ->helperText('Urutan tahapan (1 untuk tahap pertama, 2 untuk tahap kedua, dst)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->unique(modifyRuleUsing: function (Unique $rule, StagesRelationManager $livewire) {
                        return $rule->where('competition_id', $livewire->getOwnerRecord()->id);
                    }, ignoreRecord: true),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage_order')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tahap')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('qualifying_count')
                    ->label('Jumlah Lolos/Juara')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Tahap'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('stage_order')
            ->defaultSort('stage_order', 'asc')
            ->paginated(false);
    }
}
