<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Participant;
use App\Models\Aspect;

class CreateEvaluation extends CreateRecord
{
    protected static string $resource = EvaluationResource::class;

    public function mount(): void
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

    /**
     * Pre-fill data scores berdasarkan category_id
     */
    protected function prefillScoresData($categoryId): void
    {
        if ($categoryId) {
            $aspects = Aspect::where('category_id', $categoryId)
                            ->orderBy('id')
                            ->get();

            // Buat data default untuk repeater
            $scoresData = $aspects->map(function ($aspect) {
                return [
                    'aspect_id' => $aspect->id,
                    'aspect_name' => $aspect->name,
                    'score' => null,
                    'comment' => '',
                ];
            })->toArray();

            // Set data ke repeater 'scores' menggunakan form state
            $currentState = $this->form->getState();
            $currentState['scores'] = $scoresData;
            $this->form->fill($currentState);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan user_id terisi
        if (!isset($data['user_id'])) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
