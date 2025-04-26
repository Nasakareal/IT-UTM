<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSubmodulosTableAddFields extends Migration
{
    public function up()
    {
        Schema::table('submodulos', function (Blueprint $table) {
            $table->string('documento_solicitado', 125)->nullable()->after('descripcion');
            $table->dateTime('fecha_apertura')->nullable()->after('documento_solicitado');
            $table->dateTime('fecha_cierre')->nullable()->after('fecha_apertura');
        });
    }

    public function down()
    {
        Schema::table('submodulos', function (Blueprint $table) {
            $table->dropColumn(['documento_solicitado', 'fecha_apertura', 'fecha_cierre']);
        });
    }
}
