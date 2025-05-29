<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdenToSubsectionsTable extends Migration
{
    public function up()
    {
        Schema::table('subsections', function (Blueprint $table) {
            $table->integer('orden')->default(0)->after('parent_id');
        });
    }

    public function down()
    {
        Schema::table('subsections', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
}
