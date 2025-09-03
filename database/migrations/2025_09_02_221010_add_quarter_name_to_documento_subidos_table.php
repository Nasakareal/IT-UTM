<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->string('quarter_name', 60)->nullable()->after('unidad');

            $table->index('quarter_name', 'documento_subidos_quarter_idx');
        });
    }

    public function down(): void
    {
        Schema::table('documentos_subidos', function (Blueprint $table) {
            $table->dropIndex('documento_subidos_quarter_idx');
            $table->dropColumn('quarter_name');
        });
    }
};
