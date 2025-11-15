<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\EvaluationScore; // <-- TAMBAHKAN IMPORT INI

class EditEvaluation extends EditRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Fungsi ini untuk MEMUAT data repeater saat halaman Edit dibuka
    protected function afterFill(): void
    {
        $evaluation = $this->getRecord();

        // Pastikan relasi 'scores' di Model/Evaluation Anda ada
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

    // Fungsi ini untuk MENYIMPAN data repeater saat di-edit
    protected function afterSave(): void
    {
        $evaluation = $this->getRecord();
        $scoresData = $this->form->getState()['scores'] ?? [];

        // Hapus data lama
        $evaluation->scores()->delete();

        // Buat data baru dari form
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
