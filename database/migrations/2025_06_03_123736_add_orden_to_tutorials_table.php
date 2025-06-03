<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdenToTutorialsTable extends Migration
{
    public function up()
    {
        Schema::table('tutorials', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('id');
        });
    }

    public function down()
    {
        Schema::table('tutorials', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }

}
