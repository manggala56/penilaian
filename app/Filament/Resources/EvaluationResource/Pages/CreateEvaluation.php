<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Participant;
use App\Models\Aspect;
use App\Models\EvaluationScore;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Filament\Resources\PenilaianJuriResource;

class CreateEvaluation extends CreateRecord
{
    protected static bool $canCreateAnother = false;
    protected static string $resource = EvaluationResource::class;

    public function mount(): void
    {
        parent::mount();

        $participantId = request()->query('participant_id');

        if ($participantId) {
            $participant = Participant::with(['category.competition'])->find($participantId);

            if ($participant && $participant->category_id) {
                $categoryId = $participant->category_id;

                $activeStageId = $participant->category?->competition?->active_stage_id;

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
                    'user_id' => Auth::id(),
                    'competition_stage_id' => $activeStageId, // <-- TAMBAHKAN INI
                ]);
            }
        }
    }
    protected function afterCreate(): void
    {
        $evaluation = $this->getRecord();
        $scoresData = $this->form->getState()['scores'] ?? [];

        foreach ($scoresData as $score) {
            EvaluationScore::create([
                'evaluation_id' => $evaluation->id,
                'aspect_id' => $score['aspect_id'],
                'score' => $score['score'],
                'comment' => $score['comment'] ?? '',
            ]);
        }
    }
    protected function getRedirectUrl(): string
    {
        return PenilaianJuriResource::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['evaluation_date'] = Carbon::now();

        return $data;
    }
}
