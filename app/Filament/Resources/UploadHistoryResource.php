<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UploadHistoryResource\Pages;
use App\Filament\Resources\UploadHistoryResource\RelationManagers;
use App\Models\UploadHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UploadHistoryResource extends Resource
{
    protected static ?string $model = UploadHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Riwayat Upload';
    protected static ?string $modelLabel = 'Riwayat Upload';
    protected static ?string $navigationGroup = 'Manajemen Sistem';
    protected static ?int $navigationSort = 80;

    public static function canViewAny(): bool
    {
        return Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin';
    }

    public static function form(Form $form): Form
    {
        
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('participant.name')
                    ->label('Peserta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Perangkat')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // No actions needed for history log
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUploadHistories::route('/'),
        ];
    }
}

