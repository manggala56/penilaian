<?php

namespace App\Filament\Resources\PenilaianJuriResource\Pages;

use App\Filament\Resources\PenilaianJuriResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenilaianJuri extends EditRecord
{
    protected static string $resource = PenilaianJuriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
