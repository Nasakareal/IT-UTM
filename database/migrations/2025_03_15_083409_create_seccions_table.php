<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeccionsTable extends Migration
{

    public function up()
    {
        Schema::create('seccions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('color')->default('#009688');
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('seccions');
    }
}
