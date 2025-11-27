<?php

namespace App\Filament\Resources\JuriResource\Pages;

use App\Filament\Resources\JuriResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJuri extends EditRecord
{
    protected static string $resource = JuriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('changePassword')
                ->label('Ganti Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('new_password')
                        ->label('Password Baru')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->rule('confirmed'),
                    \Filament\Forms\Components\TextInput::make('new_password_confirmation')
                        ->label('Konfirmasi Password Baru')
                        ->password()
                        ->required()
                        ->minLength(8),
                ])
                ->action(function (JuriResource $resource, $record, array $data) {
                    $record->user->update([
                        'password' => \Illuminate\Support\Facades\Hash::make($data['new_password']),
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Password berhasil diubah')
                        ->success()
                        ->send();
                }),
        ];
    }
}
