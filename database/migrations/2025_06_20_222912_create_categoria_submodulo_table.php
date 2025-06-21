<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categoria_submodulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submodulo_id')->constrained('submodulos')->onDelete('cascade');
            $table->enum('categoria', [
                'Titular C', 'Titular B', 'Titular A',
                'Asociado C', 'Técnico Académico C',
                'Técnico Académico B', 'Técnico Académico A',
                'Profesor de Asignatura B'
            ]);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_submodulo');
    }
};
