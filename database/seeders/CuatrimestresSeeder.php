<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CuatrimestresSeeder extends Seeder
{
    public function run()
    {
        DB::table('cuatrimestres')->insert([
            [
                'nombre' => 'ENERO-ABRIL 2025',
                'fecha_inicio' => '2025-01-01',
                'fecha_fin' => '2025-04-30',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'MAYO-AGOSTO 2025',
                'fecha_inicio' => '2025-05-01',
                'fecha_fin' => '2025-08-31',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'SEPTIEMBRE-DICIEMBRE 2025',
                'fecha_inicio' => '2025-09-01',
                'fecha_fin' => '2025-12-31',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
