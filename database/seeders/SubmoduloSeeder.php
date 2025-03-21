<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subsection;
use App\Models\Submodulo;
use Carbon\Carbon;

class SubmoduloSeeder extends Seeder
{
    public function run()
    {
        // Obtener subsecciones existentes
        $subsecciones = Subsection::all();

        if ($subsecciones->isEmpty()) {
            $this->command->warn("No hay subsecciones en la base de datos. Ejecuta primero el seeder de subsecciones.");
            return;
        }

        // Crear submódulos en cada subsección
        foreach ($subsecciones as $subseccion) {
            Submodulo::create([
                'subsection_id' => $subseccion->id,
                'titulo'        => 'Submódulo de ' . $subseccion->nombre,
                'descripcion'   => 'Descripción del submódulo en ' . $subseccion->nombre,
                'fecha_limite'  => Carbon::now()->addDays(rand(5, 20)),
                'estatus'       => 'pendiente',
                'acuse_pdf'     => null,
                'documento_url' => null
            ]);
        }

        $this->command->info("Seeder de Submódulos ejecutado correctamente.");
    }
}
