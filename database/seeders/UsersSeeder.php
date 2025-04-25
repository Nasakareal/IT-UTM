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
                'nombres' => 'Mario',
                'apellido_paterno' => 'Bautista',
                'apellido_materno' => null,
                'curp' => 'BAUM001122HMCXXX01',
                'correo_institucional' => 'admin@admin.com',
                'correo_personal' => 'mario@gmail.com',
                'categoria' => 'Titular A',
                'caracter' => 'Indeterminado',
                'password' => Hash::make('123'),
                'role' => 'Administrador'
            ],
            [
                'name' => 'Alexei Rodriguez',
                'nombres' => 'Alexei',
                'apellido_paterno' => 'Rodriguez',
                'apellido_materno' => 'Hernández',
                'curp' => 'ROHA001122HMCXXX02',
                'correo_institucional' => 'jorgerodriguez@ut-morelia.edu.mx',
                'correo_personal' => 'alexei@gmail.com',
                'categoria' => 'Titular B',
                'caracter' => 'Determinado',
                'password' => Hash::make('12345'),
                'role' => 'Administrador'
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['correo_institucional' => $userData['correo_institucional']],
                [
                    'name' => $userData['name'],
                    'nombres' => $userData['nombres'],
                    'apellido_paterno' => $userData['apellido_paterno'],
                    'apellido_materno' => $userData['apellido_materno'],
                    'curp' => $userData['curp'],
                    'correo_personal' => $userData['correo_personal'],
                    'categoria' => $userData['categoria'],
                    'caracter' => $userData['caracter'],
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
