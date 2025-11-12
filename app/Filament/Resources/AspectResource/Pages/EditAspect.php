<?php

namespace App\Filament\Resources\AspectResource\Pages;

use App\Filament\Resources\AspectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAspect extends EditRecord
{
    protected static string $resource = AspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
