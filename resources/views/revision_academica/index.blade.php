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
        <a href="{{ url()->previous() }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
        <a href="{{ route('revision.gestion.academica.gestion') }}" class="btn btn-sm btn-info text-white">
            <i class="fas fa-folder-open"></i> Ver Documentación de Gestión Académica
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header" style="background-color: #1976d2;">
        <h3 class="card-title text-white mb-0">
            Revisión de Gestión Académica - Profesores de tu Área
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead style="background-color: #1976d2; color: white;">
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
                                    $archivo = $archivoMap[$profesor->id][$submodulo->id] ?? null;
                                    $fechaLimite = $submodulo->fecha_limite;
                                    $color = 'bg-warning text-dark'; // default: pendiente

                                    if ($archivo) {
                                        $color = 'bg-success text-white'; // entregado
                                    } elseif ($fechaLimite && now()->greaterThan($fechaLimite)) {
                                        $color = 'bg-danger text-white'; // fuera de tiempo
                                    }
                                @endphp
                                <td class="{{ $color }}">
                                    @if ($archivo)
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <a href="{{ asset('storage/'.$archivo->ruta) }}" 
                                               target="_blank" 
                                               class="text-white text-decoration-underline">
                                                Ver archivo
                                            </a>

                                            <form action="{{ route('revision.gestion.academica.eliminar', $archivo->id) }}" 
                                                  method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-danger delete-btn" title="Eliminar">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>

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
    .table td, .table th {
        text-align: center;
        vertical-align: middle;
    }

    .bg-success a,
    .bg-danger a {
        color: #fff;
        text-decoration: underline;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function(){
    // SweetAlert éxito
    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 2000
        });
    @endif

    // Confirmación al eliminar
    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let form = $(this).closest('form');
        Swal.fire({
            title: '¿Estás seguro de eliminar este documento?',
            text: "¡No podrás deshacer esta acción!",
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
</script>
@endsection
