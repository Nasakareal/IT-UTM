<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('submodulos', function (Blueprint $table) {
            if (!Schema::hasColumn('submodulos','quarter_name')) {
                $table->string('quarter_name', 80)->nullable()->after('subsection_id');
            }
            if (!Schema::hasColumn('submodulos','plantilla_path')) {
                $table->string('plantilla_path')->nullable()->after('documento_solicitado');
            }
            if (!Schema::hasColumn('submodulos','orden')) {
                $table->unsignedInteger('orden')->default(0)->after('documento_url');
            }
        });

        // Único por (quarter_name, titulo) para no duplicar el mismo submódulo cada cuatri
        Schema::table('submodulos', function (Blueprint $table) {
            $table->unique(['quarter_name','titulo'],'uq_submodulo_quarter_titulo');
        });
    }

    public function down(): void {
        Schema::table('submodulos', function (Blueprint $table) {
            if (Schema::hasColumn('submodulos','quarter_name')) {
                $table->dropUnique('uq_submodulo_quarter_titulo');
                $table->dropColumn('quarter_name');
            }
            if (Schema::hasColumn('submodulos','plantilla_path')) {
                $table->dropColumn('plantilla_path');
            }
        });
    }
};
