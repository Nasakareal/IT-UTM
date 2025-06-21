<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submodulo;
use App\Models\Comunicado;
use App\Models\Seccion;
use App\Models\DocumentoSubido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $documentosPendientes = collect();

        // 🔸 1. Lógica original: Submódulos pendientes
        if (!$user->hasAnyRole(['Administrador', 'Subdirector'])) {
            $documentosPendientes = Submodulo::whereNotNull('fecha_limite')
                ->whereHas('categoriasPermitidas', function ($q) use ($user) {
                    $q->where('categoria', $user->categoria);
                })
                ->where(function ($query) use ($user) {
                    $query->whereDoesntHave('submoduloUsuarios', function ($q) use ($user) {
                            $q->where('user_id', $user->id)
                              ->where('estatus', 'entregado');
                        })
                        ->orWhereHas('submoduloUsuarios', function ($q) use ($user) {
                            $q->where('user_id', $user->id)
                              ->where('estatus', 'pendiente');
                        });
                })
                ->where(function ($query) {
                    $query->whereNull('fecha_apertura')
                          ->orWhere('fecha_apertura', '<=', now());
                })
                ->get();
        }

        // 🔸 2. Nuevos documentos académicos por unidad
        if ($user->teacher_id) {
            // Buscar materias asignadas al usuario
            $materias = DB::connection('cargahoraria')->table('teacher_subjects as ts')
                ->join('subjects as s', 'ts.subject_id', '=', 's.subject_id')
                ->select('s.subject_name as materia', 's.unidades')
                ->where('ts.teacher_id', $user->teacher_id)
                ->groupBy('s.subject_name', 's.unidades')
                ->get();

            if ($materias->isNotEmpty()) {
                // Detectar cuatrimestre activo por fecha
                $hoy = Carbon::now();
                $cuatrimestre = DB::table('cuatrimestres')
                    ->whereDate('fecha_inicio', '<=', $hoy)
                    ->whereDate('fecha_fin', '>=', $hoy)
                    ->first();

                if ($cuatrimestre) {
                    $inicioCuatrimestre = Carbon::parse($cuatrimestre->fecha_inicio);
                    $finCuatrimestre = Carbon::parse($cuatrimestre->fecha_fin);
                    $duracionTotalDias = $inicioCuatrimestre->diffInDays($finCuatrimestre) + 1;
                    $diasTranscurridos = $inicioCuatrimestre->diffInDays($hoy) + 1;

                    // Tipos de documentos por unidad
                    $tipos = [
                        'Planeación didáctica'       => 'F-DA-GA-02',
                        'Seguimiento de la Planeación' => 'F-DA-GA-03',
                        'Informe de Estudiantes'     => 'F-DA-GA-05',
                        'Control de Asesorías'       => 'F-DA-GA-06',
                    ];

                    foreach ($materias as $materia) {
                        $totalUnidades = $materia->unidades;
                        $diasPorUnidad = (int) ceil($duracionTotalDias / $totalUnidades);
                        $unidadActual = (int) ceil($diasTranscurridos / $diasPorUnidad);

                        if ($unidadActual > $totalUnidades) {
                            $unidadActual = $totalUnidades;
                        }

                        foreach ($tipos as $tipo => $codigo) {
                            $yaEntregado = DocumentoSubido::where('user_id', $user->id)
                                ->where('materia', $materia->materia)
                                ->where('unidad', $unidadActual)
                                ->where('tipo_documento', $tipo)
                                ->exists();

                            if (!$yaEntregado) {
                                $documentosPendientes->push((object) [
                                    'titulo' => "{$tipo} - {$materia->materia} (Unidad {$unidadActual})",
                                    'fecha_limite' => $cuatrimestre->fecha_fin,
                                ]);
                            }
                        }

                        // Documento extra solo para unidad 1
                        if ($unidadActual == 1) {
                            $yaEntregado = DocumentoSubido::where('user_id', $user->id)
                                ->where('materia', $materia->materia)
                                ->where('unidad', 1)
                                ->where('tipo_documento', 'Presentación de la Asignatura')
                                ->exists();

                            if (!$yaEntregado) {
                                $documentosPendientes->push((object) [
                                    'titulo' => "Presentación de la Asignatura - {$materia->materia} (Unidad 1)",
                                    'fecha_limite' => $cuatrimestre->fecha_fin,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // 🔹 Otros datos de la vista
        $comunicados = Comunicado::latest()->get();
        $secciones = Seccion::with('modulos')->orderBy('orden')->get();

       


        return view('home', compact('documentosPendientes', 'comunicados', 'secciones'));
    }
}
