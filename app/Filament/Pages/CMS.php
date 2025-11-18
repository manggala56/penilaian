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

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static string $view = 'filament.pages.c-m-s';
    protected static ?string $title = 'Pengaturan Halaman Depan (CMS)';
    protected static ?string $navigationLabel = 'Halaman Depan';
    protected static ?string $navigationGroup = 'Manajemen Sistem';
    protected static ?int $navigationSort = 98;

    protected const WELCOME_SETTINGS_CONFIG = [
        // Tampilan
        'primary_color' => ['default' => '#3b82f6', 'type' => 'color', 'group' => 'appearance'],
        'secondary_color' => ['default' => '#1e40af', 'type' => 'color', 'group' => 'appearance'],

        // Konten Utama (Hero & Footer)
        'competition_title' => ['default' => 'Lomba Inovasi Kabupaten Nganjuk 2024', 'type' => 'text', 'group' => 'general'],
        'competition_theme' => ['default' => 'Inovasi sebagai sarana peningkatan peran potensi lokal untuk Nganjuk yang berdaya saing', 'type' => 'textarea', 'group' => 'competition'],
        'prize_total' => ['default' => 'TOTAL HADIAH 90 JUTA!', 'type' => 'text', 'group' => 'competition'],
        'footer_badge_date' => ['default' => '1–31 Oktober 2024', 'type' => 'text', 'group' => 'general'],

        // Kontak & Lokasi
        'contact_phone' => ['default' => '081335109003', 'type' => 'text', 'group' => 'contact'],
        'contact_email' => ['default' => 'info@nganjukkab.go.id', 'type' => 'email', 'group' => 'contact'],
        'contact_person' => ['default' => 'YULI', 'type' => 'text', 'group' => 'contact'],
        'registration_location' => ['default' => 'di Bidang Litbang Bappeda Kab. Nganjuk (pada jam kerja)', 'type' => 'textarea', 'group' => 'contact'],

        // Konfigurasi Teknis
        'min_description_char' => ['default' => '25', 'type' => 'number', 'group' => 'system'],
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tampilan & Header')
                    ->schema([
                        TextInput::make('competition_title')
                            ->label('Judul Lomba (Header)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        ColorPicker::make('primary_color')
                            ->label('Warna Utama (Tombol/Icon)')
                            ->required(),
                        ColorPicker::make('secondary_color')
                            ->label('Warna Sekunder (Hover/Bg)')
                            ->required(),
                    ])->columns(2),

                Section::make('Konten Kompetisi')
                    ->schema([
                        TextInput::make('prize_total')
                            ->label('Teks Total Hadiah')
                            ->placeholder('Contoh: TOTAL HADIAH 90 JUTA!')
                            ->required(),
                        TextInput::make('footer_badge_date')
                            ->label('Badge Tanggal (di Footer)')
                            ->placeholder('Contoh: 1–31 Oktober 2024')
                            ->required(),
                        Textarea::make('competition_theme')
                            ->label('Tema Lomba')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('min_description_char')
                            ->label('Minimal Karakter Deskripsi Inovasi')
                            ->numeric()
                            ->default(25)
                            ->helperText('Batas minimal panjang karakter saat peserta mengisi deskripsi.')
                            ->required(),
                    ])->columns(2),

                Section::make('Kontak & Lokasi')
                    ->schema([
                        TextInput::make('contact_person')
                            ->label('Nama Kontak Person'),
                        TextInput::make('contact_phone')
                            ->label('No. WA Kontak'),
                        TextInput::make('contact_email')
                            ->label('Email Kontak')
                            ->email(),
                        Textarea::make('registration_location')
                            ->label('Lokasi Pendaftaran')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan Halaman Depan')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            DB::beginTransaction();

            foreach ($data as $key => $value) {
                $config = self::WELCOME_SETTINGS_CONFIG[$key];
                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => $config['type'],
                        'group' => $config['group'],
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
