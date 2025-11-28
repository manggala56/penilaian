<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Str::random(12);
        $email = 'superpenilaian@admin.com';

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($password),
                'role' => 'superadmin',
            ]
        );

        $this->command->info('Superadmin created successfully.');
        $this->command->info('Email: ' . $email);
        $this->command->info('Password: ' . $password);
    }
}
