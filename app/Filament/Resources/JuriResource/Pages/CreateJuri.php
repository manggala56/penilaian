<?php

namespace App\Filament\Resources\JuriResource\Pages;

use App\Filament\Resources\JuriResource;
use App\Models\Juri;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateJuri extends CreateRecord
{
    protected static string $resource = JuriResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'juri',
            'password' => Hash::make($data['password']),
        ]);

        if ($data['can_judge_all_categories']) {
            $data['category_id'] = null;
        }

        $data['user_id'] = $user->id;

        unset($data['name'], $data['email'], $data['password']);

        return $data;
    }
}
