<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('estado')->nullable()->after('password');
            $table->string('foto_perfil')->nullable()->after('estado');
            $table->string('area')->nullable()->after('foto_perfil');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['estado', 'foto_perfil', 'area']);
        });
    }
};
