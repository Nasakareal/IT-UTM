<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firma_lotes', function (Blueprint $table) {
            $table->id();

            // Profesor que firma (users.id)
            $table->unsignedBigInteger('user_id');

            // Claves lógicas del paquete que se firmó
            $table->string('materia', 100);
            $table->string('grupo', 125);
            $table->unsignedInteger('unidad');

            // Datos de firma de lote
            $table->timestamp('firmado_at');
            $table->string('acuse_lote', 125);
            $table->unsignedSmallInteger('total_documentos');

            // Metadatos del certificado (.p12)
            $table->string('certificado_cn', 190)->nullable();
            $table->string('certificado_rfc', 30)->nullable();

            $table->timestamps();

            // FK y aceleradores
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'materia', 'grupo', 'unidad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firma_lotes');
    }
};
