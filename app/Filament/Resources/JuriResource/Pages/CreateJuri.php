<?php

namespace App\Filament\Resources\JuriResource\Pages;

use App\Filament\Resources\JuriResource;
use App\Models\Juri;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateJuri extends CreateRecord
{
    protected static string $resource = JuriResource::class;

    protected function handleRecordCreation(array $data): Juri
    {
        // Validasi: jika tidak bisa menilai semua kategori, category_id harus diisi
        if (!$data['can_judge_all_categories'] && empty($data['category_id'])) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori harus dipilih ketika juri tidak bisa menilai semua kategori.',
            ]);
        }

        // Ambil data user dari form
        $userData = [
            'name' => $this->form->getComponents()[0]->getChildComponents()[0]->getState(),
            'email' => $this->form->getComponents()[0]->getChildComponents()[1]->getState(),
            'password' => Hash::make($this->form->getComponents()[0]->getChildComponents()[2]->getState()),
        ];

        // Create user account first
        $user = User::create($userData);

        // Jika juri universal, set category_id menjadi null
        $categoryId = $data['can_judge_all_categories'] ? null : $data['category_id'];

        // Then create juri record
        return Juri::create([
            'user_id' => $user->id,
            'category_id' => $categoryId,
            'expertise' => $data['expertise'],
            'max_evaluations' => $data['max_evaluations'],
            'is_active' => $data['is_active'],
            'can_judge_all_categories' => $data['can_judge_all_categories'],
        ]);
    }
}
