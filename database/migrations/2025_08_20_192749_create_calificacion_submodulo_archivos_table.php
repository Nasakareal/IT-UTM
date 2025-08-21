<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificacion_submodulo_archivos', function (Blueprint $table) {
            // Si manejas engine/charset específicos:
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_spanish_ci';

            $table->id();

            // Archivo de submódulo al que se califica
            $table->unsignedBigInteger('submodulo_archivo_id');

            // Evaluador (user_id de quien califica)
            $table->unsignedBigInteger('evaluador_id');

            // Calificación 0..10
            $table->unsignedTinyInteger('calificacion');

            $table->timestamps();

            // Evita calificar dos veces el mismo archivo por el mismo evaluador
            $table->unique(['submodulo_archivo_id', 'evaluador_id'], 'uniq_submod_evaluador');

            // Índices útiles
            $table->index('submodulo_archivo_id', 'idx_csa_submod');
            $table->index('evaluador_id', 'idx_csa_eval');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificacion_submodulo_archivos');
    }
};
