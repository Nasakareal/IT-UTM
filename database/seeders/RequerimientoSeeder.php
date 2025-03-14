<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Requerimiento;

class RequerimientoSeeder extends Seeder
{
    public function run()
    {
        // Verifica si ya existen registros antes de crearlos
        Requerimiento::firstOrCreate([
            'user_id' => 1,
            'nombre' => 'Informe del Primer Semestre sobre Acciones en Cultura de la Paz',
            'fecha_limite' => '2025-01-15',
            'estado' => 'pendiente',
        ]);

        Requerimiento::firstOrCreate([
            'user_id' => 1,
            'nombre' => 'Informe del Segundo Semestre sobre Acciones en Cultura de la Paz',
            'fecha_limite' => '2025-03-15',
            'estado' => 'pendiente',
        ]);
    }
}
