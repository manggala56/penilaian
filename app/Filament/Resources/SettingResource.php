<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?string $navigationGroup = 'Manajemen Sistem';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Sistem')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Kunci')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'text' => 'Teks',
                                'number' => 'Angka',
                                'email' => 'Email',
                                'url' => 'URL',
                                'color' => 'Warna',
                                'textarea' => 'Teks Panjang',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('value')
                            ->label('Nilai')
                            ->required()
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['text', 'number', 'email', 'url'])),
                        Forms\Components\ColorPicker::make('value')
                            ->label('Nilai')
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'color'),
                        Forms\Components\Textarea::make('value')
                            ->label('Nilai')
                            ->required()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'textarea'),
                        Forms\Components\Select::make('group')
                            ->label('Grup')
                            ->options([
                                'general' => 'Umum',
                                'appearance' => 'Tampilan',
                                'contact' => 'Kontak',
                                'competition' => 'Lomba',
                            ])
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Kunci')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->limit(50),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),
                Tables\Columns\TextColumn::make('group')
                    ->label('Grup')
                    ->badge(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Grup')
                    ->options([
                        'general' => 'Umum',
                        'appearance' => 'Tampilan',
                        'contact' => 'Kontak',
                        'competition' => 'Lomba',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
