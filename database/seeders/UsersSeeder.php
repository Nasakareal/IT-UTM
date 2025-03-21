<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Mario Bautista',
                'email' => 'admin@admin.com',
                'password' => Hash::make('123'),
                'role' => 'Administrador'
            ],
            [
                'name' => 'Alexei Rodriguez',
                'email' => 'jorgerodriguez@ut-morelia.edu.mx',
                'password' => Hash::make('12345'),
                'role' => 'Administrador'
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if (isset($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }
}
