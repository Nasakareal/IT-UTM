<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `documentos_subidos` ENGINE=InnoDB");
        DB::statement("ALTER TABLE `documentos_subidos` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");

        if (!Schema::hasColumn('documentos_subidos', 'hash_sha256')) {
            Schema::table('documentos_subidos', function (Blueprint $table) {
                $table->string('hash_sha256', 64)->nullable()->after('archivo');
            });
        }

        if (!Schema::hasColumn('documentos_subidos', 'firma_sig')) {
            Schema::table('documentos_subidos', function (Blueprint $table) {
                $table->string('firma_sig', 125)->nullable()->after('hash_sha256');
            });
        }

        if (!Schema::hasColumn('documentos_subidos', 'lote_id')) {
            Schema::table('documentos_subidos', function (Blueprint $table) {
                $table->unsignedBigInteger('lote_id')->nullable()->after('firma_sig');
            });
        }

        try {
            DB::statement("CREATE INDEX docsub_idx_user_materia_grupo_unidad ON documentos_subidos (user_id, materia, grupo, unidad)");
        } catch (\Throwable $e) { /* ya existía */ }

        try {
            DB::statement("CREATE INDEX docsub_idx_tipo ON documentos_subidos (tipo_documento)");
        } catch (\Throwable $e) { /* ya existía */ }

    }

    public function down(): void
    {
        try { DB::statement("DROP INDEX docsub_idx_user_materia_grupo_unidad ON documentos_subidos"); } catch (\Throwable $e) {}
        try { DB::statement("DROP INDEX docsub_idx_tipo ON documentos_subidos"); } catch (\Throwable $e) {}

        if (Schema::hasColumn('documentos_subidos', 'lote_id')) {
            Schema::table('documentos_subidos', function (Blueprint $table) {
                $table->dropColumn('lote_id');
            });
        }
        if (Schema::hasColumn('documentos_subidos', 'firma_sig')) {
            Schema::table('documentos_subidos', function (Blueprint $table) {
                $table->dropColumn('firma_sig');
            });
        }
        if (Schema::hasColumn('documentos_subidos', 'hash_sha256')) {
            Schema::table('documentos_subidos', function (Blueprint $table) {
                $table->dropColumn('hash_sha256');
            });
        }
    }
};
