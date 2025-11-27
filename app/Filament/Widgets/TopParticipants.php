<?php

namespace App\Filament\Widgets;

use App\Models\Competition;
use App\Models\CompetitionStage;
use App\Models\Participant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TopParticipantsExport;
use Filament\Tables\Actions\Action;

class TopParticipants extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    // Judul Widget
    protected static ?string $heading = 'Peringkat Peserta Per Kategori';

    public static function canView(): bool
    {
        return Auth::user()->role !== 'juri';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $filterState = $this->getTableFilterState('stage_id');
                $selectedStageId = $filterState['value'] ?? null;
                if (!$selectedStageId) {
                    $activeCompetition = Competition::where('is_active', true)->first();
                    $selectedStageId = $activeCompetition?->active_stage_id;
                }

                if (!$selectedStageId) {
                    return Participant::query()->whereNull('id');
                }

                // 2. Query Utama
                return Participant::query()
                    // Ambil peserta yang punya nilai di tahap ini
                    ->whereHas('evaluations', function ($query) use ($selectedStageId) {
                        $query->where('competition_stage_id', $selectedStageId);
                    })
                    ->with(['category'])
                    // Hitung rata-rata nilai di tahap ini
                    ->withAvg(['evaluations' => function ($query) use ($selectedStageId) {
                        $query->where('competition_stage_id', $selectedStageId);
                    }], 'final_score')
                    // Urutkan: Kategori dulu (biar rapi), baru Nilai Tertinggi
                    ->orderBy('category_id')
                    ->orderByDesc('evaluations_avg_final_score');
            })
            ->columns([
                Tables\Columns\TextColumn::make('real_rank')
                    ->label('Peringkat') // Label header
                    ->weight('bold')     // Huruf tebal
                    ->size('lg')         // Ukuran besar
                    ->color(fn ($state) => match ($state) {
                        '#1' => 'warning',    // Emas untuk Rank 1
                        '#2', '#3' => 'success', // Hijau untuk 2 & 3
                        default => 'gray',
                    })
                    ->getStateUsing(function ($record, $livewire) {
                        $filterState = $livewire->getTableFilterState('stage_id');
                        $stageId = $filterState['value'] ?? Competition::where('is_active', true)->value('active_stage_id');

                        $myScore = $record->evaluations_avg_final_score;
                        $higherRankCount = Participant::query()
                                ->where('category_id', $record->category_id)
                                ->whereHas('evaluations', fn($q) => $q->where('competition_stage_id', $stageId))
                                ->withAvg(['evaluations' => fn($q) => $q->where('competition_stage_id', $stageId)], 'final_score')
                                ->having('evaluations_avg_final_score', '>', $myScore) // Cari orang yang nilainya LEBIH TINGGI
                                ->count();
                            return '#' . ($higherRankCount + 1);
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peserta')
                    ->description(fn (Participant $record) => new HtmlString(
                        ($record->innovation_title ? '<div class="font-medium text-xs">' . $record->innovation_title . '</div>' : '') .
                        '<div class="text-xs text-gray-500">' . $record->institution . '</div>'
                    ))
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('evaluations_avg_final_score')
                    ->label('Nilai')
                    ->numeric(2)
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status_prediksi')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record, $livewire) {
                        $filterState = $livewire->getTableFilterState('stage_id');
                        $stageId = $filterState['value'] ?? Competition::where('is_active', true)->value('active_stage_id');

                        $stage = CompetitionStage::find($stageId);
                        $quota = $stage?->qualifying_count ?? 0;

                        if ($quota <= 0) return 'Menunggu';
                        $myScore = $record->evaluations_avg_final_score;
                        $higherRankCount = Participant::query()
                                ->where('category_id', $record->category_id)
                                ->whereHas('evaluations', fn($q) => $q->where('competition_stage_id', $stageId))
                                ->withAvg(['evaluations' => fn($q) => $q->where('competition_stage_id', $stageId)], 'final_score')
                                ->having('evaluations_avg_final_score', '>', $myScore)
                                ->count();

                            $rankInCategory = $higherRankCount + 1;
                            return $rankInCategory <= $quota ? 'Lolos' : 'Gugur';
                    })
                    ->colors([
                        'success' => 'Lolos',
                        'danger' => 'Gugur',
                        'gray' => 'Menunggu',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'Lolos',
                        'heroicon-m-x-circle' => 'Gugur',
                    ]),
            ])
            ->groups([
                Group::make('category.name')
                    ->label('Kategori')
                    ->collapsible(),
            ])
            ->defaultGroup('category.name')
            ->filters([
                SelectFilter::make('stage_id')
                    ->label('Pilih Tahapan')
                    ->options(function () {
                        $activeCompetition = Competition::where('is_active', true)->first();
                        if (!$activeCompetition) return [];
                        return CompetitionStage::where('competition_id', $activeCompetition->id)
                            ->orderBy('stage_order')
                            ->pluck('name', 'id');
                    })
                    ->default(function () {
                        return Competition::where('is_active', true)->value('active_stage_id');
                    })
                    ->query(fn (Builder $query) => $query),
                    SelectFilter::make('urutan')
                    ->label('Urutan Nilai')
                    ->options([
                        'desc' => 'Teratas (Nilai Tertinggi)', // Descending
                        'asc'  => 'Terbawah (Nilai Terendah)', // Ascending
                    ])
                    ->default('desc') // Default Teratas
                    ->query(fn (Builder $query) => $query), // Bypass otomatis, kita handle manual di query atas

                SelectFilter::make('category_id')
                    ->label('Filter Kategori')
                    ->relationship('category', 'name'),

            ])

            ->paginated([10, 25, 50, 100, 'all'])
            ->headerActions([
                Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($livewire) {
                        $filterState = $livewire->getTableFilterState('stage_id');
                        $stageId = $filterState['value'] ?? Competition::where('is_active', true)->value('active_stage_id');
                        
                        return Excel::download(new TopParticipantsExport($stageId), 'peringkat-peserta.xlsx');
                    }),
            ]);
    }
}
