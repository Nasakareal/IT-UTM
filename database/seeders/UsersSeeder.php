<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run()
    {
        // Crear el usuario
        $user = User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Mario Bautista',
            'password' => Hash::make('123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asignar el rol de "Administrador"
        $user->assignRole('Administrador');
    }
}
