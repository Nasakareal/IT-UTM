<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('submodulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subsection_id')->constrained('subsections')->onDelete('cascade');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_limite')->nullable();
            $table->string('estatus')->default('pendiente');
            $table->string('acuse_pdf')->nullable();
            $table->string('documento_url')->nullable();
            $table->timestamps();
        });

        Schema::create('submodulo_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submodulo_id')->constrained('submodulos')->onDelete('cascade');
            $table->string('nombre');
            $table->string('ruta');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('submodulo_archivos');
        Schema::dropIfExists('submodulos');
    }
};
