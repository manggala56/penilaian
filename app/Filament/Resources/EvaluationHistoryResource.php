<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationHistoryResource\Pages;
use App\Models\EvaluationHistory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EvaluationHistoryResource extends Resource
{
    protected static ?string $model = EvaluationHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Riwayat Penilaian';
    protected static ?string $modelLabel = 'Riwayat Penilaian';

    protected static ?string $navigationGroup = 'Manajemen Sistem';
    protected static ?int $navigationSort = 80;

    public static function canViewAny(): bool
    {
        return Auth::user()->role === 'admin';
    }

    public static function table(Table $table): Table
    {
        return $table
            // Urutkan berdasarkan terbaru
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Aksi')
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Nama Juri')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('participant_name')
                    ->label('Nama Peserta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('Aktivitas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dibuat' => 'success',
                        'diperbarui' => 'warning',
                        'dihapus' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),

                // Kolom untuk melacak nilai
                Tables\Columns\TextColumn::make('details.old_final_score')
                    ->label('Nilai Lama')
                    ->numeric(2)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('details.new_final_score')
                    ->label('Nilai Baru')
                    ->numeric(2)
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'dibuat' => 'Dibuat',
                        'diperbarui' => 'Diperbarui',
                        'dihapus' => 'Dihapus',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Juri'),
            ])
            ->actions([
                // Tambahkan tombol View untuk melihat detail JSON
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\Textarea::make('details')
                            ->label('Data Lengkap (JSON)')
                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->disabled()
                            ->rows(20),
                    ]),
            ])
            ->bulkActions([
                // Sebaiknya log tidak bisa dihapus massal
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEvaluationHistories::route('/'),

        ];
    }
}
