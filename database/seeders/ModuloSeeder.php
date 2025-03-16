<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seccion;
use App\Models\Modulo;

class ModuloSeeder extends Seeder
{
    public function run()
    {
        // 1. Creamos las secciones
        $academico = Seccion::create([
            'nombre' => 'Técnico Académico',
            'slug'   => 'tecnico-academico',
            'color'  => '#670D37',
        ]);

        $administrativo = Seccion::create([
            'nombre' => 'Técnico Administrativo',
            'slug'   => 'tecnico-administrativo',
            'color'  => '#009688', 
        ]);

        $Presupuestarios = Seccion::create([
            'nombre' => 'Programas Presupuestarios',
            'slug'   => 'Programas Presupuestarios',
            'color'  => '#009688', 
        ]);

        $Materiales_y_Encuestas = Seccion::create([
            'nombre' => 'Materiales y Encuestas',
            'slug'   => 'Materiales y Encuestas',
            'color'  => '#009688', 
        ]);

        // 2. Creamos los módulos, asignándolos a las secciones
        Modulo::create([
            'titulo'      => 'Programas de Educación Técnica',
            'anio'        => '2025',
            'categoria'   => 'Técnico Académico',
            'color'       => '#009688',
            'descripcion' => 'Descripción breve para Técnico Académico.',
            'link'        => 'https://IT-UTM.COM/tecnico-academico',
            'seccion_id'  => $academico->id,
        ]);

        Modulo::create([
            'titulo'      => 'Cursos Administrativos',
            'anio'        => '2024',
            'categoria'   => 'Técnico Administrativo',
            'color'       => '#009688',
            'descripcion' => 'Descripción breve para Técnico Administrativo.',
            'link'        => 'https://T-UTM.COM/tecnico-administrativo',
            'seccion_id'  => $administrativo->id,
        ]);

        Modulo::create([
            'titulo'      => 'Programas Presupuestarios',
            'anio'        => '2025',
            'categoria'   => 'Programas Presupuestarios',
            'color'       => '#009688',
            'descripcion' => 'Descripción breve para Programas Presupuestarios.',
            'link'        => 'https://T-UTM.COM/tecnico-administrativo',
            'seccion_id'  => $Presupuestarios->id,
        ]);

        Modulo::create([
            'titulo'      => 'Materiales y Encuestas',
            'anio'        => '2025',
            'categoria'   => 'Materiales y Encuestas',
            'color'       => '#009688',
            'descripcion' => 'Descripción breve para Materiales y Encuestas.',
            'link'        => 'https://T-UTM.COM/tecnico-administrativo',
            'seccion_id'  => $Materiales_y_Encuestas->id,
        ]);
    }
}
