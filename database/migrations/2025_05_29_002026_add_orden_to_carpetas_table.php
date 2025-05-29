<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdenToCarpetasTable extends Migration

{
    public function up()
    {
        Schema::table('carpetas', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('color');
        });
    }

    public function down()
    {
        Schema::table('carpetas', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
}
