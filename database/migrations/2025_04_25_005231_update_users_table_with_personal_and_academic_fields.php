<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTableWithPersonalAndAcademicFields extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido_paterno')->nullable()->after('name');
            $table->string('apellido_materno')->nullable()->after('apellido_paterno');
            $table->string('nombres')->after('apellido_materno');
            $table->string('curp', 18)->after('nombres');
            $table->string('correo_personal')->after('correo_institucional');

            $table->enum('categoria', [
                'Titular C', 'Titular B', 'Titular A',
                'Asociado C', 'Técnico Académico C',
                'Técnico Académico B', 'Técnico Académico A',
                'Profesor de Asignatura B'
            ])->after('correo_personal');

            $table->enum('caracter', ['Indeterminado', 'Determinado'])->after('categoria');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'apellido_paterno',
                'apellido_materno',
                'nombres',
                'curp',
                'correo_personal',
                'categoria',
                'caracter'
            ]);
        });
    }
}
