@extends('layouts.app')

@section('title', 'TI-UTM - Revisión de Gestión Académica')

@section('content')

<div class="row mb-2 g-2">
    <div class="col-md-8">
        <form method="GET" action="{{ route('revision.gestion.academica') }}">
            <div class="row g-2">
                <div class="col-md-6">
                    <select name="subseccion_id" class="form-select">
                        <option value="">-- Todas las subsecciones --</option>
                        @foreach ($subseccionesDisponibles as $sub)
                            <option value="{{ $sub->id }}" {{ request('subseccion_id') == $sub->id ? 'selected' : '' }}>
                                {{ $sub->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="col-md-6">
                          <div class="input-group">
                            <select name="quarter_name" class="form-select">
                              <option value="">-- Todos los cuatrimestres --</option>
                              @foreach($quartersDisponibles ?? [] as $q)
                                @php
                                  $sel = trim(request('quarter_name', $cuatrimestreActual ?? '')) === trim($q) ? 'selected' : '';
                                @endphp
                                <option value="{{ $q }}" {{ $sel }}>{{ $q }}</option>
                              @endforeach
                            </select>

                            <button class="btn btn-primary" type="submit">
                              <i class="fa-solid fa-filter"></i> Aplicar
                            </button>

                            @if(request()->has('subseccion_id') || request()->has('quarter_name'))
                              <a href="{{ route('revision.gestion.academica') }}" class="btn btn-outline-secondary">
                                Limpiar
                              </a>
                            @endif
                          </div>
                        </div>

                            <i class="fa-solid fa-filter"></i> Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="col-md-4 text-end">
        <a href="{{ url()->previous() }}" class="btn btn-sm" style="background-color:#FFFFFF;color:#000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
        <a href="{{ route('revision.gestion.academica.gestion') }}" class="btn btn-sm btn-info text-white">
            <i class="fas fa-folder-open"></i> Ver Documentación de Gestión Académica
        </a>
    </div>
</div>

@if(!empty($cuatrimestreActual))
  <div class="alert alert-light border mb-2 py-2">
    <i class="fa-regular fa-calendar"></i>
    Cuatrimestre: <strong>{{ $cuatrimestreActual }}</strong>
  </div>
@endif

<div class="card" style="border-radius:8px; overflow:hidden;">
    <div class="card-header" style="background-color:#1976d2;">
        <h3 class="card-title text-white mb-0">
            Revisión de Gestión Académica - Profesores de tu Área
        </h3>
    </div>
    <div class="card-body p-0">
        @if($profesores->isEmpty())
            <div class="p-4 text-center text-muted">
                No hay profesores para mostrar con los filtros actuales.
            </div>
        @elseif($submodulos->isEmpty())
            <div class="p-4 text-center text-muted">
                No hay submódulos configurados para el módulo 5.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead style="background-color:#1976d2; color:white;">
                        <tr>
                            <th>Profesor</th>
                            @foreach($submodulos as $submodulo)
                                <th>{{ $submodulo->titulo }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($profesores as $profesor)
                            <tr>
                                <td class="fw-bold text-start">{{ $profesor->nombres }}</td>

                                @foreach($submodulos as $submodulo)
                                    @php
                                        /** @var \App\Models\SubmoduloArchivo|null $archivo */
                                        $archivo = $archivoMap[$profesor->id][$submodulo->id] ?? null;
                                        $fechaLimite = $submodulo->fecha_limite ?? null;

                                        $color = 'bg-warning text-dark';
                                        if ($archivo) {
                                            $color = 'bg-success text-white';
                                        } elseif ($fechaLimite && now()->greaterThan($fechaLimite)) {
                                            $color = 'bg-danger text-white';
                                        }

                                        $miCalif = ($archivo && isset($misCalifsMap[$archivo->id])) ? (int)$misCalifsMap[$archivo->id] : null;

                                        $promedio = ($archivo && isset($promediosMap[$archivo->id])) ? (float)$promediosMap[$archivo->id]['avg'] : null;
                                        $nEvals   = ($archivo && isset($promediosMap[$archivo->id])) ? (int)$promediosMap[$archivo->id]['n'] : 0;

                                        $valorSelect = null;
                                        if (!is_null($miCalif)) {
                                            $valorSelect = $miCalif;
                                        } elseif (!is_null($promedio)) {
                                            $valorSelect = max(0, min(10, (int)round($promedio)));
                                        }

                                        $textoClaro = str_contains($color,'text-white');
                                    @endphp

                                    <td class="{{ $color }}">
                                        @if ($archivo && !empty($archivo->id))
                                            <div class="d-flex flex-column align-items-center gap-2">

                                                <div class="d-flex justify-content-center align-items-center gap-2">
                                                    @if(!empty($archivo->ruta))
                                                        <a href="{{ asset('storage/'.$archivo->ruta) }}"
                                                           target="_blank"
                                                           class="text-decoration-underline {{ $textoClaro ? 'text-white' : 'text-dark' }}">
                                                            Ver archivo
                                                        </a>
                                                    @endif

                                                    <form action="{{ route('revision.gestion.academica.eliminar', $archivo->id) }}"
                                                          method="POST" style="display:inline-block;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-danger delete-btn" title="Eliminar">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                @can('calificar documentos')
                                                    <form action="{{ route('calificaciones.submodulos.store') }}"
                                                          method="POST"
                                                          class="d-inline-flex align-items-center gap-2">
                                                        @csrf
                                                        <input type="hidden" name="submodulo_archivo_id" value="{{ $archivo->id }}">
                                                        <select name="calificacion" class="form-select form-select-sm w-auto" required>
                                                            <option value="">Calificar</option>
                                                            @for ($i = 0; $i <= 10; $i++)
                                                                <option value="{{ $i }}" {{ (!is_null($valorSelect) && $valorSelect === $i) ? 'selected' : '' }}>
                                                                    {{ $i }}
                                                                </option>
                                                            @endfor
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-success" title="Guardar calificación">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    @if(!is_null($valorSelect))
                                                        <span class="badge bg-primary">Calif: {{ $valorSelect }}</span>
                                                    @else
                                                        <span class="{{ $textoClaro ? 'text-white-50' : 'text-muted' }}">Sin calificar</span>
                                                    @endif
                                                @endcan

                                                @if(!is_null($promedio))
                                                    <small class="{{ $textoClaro ? 'text-white-50' : 'text-muted' }}">
                                                        Prom: {{ number_format($promedio, 2) }} ({{ $nEvals }})
                                                    </small>
                                                @endif

                                                @error('submodulo_archivo_id')
                                                    <div class="small text-light mt-1">{{ $message }}</div>
                                                @enderror
                                                @error('calificacion')
                                                    <div class="small text-light mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection

@section('css')
<style>
    .table td, .table th { text-align:center; vertical-align:middle; }
    .table thead th { position: sticky; top: 0; z-index: 1; }
    .bg-success a, .bg-danger a { color:#fff; text-decoration:underline; }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: @json(session('success')),
            showConfirmButton: false,
            timer: 2000
        });
    @endif

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: '¿Eliminar este documento?',
                text: "No podrás deshacer esta acción",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
@endsection
