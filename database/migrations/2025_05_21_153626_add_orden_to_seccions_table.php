<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdenToSeccionsTable extends Migration
{
    public function up()
    {
        Schema::table('seccions', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('color');
        });
    }

    public function down()
    {
        Schema::table('seccions', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
}
