<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrupoToDocumentosSubidosTable extends Migration
{
    public function up()
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->string('grupo')->after('materia');
        });
    }

    public function down()
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->dropColumn('grupo');
        });
    }

}
