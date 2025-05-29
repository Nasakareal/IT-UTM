<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAcusePdfToSubmoduloArchivosTable extends Migration
{
    public function up()
    {
        Schema::table('submodulo_archivos', function (Blueprint $table) {
            $table->string('acuse_pdf')->nullable()->after('fecha_firma');
        });
    }

    public function down()
    {
        Schema::table('submodulo_archivos', function (Blueprint $table) {
            $table->dropColumn('acuse_pdf');
        });
    }
}
