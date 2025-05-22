<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirmaFieldsToDocumentosSubidosTable extends Migration
{
    public function up()
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->text('firma_sat')->nullable();
            $table->timestamp('fecha_firma')->nullable();
        });
    }


    public function down()
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->text('firma_sat')->nullable();
            $table->timestamp('fecha_firma')->nullable();
        });
    }
}
