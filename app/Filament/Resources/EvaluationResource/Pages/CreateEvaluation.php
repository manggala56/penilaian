<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Participant;
use App\Models\Aspect;
use App\Models\EvaluationScore;
class CreateEvaluation extends CreateRecord
{
    protected static string $resource = EvaluationResource::class;

    // Fungsi mount() ini untuk MENGISI form saat load DARI URL
    public function mount(): void
    {
        parent::mount();

        // 1. Ambil participant_id dari URL
        $participantId = request()->query('participant_id');

        if ($participantId) {
            $participant = Participant::with('category')->find($participantId);

            if ($participant && $participant->category_id) {
                $categoryId = $participant->category_id;

                // 2. Cari aspek
                $aspects = Aspect::where('category_id', $categoryId)
                                 ->orderBy('id')
                                 ->get();

                // 3. Siapkan data untuk repeater
                $scoresData = $aspects->map(function ($aspect) {
                    return [
                        'aspect_id' => $aspect->id,
                        'aspect_name' => $aspect->name,
                        'score' => null,
                        'comment' => '',
                    ];
                })->toArray();

                // 4. Isi form (INI AKAN BEKERJA karena repeater tidak pakai ->relationship)
                $this->form->fill([
                    'participant_id' => $participant->id,
                    'category_name' => $participant->category?->name ?? '',
                    'category_id' => $categoryId,
                    'scores' => $scoresData,
                ]);
            }
        }
    }

    // Fungsi ini untuk MENYIMPAN data repeater secara manual
    protected function afterCreate(): void
    {
        $evaluation = $this->getRecord();
        $scoresData = $this->form->getState()['scores'] ?? [];

        foreach ($scoresData as $score) {
            // Pastikan model EvaluationScore Anda ada dan namespace-nya benar
            EvaluationScore::create([
                'evaluation_id' => $evaluation->id,
                'aspect_id' => $score['aspect_id'],
                'score' => $score['score'],
                'comment' => $score['comment'] ?? '',
            ]);
        }
    }
}
