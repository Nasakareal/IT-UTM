<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            //UsersSeeder::class,
            //RequerimientoSeeder::class,
            //ComunicadoSeeder::class,
            //ModuloSeeder::class,
            //SubsectionSeeder::class,
            //CarpetaSeeder::class,
            //SubmoduloSeeder::class,
            //CorrespondenciaSeeder::class,
            //CuatrimestreSeeder::class,
        ]);
    }
}
