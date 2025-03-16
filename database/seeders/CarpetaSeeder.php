<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Carpeta;
use App\Models\Subsection;

class CarpetaSeeder extends Seeder
{
    public function run()
    {
        // Buscar la subsección que corresponde a "LICENCIATURA EN INGENIERÍA EN MANTENIMIENTO INDUSTRIAL"
        $subsecLic = Subsection::where('nombre', 'LICENCIATURA EN INGENIERÍA EN MANTENIMIENTO INDUSTRIAL')->first();

        // 1. Carpeta raíz: Licenciatura
        $licenciatura = Carpeta::create([
            'nombre'         => 'LICENCIATURA EN INGENIERÍA EN MANTENIMIENTO INDUSTRIAL',
            'color'          => '#2AB7CA',
            'parent_id'      => null,
            'subsection_id'  => $subsecLic ? $subsecLic->id : null,
        ]);

        // 2. Subcarpeta: Técnico Superior
        $tecnicoSuperior = Carpeta::create([
            'nombre'         => 'TÉCNICO SUPERIOR UNIVERSITARIO EN MANTENIMIENTO INDUSTRIAL',
            'color'          => '#FE4A49',
            'parent_id'      => $licenciatura->id,
            'subsection_id'  => $subsecLic ? $subsecLic->id : null,
        ]);

        // 3. Subcarpetas de Técnico Superior
        Carpeta::create([
            'nombre'         => 'PRODUCTOS CURRICULARES',
            'color'          => '#FED766',
            'parent_id'      => $tecnicoSuperior->id,
            'subsection_id'  => $subsecLic ? $subsecLic->id : null,
        ]);

        Carpeta::create([
            'nombre'         => 'PROGRAMAS DE ASIGNATURA',
            'color'          => '#A4036F',
            'parent_id'      => $tecnicoSuperior->id,
            'subsection_id'  => $subsecLic ? $subsecLic->id : null,
        ]);
    }
}
