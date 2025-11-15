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

        // Handle participant_id dari URL parameter
        $participantId = request()->query('participant_id');

        if ($participantId) {
            $participant = Participant::with('category')->find($participantId);

            if ($participant && $participant->category_id) {
                // Auto-fill form data berdasarkan peserta yang dipilih
                $this->form->fill([
                    'participant_id' => $participant->id,
                    'category_name' => $participant->category->name ?? '',
                    'category_id' => $participant->category_id,
                    'evaluation_date' => now(),
                ]);

                // Pre-fill scores data berdasarkan kategori peserta
                $aspects = Aspect::where('category_id', $participant->category_id)
                                ->orderBy('id')
                                ->get();

                $scoresData = $aspects->map(function ($aspect) {
                    return [
                        'aspect_id' => $aspect->id,
                        'aspect_name' => $aspect->name,
                        'score' => null,
                        'comment' => '',
                    ];
                })->toArray();

                // Isi data scores ke form
                $this->form->fill([
                    'scores' => $scoresData,
                ]);
            }
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
