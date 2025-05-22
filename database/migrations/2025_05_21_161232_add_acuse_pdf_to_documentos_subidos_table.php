<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAcusePdfToDocumentosSubidosTable extends Migration
{
    public function up()
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->string('acuse_pdf')->nullable()->after('archivo');
        });
    }

    public function down()
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->dropColumn('acuse_pdf');
        });
    }

}
