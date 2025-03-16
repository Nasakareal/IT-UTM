<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubsectionsTable extends Migration
{
    public function up()
    {
        Schema::create('subsections', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('modulo_id')->constrained('modulos')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('subsections')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subsections');
    }
}
