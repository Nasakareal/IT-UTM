<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['apellido_paterno', 'apellido_materno', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido_paterno', 125)->nullable();
            $table->string('apellido_materno', 125)->nullable();
            $table->string('name', 125);
        });
    }
};
