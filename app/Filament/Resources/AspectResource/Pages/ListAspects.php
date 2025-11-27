<?php

namespace App\Filament\Resources\AspectResource\Pages;

use App\Filament\Resources\AspectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Competition;

class ListAspects extends ListRecords
{
    protected static string $resource = AspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Semua'),
        ];

        $competitions = Competition::all();

        foreach ($competitions as $competition) {
            $tabs[$competition->id] = Tab::make($competition->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('category', fn ($q) => $q->where('competition_id', $competition->id)));
        }

        return $tabs;
    }
}
