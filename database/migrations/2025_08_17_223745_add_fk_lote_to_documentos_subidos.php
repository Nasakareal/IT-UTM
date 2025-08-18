<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `firma_lotes` ENGINE=InnoDB");
        DB::statement("ALTER TABLE `firma_lotes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci");

        if (Schema::hasColumn('documentos_subidos', 'lote_id')) {
            Schema::table('documentos_subidos', function (Blueprint $table) {
            });

            try {
                DB::statement("
                    ALTER TABLE `documentos_subidos`
                    ADD CONSTRAINT `documentos_subidos_lote_id_foreign`
                    FOREIGN KEY (`lote_id`) REFERENCES `firma_lotes`(`id`)
                    ON DELETE SET NULL
                ");
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        try {
            DB::statement("
                ALTER TABLE `documentos_subidos`
                DROP FOREIGN KEY `documentos_subidos_lote_id_foreign`
            ");
        } catch (\Throwable $e) { /* no existía */ }
    }
};
