<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeccionIdToModulosTable extends Migration
{

    public function up()
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->foreignId('seccion_id')
                  ->nullable()
                  ->constrained()
                  ->cascadeOnDelete();
        });
    }


    public function down()
    {
        Schema::table('modulos', function (Blueprint $table) {
            //
        });
    }
}
