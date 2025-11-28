<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationHistoryResource\Pages;
use App\Models\CompetitionStage;
use App\Models\EvaluationHistory;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

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
                Tables\Columns\TextColumn::make('evaluation.participant.innovation_title')
                    ->label('Judul Inovasi')
                    ->limit(30)
                    ->searchable(),
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
                Tables\Actions\ViewAction::make()
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\Placeholder::make('details_formatted')
                            ->label('Rincian Aktivitas')
                            ->content(fn (EvaluationHistory $record) => new HtmlString(self::formatDetails($record->details))),
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEvaluationHistories::route('/'),
        ];
    }

    /**
     * Helper untuk memformat value berdasarkan key-nya.
     */
    private static function formatValue(string $key, mixed $value): string
    {
        if (is_null($value)) {
            return '-';
        }

        // 1. Handle ID References (Lookup ke DB)
        if ($key === 'user_id') {
            return User::find($value)?->name . " (ID: $value)" ?? $value;
        }
        // Participant logic removed
        if ($key === 'competition_stage_id') {
            return CompetitionStage::find($value)?->name . " (ID: $value)" ?? $value;
        }

        // 2. Handle Date Formatting
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $value;
            }
        }

        // 3. Handle Participant Object/Array
        if ($key === 'participant' && (is_array($value) || is_object($value))) {
            // If it's the participant object, we probably don't need to show the whole JSON
            // We can just show the name if available, or nothing since we have participant_name column
            return is_array($value) ? ($value['name'] ?? 'Participant Data') : 'Participant Data';
        }

        // 4. Default: kembalikan value as string
        return is_array($value) ? json_encode($value) : (string) $value;
    }

    private static function formatDetails(?array $details): string
    {
        if (empty($details)) {
            return '<p class="text-sm text-gray-500 italic">Tidak ada detail tambahan.</p>';
        }

        $html = '<div class="space-y-4 text-sm">';

        // 1. Tampilkan Perubahan Skor
        if (isset($details['old_final_score']) || isset($details['new_final_score'])) {
            $old = isset($details['old_final_score']) ? number_format($details['old_final_score'], 2) : '-';
            $new = isset($details['new_final_score']) ? number_format($details['new_final_score'], 2) : '-';

            $html .= '
            <div class="p-3 rounded-lg bg-gray-50 border border-gray-200 dark:bg-white/5 dark:border-white/10">
                <div class="text-xs font-medium text-gray-500 uppercase mb-2">Perubahan Nilai Akhir</div>
                <div class="flex items-center gap-4">
                    <div>
                        <div class="text-xs text-gray-400">Sebelumnya</div>
                        <div class="text-lg font-bold text-danger-600 font-mono">'.$old.'</div>
                    </div>
                    <div class="text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Menjadi</div>
                        <div class="text-lg font-bold text-success-600 font-mono">'.$new.'</div>
                    </div>
                </div>
            </div>';
        }

        // 2. Tampilkan List Perubahan (Updated)
        if (!empty($details['changes']) && is_array($details['changes'])) {
            $html .= '<div class="mt-2">';
            $html .= '<h4 class="font-semibold text-gray-900 dark:text-white mb-1">Atribut yang Diperbarui:</h4>';
            $html .= '<ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-white/5 p-3 rounded border border-gray-200 dark:border-white/10">';

            foreach ($details['changes'] as $key => $value) {
                if ($key === 'updated_at') continue;

                $displayValue = self::formatValue($key, $value);

                $html .= "<li><span class='font-medium text-primary-600'>{$key}</span>: <span class='italic'>{$displayValue}</span></li>";
            }
            $html .= '</ul></div>';
        }

        // 3. Tampilkan Data Lengkap (Created/Deleted)
        $data = $details['data'] ?? $details['deleted_data'] ?? null;
        if (is_array($data)) {
            $label = isset($details['deleted_data']) ? 'Data yang Dihapus' : 'Data Tersimpan';
            $html .= '<div class="mt-2">';
            $html .= "<h4 class='font-semibold text-gray-900 dark:text-white mb-1'>{$label}:</h4>";
            $html .= '<div class="grid grid-cols-1 gap-1 bg-gray-50 dark:bg-white/5 p-3 rounded border border-gray-200 dark:border-white/10 max-h-60 overflow-y-auto">';

            foreach ($data as $k => $v) {
                if (in_array($k, ['created_at', 'updated_at', 'id'])) continue;

                $displayValue = self::formatValue($k, $v);

                $html .= "<div class='flex justify-between py-1 border-b border-gray-200 dark:border-white/5 last:border-0'>";
                $html .= "<span class='text-gray-500 text-xs'>{$k}</span>";
                $html .= "<span class='text-gray-900 dark:text-gray-200 text-xs font-medium text-right pl-2'>{$displayValue}</span>";
                $html .= "</div>";
            }
            $html .= '</div></div>';
        }

        $html .= '</div>';
        return $html;
    }
}
