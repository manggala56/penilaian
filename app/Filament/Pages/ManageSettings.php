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
        return Auth::user()->role === 'admin';
    }

    public function mount(): void
    {
        $setting = Setting::firstOrNew();
        $this->form->fill($setting->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Jadwal Penjurian')
                    ->description('Atur durasi waktu penilaian bagi Juri.')
                    ->schema([
                        Forms\Components\DateTimePicker::make('judging_start')
                            ->label('Mulai Penilaian')
                            ->required(),
                        Forms\Components\DateTimePicker::make('judging_end')
                            ->label('Selesai Penilaian')
                            ->required()
                            ->after('judging_start'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $setting = Setting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            Setting::create($data);
        }

        Notification::make()
            ->success()
            ->title('Pengaturan berhasil disimpan')
            ->send();
    }
}
