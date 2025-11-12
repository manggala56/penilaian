<?php

namespace App\Filament\Resources\JuriResource\Pages;

use App\Filament\Resources\JuriResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJuri extends EditRecord
{
    protected static string $resource = JuriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
