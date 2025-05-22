<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnidadToDocumentosSubidosTable extends Migration
{
    public function up()
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->unsignedTinyInteger('unidad')->nullable()->after('tipo_documento');
        });
    }

    public function down()
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->dropColumn('unidad');
        });
    }
}
