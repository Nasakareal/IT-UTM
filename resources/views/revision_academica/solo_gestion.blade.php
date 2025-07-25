@extends('layouts.app')

@section('title', 'TI-UTM - Documentación de Gestión Académica')

@section('content')
<form method="GET" action="{{ route('revision.gestion.academica.gestion') }}">
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="input-group-text"><i class="fa-solid fa-user"></i> Profesor:</label>
            <select name="profesor_id" class="form-select">
                <option value="">-- Todos los profesores --</option>
                @foreach ($profesores as $p)
                    <option value="{{ $p->id }}" {{ request('profesor_id') == $p->id ? 'selected' : '' }}>
                        {{ $p->nombres }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="input-group-text"><i class="fa-solid fa-people-group"></i> Grupo:</label>
            <select name="grupo" class="form-select">
                <option value="">-- Todos los grupos --</option>
                @foreach ($gruposDisponibles as $g)
                    <option value="{{ $g }}" {{ request('grupo') == $g ? 'selected' : '' }}>
                        {{ $g }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="input-group-text"><i class="fa-solid fa-book"></i> Materia:</label>
            <select name="materia" class="form-select">
                <option value="">-- Todas las materias --</option>
                @foreach ($materiasDisponibles as $m)
                    <option value="{{ $m }}" {{ request('materia') == $m ? 'selected' : '' }}>
                        {{ $m }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="input-group-text"><i class="fa-solid fa-layer-group"></i> Unidad:</label>
            <select name="unidad" class="form-select">
                <option value="">-- Todas las unidades --</option>
                @foreach ($unidadesDisponibles as $u)
                    <option value="{{ $u }}" {{ request('unidad') == $u ? 'selected' : '' }}>
                        Unidad {{ $u }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12 text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Aplicar filtros
            </button>
            <a href="{{ route('revision.gestion.academica') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Regresar a Submódulos
            </a>
        </div>
    </div>
</form>

@if($profesorSeleccionado)
    <div class="card">
        <div class="card-header bg-primary text-white">
            Documentación de Gestión Académica – {{ $profesorSeleccionado->nombres }}
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>Materia</th>
                            <th>Grupo</th>
                            <th>Unidad</th>
                            <th>Documento</th>
                            <th>Fecha de subida</th> {{-- NUEVA COLUMNA --}}
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documentos as $doc)
                            @php
                                $color = 'bg-warning text-dark';
                                $texto = '-';
                                if ($doc['entregado']) {
                                    $color = 'bg-success text-white';
                                    $texto = '<a href="'.asset('storage/'.$doc['archivo_subido']).'" target="_blank">Ver archivo</a>';
                                } elseif (!$doc['entregado'] && !$doc['es_actual']) {
                                    $color = 'bg-danger text-white';
                                    $texto = '-';
                                }
                            @endphp
                            <tr>
                                <td>{{ $doc['materia'] }}</td>
                                <td>{{ $doc['grupo'] }}</td>
                                <td>{{ $doc['unidad'] }}</td>
                                <td>{{ $doc['tipo_documento'] }}</td>
                                <td>
                                    @if($doc['entregado'] && isset($doc['created_at']))
                                        {{ \Carbon\Carbon::parse($doc['created_at'])->format('d/m/Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="{{ $color }}">{!! $texto !!}</td>
                                <td>
                                    @if($doc['entregado'])
                                        {{-- Botón Eliminar --}}
                                        <form action="{{ route('revision.gestion.academica.eliminarUno') }}" method="POST" onsubmit="return confirm('¿Eliminar este documento?');" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="user_id" value="{{ $profesorSeleccionado->id }}">
                                            <input type="hidden" name="materia" value="{{ $doc['materia'] }}">
                                            <input type="hidden" name="grupo" value="{{ $doc['grupo'] }}">
                                            <input type="hidden" name="unidad" value="{{ $doc['unidad'] }}">
                                            <input type="hidden" name="tipo_documento" value="{{ $doc['tipo_documento'] }}">
                                            <button class="btn btn-danger btn-sm" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>

                                        {{-- Formulario Calificación --}}
                                        <form action="{{ route('revision.gestion.academica.calificar') }}" method="POST" style="display:inline-block; margin-top: 5px;">
                                            @csrf
                                            <input type="hidden" name="documento_id" value="{{ $doc['id'] ?? null }}">

                                            <select name="calificacion" class="form-select form-select-sm d-inline-block w-auto" required>
                                                <option value="">Calificar</option>
                                                @for ($i = 1; $i <= 10; $i++)
                                                    <option value="{{ $i }}" {{ isset($doc['mi_calificacion']) && $doc['mi_calificacion'] == $i ? 'selected' : '' }}>
                                                        {{ $i }}
                                                    </option>
                                                @endfor
                                            </select>

                                            <button type="submit" class="btn btn-sm btn-success" title="Guardar calificación">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    @endif

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No hay documentación disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>
@endif
@endsection

@section('css')
<style>
    .table td, .table th {
        text-align: center;
        vertical-align: middle;
    }
    .bg-success a, .bg-danger a {
        color: #fff;
        text-decoration: underline;
    }
</style>
@endsection
