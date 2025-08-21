@extends('layouts.app')

@section('title', 'TI-UTM - Revisión de Gestión Académica')

@section('content')
<div class="row mb-2">
    <div class="col-md-6">
        <form method="GET" action="{{ route('revision.gestion.academica') }}">
            <div class="input-group">
                <select name="subseccion_id" class="form-select">
                    <option value="">-- Todas las subsecciones --</option>
                    @foreach ($subseccionesDisponibles as $sub)
                        <option value="{{ $sub->id }}" {{ request('subseccion_id') == $sub->id ? 'selected' : '' }}>
                            {{ $sub->nombre }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="col-md-6 text-end">
        <a href="{{ url()->previous() }}" class="btn btn-sm" style="background-color:#FFFFFF;color:#000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
        <a href="{{ route('revision.gestion.academica.gestion') }}" class="btn btn-sm btn-info text-white">
            <i class="fas fa-folder-open"></i> Ver Documentación de Gestión Académica
        </a>
    </div>
</div>

<div class="card" style="border-radius:8px; overflow:hidden;">
    <div class="card-header" style="background-color:#1976d2;">
        <h3 class="card-title text-white mb-0">
            Revisión de Gestión Académica - Profesores de tu Área
        </h3>
    </div>
    <div class="card-body p-0">
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
                            <td class="fw-bold">{{ $profesor->nombres }}</td>

                            @foreach($submodulos as $submodulo)
                                @php
                                    /** @var \App\Models\SubmoduloArchivo|null $archivo */
                                    $archivo = $archivoMap[$profesor->id][$submodulo->id] ?? null;

                                    $fechaLimite = $submodulo->fecha_limite ?? null;
                                    $color = 'bg-warning text-dark'; // pendiente por default
                                    if ($archivo) {
                                        $color = 'bg-success text-white'; // entregado
                                    } elseif ($fechaLimite && now()->greaterThan($fechaLimite)) {
                                        $color = 'bg-danger text-white'; // fuera de tiempo
                                    }

                                    // Preselección de MI calificación
                                    $miCalif = ($archivo && isset($misCalifsMap[$archivo->id]))
                                                ? (int)$misCalifsMap[$archivo->id]
                                                : null;

                                    // Promedio global (todas las calificaciones de ese archivo)
                                    $promedio = ($archivo && isset($promediosMap[$archivo->id]))
                                                ? $promediosMap[$archivo->id]['avg']
                                                : null;
                                    $nEvals   = ($archivo && isset($promediosMap[$archivo->id]))
                                                ? $promediosMap[$archivo->id]['n']
                                                : 0;
                                @endphp

                                <td class="{{ $color }}">
                                    @if ($archivo && !empty($archivo->id))
                                        <div class="d-flex flex-column align-items-center gap-2">

                                            {{-- Ver archivo / Eliminar --}}
                                            <div class="d-flex justify-content-center align-items-center gap-2">
                                                @if(!empty($archivo->ruta))
                                                    <a href="{{ asset('storage/'.$archivo->ruta) }}"
                                                       target="_blank"
                                                       class="text-white text-decoration-underline">
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

                                            {{-- Calificar (solo con permiso) --}}
                                            @can('calificar documentos')
                                                <form action="{{ route('calificaciones.submodulos.store') }}"
                                                      method="POST"
                                                      class="d-inline-flex align-items-center gap-2">
                                                    @csrf

                                                    {{-- CAMPO CLAVE: SIEMPRE CON VALOR --}}
                                                    <input type="hidden" name="submodulo_archivo_id" value="{{ $archivo->id }}">

                                                    <select name="calificacion" class="form-select form-select-sm w-auto" required>
                                                        <option value="">Calificar</option>
                                                        @for ($i = 0; $i <= 10; $i++)
                                                            <option value="{{ $i }}" {{ (!is_null($miCalif) && $miCalif === $i) ? 'selected' : '' }}>
                                                                {{ $i }}
                                                            </option>
                                                        @endfor
                                                    </select>

                                                    <button type="submit" class="btn btn-sm btn-success" title="Guardar calificación">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Solo lectura para quien no tiene permiso --}}
                                                @if(!is_null($miCalif))
                                                    <span class="badge bg-primary">Calif: {{ $miCalif }}</span>
                                                @else
                                                    <span class="text-white-50">Sin calificar</span>
                                                @endif
                                            @endcan>

                                            {{-- Promedio global (si existe) --}}
                                            @if(!is_null($promedio))
                                                <small class="text-white-50">
                                                    Prom: {{ number_format($promedio, 2) }} ({{ $nEvals }})
                                                </small>
                                            @endif

                                            {{-- Errores scoped (si fallara validación) --}}
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
    </div>
</div>
@endsection

@section('css')
<style>
    .table td, .table th { text-align:center; vertical-align:middle; }
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
