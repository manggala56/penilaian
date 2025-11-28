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
                            ->options(fn () => $this->getAllowedCompetitions())
                            ->default(fn () => $this->getAllowedCompetitions()->keys()->first())
                            ->visible(fn () => $this->getAllowedCompetitions()->count() > 1)
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getAllowedCompetitions()
    {
        $user = auth()->user();
        $query = \App\Models\Competition::where('is_active', true);

        if ($user->role === 'juri') {
            $juri = \App\Models\Juri::where('user_id', $user->id)->first();
            if ($juri && !$juri->can_judge_all_categories) {
                $competitionIds = $juri->categories->pluck('competition_id')->unique();
                $query->whereIn('id', $competitionIds);
            }
        }
        
        return $query->pluck('name', 'id');
    }
}
