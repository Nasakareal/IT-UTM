@extends('layouts.app')

@section('title', 'TI-UTM - Editar Submódulo')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 offset-md-1">
        <div class="card card-outline card-success mb-4">
            <div class="card-header">
                <h3 class="card-title">Editar Submódulo</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('submodulos.update', $submodulo->id) }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- 1ª fila: Título y Subsección -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="titulo" class="form-label fw-bold">
                                Título del Submódulo
                            </label>
                            <input
                                type="text"
                                name="titulo"
                                id="titulo"
                                class="form-control @error('titulo') is-invalid @enderror"
                                value="{{ old('titulo', $submodulo->titulo) }}"
                                placeholder="Ingrese el título"
                                required
                            >
                            @error('titulo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="subsection_id" class="form-label fw-bold">
                                Subsección
                            </label>
                            <select
                                name="subsection_id"
                                id="subsection_id"
                                class="form-select @error('subsection_id') is-invalid @enderror"
                                required
                            >
                                <option value="" disabled {{ old('subsection_id', $submodulo->subsection_id) ? '' : 'selected' }}>
                                    Seleccione...
                                </option>
                                @foreach($subsections as $sub)
                                    <option
                                        value="{{ $sub->id }}"
                                        {{ old('subsection_id', $submodulo->subsection_id) == $sub->id ? 'selected' : '' }}
                                    >
                                        {{ $sub->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subsection_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- 2ª fila: Archivo Base y Descripción -->
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <label for="documento_solicitado" class="form-label fw-bold">
                                Archivo Base (plantilla)
                            </label>
                            <input
                                type="file"
                                name="documento_solicitado"
                                id="documento_solicitado"
                                class="form-control @error('documento_solicitado') is-invalid @enderror"
                                accept=".pdf,.doc,.docx, .xls, .xlsx, .xml"
                            >
                            @if($submodulo->documento_solicitado)
                                <small class="text-muted">
                                    Archivo actual: 
                                    <a href="{{ asset('storage/' . $submodulo->documento_solicitado) }}" target="_blank">
                                        Ver documento
                                    </a>
                                </small>
                            @endif
                            @error('documento_solicitado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="descripcion" class="form-label fw-bold">
                                Descripción
                            </label>
                            <textarea
                                name="descripcion"
                                id="descripcion"
                                class="form-control @error('descripcion') is-invalid @enderror"
                                placeholder="Ingrese la descripción"
                            >{{ old('descripcion', $submodulo->descripcion) }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- 3ª fila: Fechas -->
                    <div class="row g-3 mt-3">
                        <div class="col-md-4">
                            <label for="fecha_apertura" class="form-label fw-bold">
                                Fecha Apertura
                            </label>
                            <input
                                type="datetime-local"
                                name="fecha_apertura"
                                id="fecha_apertura"
                                class="form-control @error('fecha_apertura') is-invalid @enderror"
                                value="{{ old('fecha_apertura', \Carbon\Carbon::parse($submodulo->fecha_apertura)->format('Y-m-d\TH:i')) }}">
                            @error('fecha_apertura')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_limite" class="form-label fw-bold">
                                Fecha Límite
                            </label>
                            <input
                                type="datetime-local"
                                name="fecha_limite"
                                id="fecha_limite"
                                class="form-control @error('fecha_limite') is-invalid @enderror"
                                value="{{ old('fecha_limite', \Carbon\Carbon::parse($submodulo->fecha_limite)->format('Y-m-d\TH:i')) }}">
                            @error('fecha_limite')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_cierre" class="form-label fw-bold">
                                Fecha Cierre
                            </label>
                            <input
                                type="datetime-local"
                                name="fecha_cierre"
                                id="fecha_cierre"
                                class="form-control @error('fecha_cierre') is-invalid @enderror"
                                value="{{ old('fecha_cierre', \Carbon\Carbon::parse($submodulo->fecha_cierre)->format('Y-m-d\TH:i')) }}">
                            @error('fecha_cierre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Fila: Categorías permitidas (checkbox) -->
                    <div class="row g-3 mt-4">
                        <div class="col-md-12">
                            <label class="form-label fw-bold mb-2">Categorías permitidas para este submódulo:</label>

                            @php
                                $categoriasDisponibles = [
                                    'Titular C', 'Titular B', 'Titular A',
                                    'Asociado C', 'Técnico Académico C',
                                    'Técnico Académico B', 'Técnico Académico A',
                                    'Profesor de Asignatura B'
                                ];

                                // Categorías ya asignadas al submódulo
                                $asignadas = $submodulo->categoriasPermitidas->pluck('categoria')->toArray();
                            @endphp

                            <div class="row">
                                @foreach($categoriasDisponibles as $cat)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="categorias[]"
                                                value="{{ $cat }}"
                                                id="categoria_{{ Str::slug($cat, '_') }}"
                                                {{ in_array($cat, old('categorias', $asignadas)) ? 'checked' : '' }}
                                            >
                                            <label class="form-check-label" for="categoria_{{ Str::slug($cat, '_') }}">
                                                {{ $cat }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @error('categorias')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>


                    <hr class="mt-4">

                    <!-- Botones -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="fa-solid fa-check"></i> Actualizar
                        </button>
                        <a href="{{ route('submodulos.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-ban"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .form-group label {
        font-weight: bold;
    }
    .card {
        max-width: 100%;
    }
</style>
@stop

@section('scripts')
<script>
    $(document).ready(function(){
        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
                html: `
                    <ul style="text-align: left;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        @endif
    });
</script>
@stop
