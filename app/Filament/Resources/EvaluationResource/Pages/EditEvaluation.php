<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\EvaluationScore;

class EditEvaluation extends EditRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function afterFill(): void
    {
        $evaluation = $this->getRecord();

        // Load relasi scores beserta aspeknya
        $scores = $evaluation->scores()->with('aspect')->get();

        // Format data untuk repeater
        $scoresData = $scores->map(function ($score) {
            return [
                'aspect_id' => $score->aspect_id,
                'aspect_name' => $score->aspect?->name ?? '',
                'score' => $score->score,
                'comment' => $score->comment,
            ];
        })->toArray();

        // Isi field 'scores' di form
        $this->form->fill([
            'scores' => $scoresData,
        ]);
    }

    // TAMBAHKAN FUNGSI INI UNTUK MENYIMPAN data repeater
    protected function afterSave(): void
    {
        $evaluation = $this->getRecord();
        $scoresData = $this->form->getState()['scores'] ?? [];

        // Cara termudah untuk sinkronisasi: Hapus yang lama, buat yang baru
        $evaluation->scores()->delete();

        foreach ($scoresData as $score) {
            EvaluationScore::create([
                'evaluation_id' => $evaluation->id,
                'aspect_id' => $score['aspect_id'],
                'score' => $score['score'],
                'comment' => $score['comment'] ?? '',
            ]);
        }
    }
}
