<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Participant; // <-- TAMBAHKAN IMPORT INI
use App\Models\Aspect;      // <-- TAMBAHKAN IMPORT INI

class CreateEvaluation extends CreateRecord
{
    protected static string $resource = EvaluationResource::class;

    // TAMBAHKAN SELURUH FUNGSI DI BAWAH INI
    protected function mount(): void
    {
        parent::mount();

        // 1. Ambil participant_id dari query string di URL
        $participantId = request()->query('participant_id');

        if ($participantId) {
            // 2. Cari peserta beserta data kategorinya
            $participant = Participant::with('category')->find($participantId);

            if ($participant && $participant->category_id) {
                $categoryId = $participant->category_id;

                // 3. Cari semua aspek penilaian berdasarkan kategori peserta
                $aspects = Aspect::where('category_id', $categoryId)
                                 ->orderBy('id') // Pastikan urutan konsisten
                                 ->get();

                // 4. Siapkan data default untuk repeater 'scores'
                $scoresData = $aspects->map(function ($aspect) {
                    return [
                        'aspect_id' => $aspect->id,
                        'aspect_name' => $aspect->name, // Langsung isi nama aspek
                        'score' => null,
                        'comment' => '',
                    ];
                })->toArray();

                // 5. Isi form dengan semua data yang sudah disiapkan
                $this->form->fill([
                    'participant_id' => $participant->id,
                    'category_name' => $participant->category?->name ?? '',
                    'category_id' => $categoryId,
                    'scores' => $scoresData, // Ini akan mengisi repeater 'scores'
                ]);
            }
        }
    }
}
