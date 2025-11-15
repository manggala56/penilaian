<?php

namespace App\Filament\Resources\CompetitionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                Forms\Components\NumberInput::make('qualifying_count')
                    ->label('Jumlah Lolos / Juara')
                    ->helperText('Jumlah peserta yang lolos dari tahap ini, atau jumlah juara jika ini tahap final.')
                    ->required(),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tahap'),
                Tables\Columns\TextColumn::make('qualifying_count')
                    ->label('Jumlah Lolos/Juara'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
            // KUNCI UTAMA: Aktifkan pengurutan
            ->reorderable('stage_order')
            ->defaultSort('stage_order', 'asc') // Selalu urutkan
            ->paginated(false); // Matikan paginasi agar semua 3 tahap terlihat
    }
}
