<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubmodulosInstanciar extends Command
{
    // Uso:
    // php artisan submodulos:instanciar "SEPTIEMBRE-DICIEMBRE 2025" --inicio=2025-09-01
    protected $signature = 'submodulos:instanciar {quarter_name} {--inicio=}';
    protected $description = 'Crea/actualiza los submódulos fijos para un quarter_name y fecha de inicio dadas, y asegura categorías.';

    private array $CATS_DEFAULT = [
        'Titular C','Titular B','Titular A','Asociado C',
        'Técnico Académico C','Técnico Académico B','Técnico Académico A',
        'Profesor de Asignatura B',
    ];

    public function handle()
    {
        $quarter = trim((string)$this->argument('quarter_name'));
        $inicio  = $this->option('inicio');

        if ($quarter === '') {
            $this->error('Falta quarter_name. Ej: "MAYO-AGOSTO 2025"');
            return self::INVALID;
        }
        if (!$inicio) {
            $this->error('Falta --inicio=YYYY-MM-DD (fecha de inicio del cuatrimestre)');
            return self::INVALID;
        }

        $ini   = Carbon::parse($inicio)->startOfDay();
        $fixed = config('submodulos_fixed', []);

        DB::beginTransaction();
        try {
            foreach ($fixed as $row) {
                $apertura = $ini->copy()->addDays($row['apertura_d'])->startOfDay();
                $cierre   = $ini->copy()->addDays($row['cierre_d'])->endOfDay();
                $limite   = $ini->copy()->addDays($row['limite_d'])->endOfDay();

                // Estado calculado: si hoy > cierre -> Incumplimiento, si no -> Abierto
                $estatus = now()->gt($cierre) ? 'Incumplimiento' : 'Abierto';

                // 1) UPSERT del submódulo (único por quarter_name + titulo)
                DB::table('submodulos')->updateOrInsert(
                    ['quarter_name' => $quarter, 'titulo' => $row['titulo']],
                    [
                        'subsection_id'        => $row['subsection_id'],
                        'descripcion'          => null,
                        'documento_solicitado' => $row['plantilla_path'],
                        'plantilla_path'       => $row['plantilla_path'],
                        'fecha_apertura'       => $apertura,
                        'fecha_cierre'         => $cierre,
                        'fecha_limite'         => $limite,
                        'estatus'              => $estatus,
                        'orden'                => $row['orden'],
                        'updated_at'           => now(),
                        'created_at'           => now(),
                    ]
                );

                // 2) ID del submódulo recién upserteado
                $subId = (int) DB::table('submodulos')
                    ->where('quarter_name', $quarter)
                    ->where('titulo', $row['titulo'])
                    ->value('id');

                // 3) Asegurar categorías:
                //    - Si no tiene ninguna, inserta las CATS_DEFAULT
                $tieneCats = DB::table('categoria_submodulos')
                    ->where('submodulo_id', $subId)
                    ->limit(1)
                    ->exists();

                if (!$tieneCats) {
                    $bulk = [];
                    $now  = now();
                    foreach ($this->CATS_DEFAULT as $cat) {
                        $bulk[] = [
                            'submodulo_id' => $subId,
                            'categoria'    => $cat,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                    DB::table('categoria_submodulos')->insert($bulk);
                }
            }

            DB::commit();
            $this->info("Listo: submódulos y categorías instanciados para {$quarter}");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error: ".$e->getMessage());
            return self::FAILURE;
        }
    }
}
