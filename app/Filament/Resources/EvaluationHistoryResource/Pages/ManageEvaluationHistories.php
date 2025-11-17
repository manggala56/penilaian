<?php

namespace App\Filament\Resources\EvaluationHistoryResource\Pages;

use App\Filament\Resources\EvaluationHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEvaluationHistories extends ManageRecords
{
    protected static string $resource = EvaluationHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
