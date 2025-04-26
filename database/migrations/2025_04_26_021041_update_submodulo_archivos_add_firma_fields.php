<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSubmoduloArchivosAddFirmaFields extends Migration
{
    public function up()
    {
        Schema::table('submodulo_archivos', function (Blueprint $table) {
            $table->text('firma_sat')->nullable()->after('ruta');
            $table->timestamp('fecha_firma')->nullable()->after('firma_sat');
        });
    }

    public function down()
    {
        Schema::table('submodulo_archivos', function (Blueprint $table) {
            $table->dropColumn(['firma_sat', 'fecha_firma']);
        });
    }
}
