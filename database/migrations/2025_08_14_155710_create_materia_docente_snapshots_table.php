<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materias_docentes_snapshots', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->string('materia', 255);
            $table->string('grupo', 255);
            $table->string('programa', 255);

            $table->unsignedInteger('unidades');

            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();

            $table->unsignedBigInteger('cuatrimestre_id')->nullable();
            $table->string('quarter_name', 150)->nullable();

            $table->timestamp('captured_at')->useCurrent();
            $table->string('source', 50)->default('cargahoraria');

            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                CREATE UNIQUE INDEX uniq_docente_materia_grupo_prog_cuatri
                ON materias_docentes_snapshots (
                    teacher_id,
                    materia(80),
                    grupo(40),
                    programa(80),
                    cuatrimestre_id
                )
            ");
        } else {
            Schema::table('materias_docentes_snapshots', function (Blueprint $table) {
                $table->unique(
                    ['teacher_id','materia','grupo','programa','cuatrimestre_id'],
                    'uniq_docente_materia_grupo_prog_cuatri'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('materias_docentes_snapshots') && DB::getDriverName() === 'mysql') {
            try {
                DB::statement("DROP INDEX uniq_docente_materia_grupo_prog_cuatri ON materias_docentes_snapshots");
            } catch (\Throwable $e) {
            }
        }

        Schema::dropIfExists('materias_docentes_snapshots');
    }
};
