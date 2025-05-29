<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdenToSubmodulosTable extends Migration
{
    public function up()
    {
        Schema::table('submodulos', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('documento_url');
        });
    }

    public function down()
    {
        Schema::table('submodulos', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
}
