<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calificacion_submodulo_archivos', function (Blueprint $table) {
            $table->unsignedBigInteger('profesor_id')->nullable()->after('evaluador_id')->index();
            $table->foreign('profesor_id', 'csa_profesor_id_fk')
                  ->references('id')->on('users')
                  ->onUpdate('cascade')
                  ->onDelete('set null');
        });

        DB::statement("
            UPDATE calificacion_submodulo_archivos c
            JOIN submodulo_archivos sa ON sa.id = c.submodulo_archivo_id
            SET c.profesor_id = sa.user_id
            WHERE c.profesor_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('calificacion_submodulo_archivos', function (Blueprint $table) {
            $table->dropForeign('csa_profesor_id_fk');
            $table->dropColumn('profesor_id');
        });
    }
};
