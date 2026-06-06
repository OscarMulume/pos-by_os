<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'restaurant_id' => 1,
            'name' => 'Administrateur',
            'username' => 'admin',
            'email' => 'admin@pos.local',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'restaurant_id' => 1,
            'name' => 'Caissier Kin Plazza',
            'username' => 'caissier1',
            'email' => 'caissier1@pos.local',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        User::create([
            'restaurant_id' => 2,
            'name' => 'Caissier Ngaliema',
            'username' => 'caissier2',
            'email' => 'caissier2@pos.local',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }
}
