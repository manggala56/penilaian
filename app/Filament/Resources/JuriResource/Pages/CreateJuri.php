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

    protected function handleRecordCreation(array $data): Juri
    {
        // Data untuk membuat user baru
        $userData = [
            'name' => request('name'), // Ambil dari request langsung
            'email' => request('email'),
            'password' => Hash::make(request('password')),
        ];

        // Create user account first
        $user = User::create($userData);

        // Jika juri universal, set category_id menjadi null
        if ($data['can_judge_all_categories']) {
            $data['category_id'] = null;
        }

        // Then create juri record
        return Juri::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'expertise' => $data['expertise'],
            'max_evaluations' => $data['max_evaluations'],
            'is_active' => $data['is_active'],
            'can_judge_all_categories' => $data['can_judge_all_categories'],
        ]);
    }
}
