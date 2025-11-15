<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\EvaluationScore; // <-- 1. TAMBAHKAN INI
use App\Models\Aspect;          // <-- 2. TAMBAHKAN INI

class EditEvaluation extends EditRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // 3. TAMBAHKAN FUNGSI INI UNTUK ME-LOAD DATA
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // 1. Ambil data scores yang sudah ada dari database
        $evaluationScores = EvaluationScore::where('evaluation_id', $this->getRecord()->id)
                                            ->with('aspect') // Load relasi aspect
                                            ->orderBy('id')  // Urutkan
                                            ->get();

        // 2. Format data tersebut agar sesuai dengan field repeater
        $scoresData = $evaluationScores->map(function ($score) {
            return [
                'aspect_id'   => $score->aspect_id,
                'aspect_name' => $score->aspect?->name ?? '', // Ambil nama dari relasi
                'score'       => $score->score,
                'comment'     => $score->comment,
            ];
        })->toArray();

        // 3. Masukkan data scores ke dalam array $data form
        $data['scores'] = $scoresData;

        return $data;
    }

    // 4. TAMBAHKAN FUNGSI INI UNTUK MENYIMPAN DATA
    protected function afterSave(): void
    {
        $evaluation = $this->getRecord();
        $scoresData = $this->form->getState()['scores'] ?? [];

        // 1. Hapus data score yang lama (cara paling aman)
        EvaluationScore::where('evaluation_id', $evaluation->id)->delete();

        // 2. Buat ulang data score berdasarkan data repeater yang baru
        foreach ($scoresData as $score) {
            EvaluationScore::create([
                'evaluation_id' => $evaluation->id,
                'aspect_id'     => $score['aspect_id'],
                'score'         => $score['score'],
                'comment'       => $score['comment'] ?? '',
            ]);
        }
    }
}
