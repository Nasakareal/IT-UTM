<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarpetasTable extends Migration
{
    public function up()
    {
        Schema::create('carpetas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('color')->nullable();
            // Para permitir subcarpetas
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('carpetas')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('carpetas');
    }
}
