<?php

namespace App\Filament\Widgets;

use App\Models\Participant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TopParticipants extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    public static function canView(): bool
    {
        return Auth::user()->role !== 'juri';
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Participant::query()
                    ->whereHas('evaluations')
                    ->with(['category', 'evaluations'])
                    ->withAvg('evaluations', 'final_score')
                    ->orderBy('evaluations_avg_final_score', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peserta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('innovation_title')
                    ->label('Judul Inovasi')
                    ->limit(50),
                Tables\Columns\TextColumn::make('evaluations_avg_final_score')
                    ->label('Nilai Rata-rata')
                    ->numeric(2)
                    ->sortable(),
            ])
            ->heading('5 Besar Nilai Terbaik');
    }

}
