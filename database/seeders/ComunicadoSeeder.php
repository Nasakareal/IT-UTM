<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comunicado;

class ComunicadoSeeder extends Seeder
{
    public function run()
    {
        // Comunicado solo de texto
        Comunicado::create([
            'titulo'    => 'Comunicado Solo de Texto',
            'contenido' => 'Ola k ase.',
            'tipo'      => 'texto',
            'fecha'     => now(),
        ]);

        // Comunicado solo de imagen
        Comunicado::create([
            'titulo'      => 'Comunicado Solo de Imagen',
            'contenido'   => null, // Sin contenido textual
            'tipo'        => 'imagen',
            'ruta_imagen' => 'comunicados/imagen1.jpg',
            'fecha'       => now(),
        ]);

        // Comunicado mixto (texto e imagen)
        Comunicado::create([
            'titulo'    => 'Comunicado Mixto',
            'contenido' => 'Vasir?.',
            'tipo'      => 'mixto',
            'ruta_imagen' => 'comunicados/imagen2.jpg',
            'fecha'     => now(),
        ]);
    }
}
