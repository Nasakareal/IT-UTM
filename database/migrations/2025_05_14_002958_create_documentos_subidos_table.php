<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentosSubidosTable extends Migration
{
    public function up()
    {
        Schema::create('documentos_subidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('materia', 100);
            $table->integer('unidad');
            $table->string('tipo_documento', 100);
            $table->string('archivo');
            $table->timestamps();

            $table->unique(['user_id', 'materia', 'unidad', 'tipo_documento'], 'documento_unico');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('documentos_subidos');
    }
}
