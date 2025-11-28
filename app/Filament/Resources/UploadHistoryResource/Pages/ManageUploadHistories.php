<?php

namespace App\Filament\Resources\UploadHistoryResource\Pages;

use App\Filament\Resources\UploadHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUploadHistories extends ManageRecords
{
    protected static string $resource = UploadHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
