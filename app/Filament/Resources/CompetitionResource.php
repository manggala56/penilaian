<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitionResource\Pages;
use App\Filament\Resources\CompetitionResource\RelationManagers;
use App\Models\Competition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompetitionResource extends Resource
{
    protected static ?string $model = Competition::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Lomba';

    protected static ?string $navigationGroup = 'Manajemen Lomba';
    protected static ?int $navigationSort = 1;
    public static function canViewAny(): bool
    {
        return Auth::user()->role !== 'juri';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Lomba')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lomba')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                            Forms\Components\Select::make('active_stage_id')
                            ->label('Tahap Penilaian Aktif')
                            ->options(function (?Competition $record): array {
                                if (!$record) {
                                    return [];
                                }
                                return $record->stages()->pluck('name', 'id')->toArray();
                            })
                            ->helperText('Simpan lomba ini, lalu buat tahapan di bawah. Setelah itu, Anda bisa memilih tahap aktif di sini.')
                            ->searchable()
                            ->default(null),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lomba')
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
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('activeStage.name')
                    ->label('Tahap Aktif')
                    ->badge()
                    ->default('Belum diatur')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('stages_count')
                    ->label('Total Tahap')
                    ->counts('stages'),
                Tables\Columns\TextColumn::make('categories_count')
                    ->label('Jumlah Kategori')
                    ->counts('categories'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        true => 'Aktif',
                        false => 'Tidak Aktif',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('advance_stage')
                ->label('Naikkan Tahap')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Naikkan Tahapan Lomba')
                ->modalDescription('Aksi ini akan mengubah tahap aktif lomba dan menaikkan semua peserta di tahap saat ini ke tahap yang dipilih. Lanjutkan?')
                ->form(function (Competition $record) {
                    // Ambil tahap saat ini
                    $currentStageId = $record->active_stage_id;
                    return [
                        Forms\Components\Select::make('new_stage_id')
                            ->label('Pilih Tahap Selanjutnya')
                            ->options($record->stages->pluck('name', 'id'))
                            ->required()
                            ->default($currentStageId),
                    ];
                })
                ->action(function (Competition $record, array $data) {
                    $newStageId = $data['new_stage_id'];

                    // 1. Dapatkan object tahap baru untuk tahu urutannya (stage_order)
                    $newStage = \App\Models\CompetitionStage::find($newStageId);

                    if (!$newStage) return;

                    // 2. Dapatkan order tahap saat ini (sebelum diupdate)
                    $currentStageOrder = $record->activeStage?->stage_order ?? 1;

                    // 3. Update Lomba ke tahap baru
                    $record->update([
                        'active_stage_id' => $newStageId
                    ]);
                    \App\Models\Participant::whereHas('category', function ($q) use ($record) {
                            $q->where('competition_id', $record->id);
                        })
                        ->where('current_stage_order', '<', $newStage->stage_order) // Hanya yang levelnya di bawah target
                        ->update([
                            'current_stage_order' => $newStage->stage_order
                        ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Tahapan berhasil diperbarui')
                        ->success()
                        ->send();
                }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitions::route('/'),
            'create' => Pages\CreateCompetition::route('/create'),
            'edit' => Pages\EditCompetition::route('/{record}/edit'),
        ];
    }
}
