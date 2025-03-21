<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Correspondencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CorrespondenciaSeeder extends Seeder
{
    public function run()
    {
        // Obtener usuarios existentes
        $usuarios = User::all();

        if ($usuarios->isEmpty()) {
            $this->command->warn("No hay usuarios en la base de datos. Ejecuta primero el seeder de usuarios.");
            return;
        }

        // Crear correspondencias de prueba
        foreach ($usuarios as $usuario) {
            Correspondencia::create([
                'remitente' => $usuario->name . ' / ' . 'Cargo Ejemplo',
                'referencia' => 'REF-' . Str::random(6),
                'oficio' => 'OFICIO-' . rand(100, 999) . '/' . date('Y'),
                'tipo_documento' => ['Oficio', 'Nota Informativa', 'Otro'][array_rand(['Oficio', 'Nota Informativa', 'Otro'])],
                'fecha_elaboracion' => Carbon::now()->subDays(rand(1, 30)),
                'tema' => 'Solicitud Ejemplo ' . rand(1, 100),
                'descripcion_asunto' => 'Esta es una descripción de prueba para la correspondencia generada.',
                'archivo_pdf' => null,
                'observaciones' => 'Observaciones de prueba para correspondencia.',
                'estado' => ['En proceso', 'Pendiente', 'Concluido'][array_rand(['En proceso', 'Pendiente', 'Concluido'])],
                'usuario_id' => $usuario->id
            ]);
        }

        $this->command->info("Seeder de Correspondencias ejecutado correctamente.");
    }
}
