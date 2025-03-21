<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('correspondencias', function (Blueprint $table) {
            $table->id();
            $table->string('remitente');
            $table->string('referencia')->nullable();
            $table->string('oficio')->nullable();
            $table->enum('tipo_documento', ['OFICIO', 'NOTA INFORMATIVA', 'OTRO']);
            $table->date('fecha_elaboracion');
            $table->string('tema');
            $table->text('descripcion_asunto');
            $table->string('archivo_pdf')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['EN PROCESO', 'PENDIENTE', 'CONCLUIDO'])->default('En proceso');
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondencias');
    }
};
