<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubsectionIdToCarpetasTable extends Migration
{
    public function up()
    {
        Schema::table('carpetas', function (Blueprint $table) {
            $table->foreignId('subsection_id')
                  ->nullable()
                  ->constrained('subsections')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('carpetas', function (Blueprint $table) {
            $table->dropForeign(['subsection_id']);
            $table->dropColumn('subsection_id');
        });
    }
}
