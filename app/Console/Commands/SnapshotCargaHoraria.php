<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SnapshotCargaHoraria extends Command
{
    protected $signature = 'snapshot:cargahoraria {--cuatrimestre_id=} {--quarter_name=}';
    protected $description = 'Copia profesor–materia–grupo–programa–unidades desde la conexión cargahoraria a la tabla local.';

    public function handle(): int
    {
        $cuatrimestreId = $this->option('cuatrimestre_id');
        $quarterName    = $this->option('quarter_name');

        if (! $cuatrimestreId) {
            $hoy = Carbon::now();
            $cuatri = DB::table('cuatrimestres')
                ->whereDate('fecha_inicio', '<=', $hoy)
                ->whereDate('fecha_fin', '>=', $hoy)
                ->first();

            if ($cuatri) {
                $cuatrimestreId = $cuatri->id ?? null;
                $quarterName    = $quarterName ?: ($cuatri->nombre ?? null);
            }
        }

        $rows = DB::connection('cargahoraria')
            ->table('teacher_subjects as ts')
            ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
            ->join('programs as p', 's.program_id', '=', 'p.program_id')
            ->join('groups as g', 'ts.group_id', '=', 'g.group_id')
            ->select(
                'ts.teacher_id',
                's.subject_name as materia',
                'g.group_name as grupo',
                'p.program_name as programa',
                's.unidades',
                's.subject_id',
                'g.group_id',
                'p.program_id'
            )
            ->groupBy(
                'ts.teacher_id',
                's.subject_name',
                'g.group_name',
                'p.program_name',
                's.unidades',
                's.subject_id',
                'g.group_id',
                'p.program_id'
            )
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            DB::table('materias_docentes_snapshots')->updateOrInsert(
                [
                    'teacher_id'      => $r->teacher_id,
                    'materia'         => $r->materia,
                    'grupo'           => $r->grupo,
                    'programa'        => $r->programa,
                    'cuatrimestre_id' => $cuatrimestreId,
                ],
                [
                    'unidades'     => (int) $r->unidades,
                    'subject_id'   => $r->subject_id,
                    'group_id'     => $r->group_id,
                    'program_id'   => $r->program_id,
                    'quarter_name' => $quarterName,
                    'source'       => 'cargahoraria',
                    'captured_at'  => now(),
                    'updated_at'   => now(),
                    'created_at'   => now(),
                ]
            );
            $count++;
        }

        $this->info("Snapshot guardado: {$count} filas.");
        return self::SUCCESS;
    }
}
