<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModulosTable extends Migration
{

    public function up()
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('anio')->nullable();
            $table->string('categoria')->nullable();
            $table->string('color')->default('#009688');
            $table->text('descripcion')->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('modulos');
    }
}
