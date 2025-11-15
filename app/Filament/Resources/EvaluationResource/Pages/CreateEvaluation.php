<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Participant;

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

            if ($participant) {
                // Auto-fill form data berdasarkan peserta yang dipilih
                $this->form->fill([
                    'participant_id' => $participant->id,
                    'category_name' => $participant->category->name ?? '',
                    'category_id' => $participant->category_id,
                    'evaluation_date' => now(),
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
