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
        \DB::beginTransaction();

        try {

            \Log::info('Data received in CreateJuri:', $data);

            if (!$data['can_judge_all_categories'] && empty($data['category_id'])) {
                throw ValidationException::withMessages([
                    'category_id' => 'Kategori harus dipilih ketika juri tidak bisa menilai semua kategori.',
                ]);
            }

            $user = User::create([
                'name' => $data['name'] ?? $this->getUserDataFromForm('name'),
                'email' => $data['email'] ?? $this->getUserDataFromForm('email'),
                'role'=> 'juri',
                'password' => Hash::make($data['password'] ?? $this->getUserDataFromForm('password')),
            ]);
            $categoryId = $data['can_judge_all_categories'] ? null : $data['category_id'];

            \Log::info('Creating Juri with data:', [
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'can_judge_all_categories' => $data['can_judge_all_categories']
            ]);
            $juri = Juri::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'expertise' => $data['expertise'] ?? null,
                'max_evaluations' => $data['max_evaluations'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'can_judge_all_categories' => $data['can_judge_all_categories'] ?? false,
            ]);

            \DB::commit();
            return $juri;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating Juri: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getUserDataFromForm(string $field)
    {
        try {
            $section = $this->form->getComponents()[0];
            $components = $section->getChildComponents();

            foreach ($components as $component) {
                if ($component->getName() === $field) {
                    return $component->getState();
                }
            }

            return $this->form->getState()[$field] ?? null;

        } catch (\Exception $e) {
            \Log::error("Error getting $field from form: " . $e->getMessage());
            return null;
        }
    }
}
