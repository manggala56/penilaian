<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\EvaluationScore;
use Illuminate\Support\Facades\Auth;

class EditEvaluation extends EditRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    public function mount(int | string $record): void
    {
        parent::mount($record);

        $evaluation = $this->getRecord();
        $participant = $evaluation->participant->load('category');

        $this->form->fill([
            'category_name' => $participant->category?->name ?? '',
        ]);
    }
    protected function afterFill(): void
    {
        $evaluation = $this->getRecord();
        $scores = $evaluation->scores()->with('aspect')->get();
        $scoresData = $scores->map(function ($score) {
            return [
                'aspect_id' => $score->aspect_id,
                'aspect_name' => $score->aspect?->name ?? '',
                'score' => $score->score,
                'comment' => $score->comment,
            ];
        })->toArray();
        $this->form->fill([
            'scores' => $scoresData,
            'user_id' => Auth::id(),
        ]);
    }
    protected function afterSave(): void
    {
        $evaluation = $this->getRecord();
        $scoresData = $this->form->getState()['scores'] ?? [];

        $evaluation->scores()->delete();

        foreach ($scoresData as $score) {
            EvaluationScore::updateOrCreate([
                'evaluation_id' => $evaluation->id,
                'aspect_id' => $score['aspect_id'],
                'score' => $score['score'],
                'comment' => $score['comment'] ?? '',
            ]);
        }
    }
}
