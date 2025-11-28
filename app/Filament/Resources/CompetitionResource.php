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
                    $newStage = \App\Models\CompetitionStage::find($newStageId);

                    if (!$newStage) return;

                    $currentStage = $record->activeStage;
                    
                    // Jika tidak ada tahap aktif sebelumnya (awal lomba), set semua peserta ke tahap baru
                    if (!$currentStage) {
                        $record->update(['active_stage_id' => $newStageId]);
                        
                        // Update semua peserta di lomba ini ke tahap pertama
                        \App\Models\Participant::whereHas('category', fn($q) => $q->where('competition_id', $record->id))
                            ->update(['current_stage_order' => $newStage->stage_order]);

                        \Filament\Notifications\Notification::make()
                            ->title('Lomba dimulai')
                            ->body('Semua peserta masuk ke tahap awal.')
                            ->success()
                            ->send();
                        return;
                    }

                    // Logika Eliminasi / Promosi
                    $quota = $currentStage->qualifying_count;

                    // Jika ada kuota (misal: 10 besar), maka kita filter
                    if ($quota > 0) {
                        // Iterasi per kategori karena kuota berlaku per kategori
                        foreach ($record->categories as $category) {
                            // Ambil peserta di kategori ini yang ada di tahap saat ini
                            $participants = \App\Models\Participant::query()
                                ->where('category_id', $category->id)
                                ->where('current_stage_order', $currentStage->stage_order)
                                ->withAvg(['evaluations' => fn($q) => $q->where('competition_stage_id', $currentStage->id)], 'final_score')
                                ->orderByDesc('evaluations_avg_final_score')
                                ->get();

                            // Ambil N terbaik
                            $qualified = $participants->take($quota);

                            // Update status mereka ke tahap berikutnya
                            foreach ($qualified as $p) {
                                $p->update(['current_stage_order' => $newStage->stage_order]);
                            }
                            
                            // Sisanya tetap di stage_order lama (artinya gugur/tidak lanjut)
                        }
                        
                        $msg = "Tahapan diperbarui. Peserta disaring berdasarkan kuota {$quota} besar per kategori.";
                    } else {
                        // Jika kuota 0 atau null, berarti lolos semua (non-eliminasi)
                         \App\Models\Participant::whereHas('category', fn($q) => $q->where('competition_id', $record->id))
                            ->where('current_stage_order', $currentStage->stage_order)
                            ->update([
                                'current_stage_order' => $newStage->stage_order
                            ]);
                            
                        $msg = "Tahapan diperbarui. Semua peserta lanjut ke tahap berikutnya.";
                    }

                    // Update Lomba ke tahap baru
                    $record->update(['active_stage_id' => $newStageId]);

                    \Filament\Notifications\Notification::make()
                        ->title('Tahapan Berhasil Diperbarui')
                        ->body($msg)
                        ->success()
                        ->send();
                }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Arsipkan')
                    ->modalHeading('Arsipkan Lomba')
                    ->modalDescription('Lomba ini akan diarsipkan beserta semua data terkait (Kategori, Peserta, Nilai). Hanya Superadmin yang dapat memulihkannya. Lanjutkan?'),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StagesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()->role === 'superadmin') {
            $query->withTrashed();
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitions::route('/'),
            'create' => Pages\CreateCompetition::route('/create'),
            'edit' => Pages\EditCompetition::route('/{record}/edit'),
        ];
    }

    public static function getSoftDeletingScope(): ?string
    {
        // Only superadmin can see trashed records
        if (Auth::user()->role === 'superadmin') {
            return \Illuminate\Database\Eloquent\SoftDeletingScope::class;
        }
        return null; // Others cannot see trashed
    }

    public static function canRestore(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->role === 'superadmin';
    }

    public static function canRestoreAny(): bool
    {
        return Auth::user()->role === 'superadmin';
    }

    public static function canForceDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()->role === 'superadmin';
    }

    public static function canForceDeleteAny(): bool
    {
        return Auth::user()->role === 'superadmin';
    }
}
