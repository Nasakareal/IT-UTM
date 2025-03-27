<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmoduloUsuariosTable extends Migration
{
    public function up()
    {
       Schema::create('submodulo_usuario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('submodulo_id');
            $table->string('estatus')->default('Pendiente');
            $table->timestamps();

            $table->unique(['user_id', 'submodulo_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('submodulo_id')->references('id')->on('submodulos')->onDelete('cascade');
        });

    }

    public function down()
    {
        Schema::dropIfExists('submodulo_usuario');
    }

}
