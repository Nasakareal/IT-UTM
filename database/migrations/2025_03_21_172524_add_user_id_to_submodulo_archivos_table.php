<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToSubmoduloArchivosTable extends Migration
{
    public function up()
    {
        Schema::table('submodulo_archivos', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('submodulo_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('submodulo_archivos', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

}
