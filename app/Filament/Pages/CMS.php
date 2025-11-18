<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CMS extends Page implements HasForms
{
    use InteractsWithForms;

    // --- Konfigurasi Halaman ---
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static string $view = 'filament.pages.c-m-s';
    protected static ?string $title = 'Pengaturan Halaman Depan (CMS)';
    protected static ?string $navigationLabel = 'Halaman Depan';
    protected static ?string $navigationGroup = 'Manajemen Sistem';
    protected static ?int $navigationSort = 98; // Tampil di atas Pengaturan Umum

    // Daftar semua kunci (keys) pengaturan yang ada di WelcomeController.php
    protected const WELCOME_SETTINGS_CONFIG = [
        'primary_color' => ['default' => '#3b82f6', 'type' => 'color', 'group' => 'appearance'],
        'secondary_color' => ['default' => '#1e40af', 'type' => 'color', 'group' => 'appearance'],
        'contact_phone' => ['default' => '081335109003', 'type' => 'text', 'group' => 'contact'],
        'contact_email' => ['default' => 'info@nganjukkab.go.id', 'type' => 'email', 'group' => 'contact'],
        'contact_person' => ['default' => 'YULI', 'type' => 'text', 'group' => 'contact'],
        'competition_theme' => ['default' => 'Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing', 'type' => 'textarea', 'group' => 'competition'],
        'registration_location' => ['default' => 'di Bidang Litbang Bappeda Kab. Nganjuk (pada jam kerja)', 'type' => 'textarea', 'group' => 'competition'],
    ];

    public array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()->role !== 'juri';
    }

    /**
     * Mengisi formulir dengan nilai yang sudah tersimpan atau nilai default.
     */
    public function mount(): void
    {
        $settingsData = [];
        // Menggunakan Setting::getValue() dari model Setting Anda
        foreach (self::WELCOME_SETTINGS_CONFIG as $key => $config) {
            //
            $settingsData[$key] = Setting::getValue($key, $config['default']);
        }
        $this->form->fill($settingsData);
    }

    /**
     * Mendefinisikan skema formulir.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tampilan Warna')
                    ->description('Sesuaikan skema warna untuk halaman depan. Default: Warna Utama (#3b82f6), Warna Sekunder (#1e40af)')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Warna Utama')
                            ->required(),
                        ColorPicker::make('secondary_color')
                            ->label('Warna Sekunder')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Informasi Lomba')
                    ->description('Teks utama yang ditampilkan di bagian Hero dan Footer.')
                    ->schema([
                        Textarea::make('competition_theme')
                            ->label('Tema Lomba')
                            ->rows(3)
                            ->required()
                            ->maxLength(500),
                        Textarea::make('registration_location')
                            ->label('Lokasi Pendaftaran')
                            ->rows(3)
                            ->required()
                            ->maxLength(500),
                    ]),

                Section::make('Kontak Person')
                    ->description('Data kontak yang akan ditampilkan di bagian Footer.')
                    ->schema([
                        TextInput::make('contact_person')
                            ->label('Nama Kontak Person')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label('Nomor Telepon Kontak (WA)')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('contact_email')
                            ->label('Email Kontak')
                            ->email()
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    /**
     * Tombol aksi untuk menyimpan data.
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan Halaman Depan')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    /**
     * Logika untuk menyimpan data menggunakan Model Setting.
     */
    public function save(): void
    {
        try {
            $data = $this->form->getState();

            DB::beginTransaction();

            foreach ($data as $key => $value) {
                $config = self::WELCOME_SETTINGS_CONFIG[$key];

                // Menggunakan updateOrCreate untuk memastikan data Setting tersimpan
                // dengan kolom 'type' dan 'group' yang sesuai (seperti di SettingResource)
                //
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => $config['type'], // Mengisi kolom 'type' secara otomatis
                        'group' => $config['group'], // Mengisi kolom 'group' secara otomatis
                    ]
                );
            }

            DB::commit();

            Notification::make()
                ->title('Pengaturan berhasil disimpan!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal menyimpan pengaturan.')
                ->body('Terjadi kesalahan: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
