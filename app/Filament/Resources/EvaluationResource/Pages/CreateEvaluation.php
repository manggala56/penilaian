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

                $scoresData = [];
                foreach ($aspects as $aspect) {
                    $scoresData[] = [
                        'aspect_id' => $aspect->id,
                        'aspect_name' => $aspect->name,
                        'score' => null,
                        'comment' => '',
                    ];
                }

                $this->form->fill([
                    'participant_id' => $participant->id,
                    'category_name' => $participant->category?->name ?? '',
                    'category_id' => $categoryId,
                    'evaluation_date' => now(),
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
