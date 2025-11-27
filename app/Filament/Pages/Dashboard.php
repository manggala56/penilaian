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
                            ->options(function () {
                                $user = auth()->user();
                                if ($user->role === 'juri') {
                                    $juri = \App\Models\Juri::where('user_id', $user->id)->first();
                                    if ($juri && !$juri->can_judge_all_categories) {
                                        $competitionIds = $juri->categories->pluck('competition_id')->unique();
                                        return \App\Models\Competition::whereIn('id', $competitionIds)->pluck('name', 'id');
                                    }
                                }
                                return \App\Models\Competition::pluck('name', 'id');
                            })
                            ->default(fn () => \App\Models\Competition::where('is_active', true)->value('id'))
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),
            ]);
    }
}
