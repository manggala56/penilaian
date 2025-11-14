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
        // Ambil data user dari form components
        $userData = [
            'name' => $this->form->getComponents()[0]->getChildComponents()[0]->getState(),
            'email' => $this->form->getComponents()[0]->getChildComponents()[1]->getState(),
            'role' => 'juri',
            'password' => Hash::make($this->form->getComponents()[0]->getChildComponents()[2]->getState()),
        ];

        $user = User::create($userData);

        if ($data['can_judge_all_categories']) {
            $data['category_id'] = null;
        }

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
