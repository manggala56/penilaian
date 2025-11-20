<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\EvaluationScore;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\PenilaianJuriResource;

class EditEvaluation extends EditRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $evaluation = $this->getRecord();
        $scores = $evaluation->scores()->with('aspect')->get();
        $data['scores'] = $scores->map(function ($score) {
            return [
                'aspect_id' => $score->aspect_id,
                'aspect_name' => $score->aspect?->name ?? '',
                'score' => $score->score,
                'comment' => $score->comment,
            ];
        })->toArray();
        $data['user_id'] = Auth::id();
        if ($evaluation->participant) {
            $data['category_id'] = $evaluation->participant->category_id;
            $data['category_name'] = $evaluation->participant->category->name ?? '';
        }

        return $data;
    }
    protected function afterSave(): void
    {
        $evaluation = $this->getRecord();
        $scoresData = $this->form->getState()['scores'] ?? [];

        $evaluation->scores()->delete();

        foreach ($scoresData as $score) {
            EvaluationScore::create([
                'evaluation_id' => $evaluation->id ,
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
}
