<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('competition_id')
                            ->label('Pilih Lomba')
                            ->options(\App\Models\Competition::pluck('name', 'id'))
                            ->default(fn () => \App\Models\Competition::where('is_active', true)->value('id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),
            ]);
    }
}
