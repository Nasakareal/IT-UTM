<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalificacionDocumentosTable extends Migration
{
    public function up()
    {
        Schema::create('calificacion_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id');
            $table->unsignedBigInteger('evaluador_id');
            $table->tinyInteger('calificacion');
            $table->timestamps();

            $table->foreign('documento_id')->references('id')->on('documentos_subidos')->onDelete('cascade');
            $table->foreign('evaluador_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['documento_id', 'evaluador_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('calificacion_documentos');
    }
}
