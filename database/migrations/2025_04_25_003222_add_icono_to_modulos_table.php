<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIconoToModulosTable extends Migration
{
    public function up()
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->string('icono')->nullable();
        });
    }

    public function down()
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->dropColumn('icono');
        });
    }

}
