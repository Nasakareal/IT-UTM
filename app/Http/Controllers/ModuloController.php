<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Models\Subsection;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ModuloController extends Controller
{
    private function quarterNameFromDate(\DateTimeInterface $date): string
    {
        $y = (int)$date->format('Y');
        $m = (int)$date->format('n');
        if ($m >= 1 && $m <= 4)   return "ENERO-ABRIL {$y}";
        if ($m >= 5 && $m <= 8)   return "MAYO-AGOSTO {$y}";
        return "SEPTIEMBRE-DICIEMBRE {$y}";
    }

    public function index()
    {
        $modulos = Modulo::all();
        return view('settings.modulos.index', compact('modulos'));
    }

    public function create()
    {
        $secciones = \App\Models\Seccion::all();
        return view('settings.modulos.create', compact('secciones'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'anio'        => 'nullable|string|max:10',
            'categoria'   => 'required|string|max:255',
            'color'       => 'nullable|string|max:7',
            'descripcion' => 'nullable|string',
            'link'        => 'nullable|url',
            'seccion_id'  => 'required|exists:seccions,id',
            'icono'       => 'nullable|string|max:125',
        ]);

        Modulo::create($data);

        return redirect()->route('modulos.index')->with('success', 'Módulo creado correctamente.');
    }

    // Muestra un módulo en detalle
public function show(\App\Models\Modulo $modulo, \Illuminate\Http\Request $request)
{
    // 1) quarter solicitado o actual
    $qp  = trim((string)$request->get('quarter_name', ''));
    $now = \Carbon\Carbon::now();
    $y   = (int)$now->format('Y');
    $m   = (int)$now->format('n');
    $quarterActual = ($m >= 1 && $m <= 4)
        ? "ENERO-ABRIL {$y}"
        : (($m >= 5 && $m <= 8) ? "MAYO-AGOSTO {$y}" : "SEPTIEMBRE-DICIEMBRE {$y}");
    $quarterSolicitado = $qp !== '' ? $qp : $quarterActual;

    // 2) Resolver quarter efectivo con fallback (solo módulo 5)
    $quarterEfectivo = null;
    if ((int)$modulo->id === 5 && \Schema::hasColumn('submodulos','quarter_name')) {
        $subsecIds = \App\Models\Subsection::where('modulo_id', $modulo->id)->pluck('id');

        $existeSolicitado = \App\Models\Submodulo::whereIn('subsection_id', $subsecIds)
            ->where('quarter_name', $quarterSolicitado)
            ->exists();

        if ($existeSolicitado) {
            $quarterEfectivo = $quarterSolicitado;
        } else {
            $masReciente = \App\Models\Submodulo::whereIn('subsection_id', $subsecIds)
                ->whereNotNull('quarter_name')
                ->orderBy('quarter_name', 'desc')
                ->value('quarter_name');
            $quarterEfectivo = $masReciente ?: null; // si no hay ninguno con quarter_name, veremos legacy (NULL)
        }
    }

    $categoriaUser = auth()->user()->categoria ?? null;

    // 3) Carga subsecciones + submódulos
    $subsections = \App\Models\Subsection::where('modulo_id', $modulo->id)
        ->whereNull('parent_id')
        ->orderBy('orden')
        ->with([
            'carpetas' => function ($query) {
                $query->whereNull('parent_id')
                      ->orderBy('orden')
                      ->with([
                          'archivos',
                          'children' => function ($q) {
                              $q->orderBy('orden');
                          }
                      ]);
            },
            'submodulos' => function ($query) use ($modulo, $quarterEfectivo, $categoriaUser) {
                $query->orderBy('orden')

                      // Mostrar submódulos si:
                      //  - NO tienen categoriasPermitidas (→ para todos)
                      //  - O SÍ tienen y coincide la categoría del usuario
                      ->where(function ($w) use ($categoriaUser) {
                          $w->doesntHave('categoriasPermitidas')
                            ->orWhereHas('categoriasPermitidas', function ($q) use ($categoriaUser) {
                                $q->where('categoria', $categoriaUser);
                            });
                      })

                      // Filtro por quarter efectivo SOLO si lo resolvimos (módulo 5)
                      ->when((int)$modulo->id === 5 && \Schema::hasColumn('submodulos','quarter_name'), function ($qq) use ($quarterEfectivo) {
                          if (!is_null($quarterEfectivo)) {
                              $qq->where('quarter_name', $quarterEfectivo);
                          }
                      })

                      ->with([
                          'archivos' => function ($q) {
                              $q->where('user_id', auth()->id());
                          },
                          'submoduloUsuarios' => function ($q) {
                              $q->where('user_id', auth()->id());
                          },
                      ]);
            },
        ])
        ->get();

    // 4) Normaliza fechas
    $subsections->each(function ($subsec) {
        $subsec->submodulos->transform(function ($sm) {
            $sm->fecha_apertura = $sm->fecha_apertura ? \Carbon\Carbon::parse($sm->fecha_apertura) : null;
            $sm->fecha_limite   = $sm->fecha_limite   ? \Carbon\Carbon::parse($sm->fecha_limite)   : null;
            $sm->fecha_cierre   = $sm->fecha_cierre   ? \Carbon\Carbon::parse($sm->fecha_cierre)   : null;
            return $sm;
        });
    });

    return view('modulos.show', [
        'modulo'                => $modulo,
        'subnivelesPrincipales' => $subsections,
        'quarter_name'          => $quarterEfectivo ?? $quarterSolicitado,
    ]);
}



    // Muestra el formulario para editar un módulo
    public function edit(Modulo $modulo)
    {
        $secciones = \App\Models\Seccion::all();
        return view('settings.modulos.edit', compact('modulo', 'secciones'));
    }

    // Actualiza el módulo en la base de datos
    public function update(Request $request, Modulo $modulo)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'anio'        => 'nullable|string|max:10',
            'categoria'   => 'required|string|max:255',
            'color'       => 'nullable|string|max:7',
            'descripcion' => 'nullable|string',
            'link'        => 'nullable|url',
            'seccion_id'  => 'required|exists:seccions,id',
            'icono'       => 'nullable|string|max:125',
        ]);

        $modulo->update($data);

        return redirect()->route('modulos.index')->with('success', 'Módulo actualizado correctamente.');
    }


    // Elimina el módulo de la base de datos
    public function destroy(Modulo $modulo)
    {
        $modulo->delete();

        return redirect()->route('modulos.index')->with('success', 'Módulo eliminado correctamente.');
    }
}
