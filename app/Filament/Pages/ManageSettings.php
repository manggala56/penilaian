<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ManageSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Pengaturan';
    protected static ?string $navigationGroup = 'Manajemen Sistem';
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()->role === 'admin' || Auth::user()->role === 'superadmin';
    }

    public function mount(): void
    {
        $data = [];

        // Load global settings
        $globalSetting = Setting::whereNull('competition_id')->where('key', 'judging_period')->first();
        // Fallback for global
        if (!$globalSetting) {
             $globalSetting = Setting::whereNull('competition_id')->first();
        }

        if ($globalSetting) {
            $data['global_judging_start'] = $globalSetting->judging_start;
            $data['global_judging_end'] = $globalSetting->judging_end;
        }

        // Load competition settings
        $competitions = \App\Models\Competition::all();
        foreach ($competitions as $competition) {
            $setting = Setting::where('competition_id', $competition->id)->where('key', 'judging_period')->first();
            // Fallback
            if (!$setting) {
                $setting = Setting::where('competition_id', $competition->id)->whereNotNull('judging_start')->first();
            }

            if ($setting) {
                $data["competition_{$competition->id}_judging_start"] = $setting->judging_start;
                $data["competition_{$competition->id}_judging_end"] = $setting->judging_end;
            }
        }

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        $schema = [];

        // Global Section
        $schema[] = Forms\Components\Section::make('Pengaturan Global')
            ->description('Pengaturan default jika tidak ada pengaturan khusus per lomba.')
            ->schema([
                Forms\Components\DateTimePicker::make('global_judging_start')
                    ->label('Mulai Penilaian'),
                Forms\Components\DateTimePicker::make('global_judging_end')
                    ->label('Selesai Penilaian')
                    ->after('global_judging_start'),
            ])->columns(2)
            ->collapsible();

        // Competition Sections
        $competitions = \App\Models\Competition::all();
        foreach ($competitions as $competition) {
            $schema[] = Forms\Components\Section::make($competition->name)
                ->schema([
                    Forms\Components\DateTimePicker::make("competition_{$competition->id}_judging_start")
                        ->label('Mulai Penilaian'),
                    Forms\Components\DateTimePicker::make("competition_{$competition->id}_judging_end")
                        ->label('Selesai Penilaian')
                        ->after("competition_{$competition->id}_judging_start"),
                ])->columns(2)
                ->collapsible();
        }

        return $form
            ->schema($schema)
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $key = 'judging_period';

        // Save Global
        $globalAttributes = [
            'key' => $key,
            'competition_id' => null,
            'judging_start' => $data['global_judging_start'] ?? null,
            'judging_end' => $data['global_judging_end'] ?? null,
            'type' => 'datetime_range',
            'group' => 'competition',
        ];

        // Update or Create Global
        // We need to be careful not to create duplicates if we are using updateOrCreate with just key/competition_id
        // But since we have a unique constraint on (key, competition_id), updateOrCreate is safe.
        Setting::updateOrCreate(
            ['key' => $key, 'competition_id' => null],
            $globalAttributes
        );

        // Save Competitions
        $competitions = \App\Models\Competition::all();
        foreach ($competitions as $competition) {
            $start = $data["competition_{$competition->id}_judging_start"] ?? null;
            $end = $data["competition_{$competition->id}_judging_end"] ?? null;

            // Only save if at least one date is set, or if we want to clear existing settings?
            // If both are null, maybe we should delete the setting row to revert to global?
            // Or just save nulls. Saving nulls is fine, getJudgingStatus handles it.
            
            $attributes = [
                'key' => $key,
                'competition_id' => $competition->id,
                'judging_start' => $start,
                'judging_end' => $end,
                'type' => 'datetime_range',
                'group' => 'competition',
            ];

            Setting::updateOrCreate(
                ['key' => $key, 'competition_id' => $competition->id],
                $attributes
            );
        }

        Notification::make()
            ->success()
            ->title('Pengaturan berhasil disimpan')
            ->send();
    }
}
