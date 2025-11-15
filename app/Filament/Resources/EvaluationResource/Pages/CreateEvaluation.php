<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Participant;
use App\Models\Aspect;
use App\Models\EvaluationScore;

class CreateEvaluation extends CreateRecord
{
    protected static string $resource = EvaluationResource::class;

    public function mount(): void
    {
        parent::mount();
        $participantId = request()->query('participant_id');

        if ($participantId) {
            $participant = Participant::with('category')->find($participantId);

            if ($participant && $participant->category_id) {
                $categoryId = $participant->category_id;

                $aspects = Aspect::where('category_id', $categoryId)
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

                $this->form->fill([
                    'participant_id' => $participant->id,
                    'category_name' => $participant->category?->name ?? '',
                    'category_id' => $categoryId,
                    'scores' => $scoresData,
                ]);
            }
        }
    }

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
