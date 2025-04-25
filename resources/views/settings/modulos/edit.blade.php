@extends('layouts.app')

@section('title', 'TI-UTM - Editar Módulo')

@section('content_header')
    <h1>Edición del Módulo</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10 offset-md-1"> 
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title">Modifique los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('modulos.update', $modulo->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Primera fila: Título, Año, Categoría -->
                        <div class="row g-3">
                            <!-- Título -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="titulo" class="fw-bold">Título del Módulo</label>
                                    <input type="text" name="titulo" id="titulo" class="form-control @error('titulo') is-invalid @enderror" value="{{ old('titulo', $modulo->titulo) }}" placeholder="Ingrese el título" required>
                                    @error('titulo')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Año -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="anio" class="fw-bold">Año</label>
                                    <input type="text" name="anio" id="anio" class="form-control @error('anio') is-invalid @enderror" value="{{ old('anio', $modulo->anio) }}" placeholder="Ingrese el año">
                                    @error('anio')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Categoría -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="categoria" class="fw-bold">Categoría</label>
                                    <input type="text" name="categoria" id="categoria" class="form-control @error('categoria') is-invalid @enderror" value="{{ old('categoria', $modulo->categoria) }}" placeholder="Ingrese la categoría" required>
                                    @error('categoria')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Segunda fila: Sección, Ícono -->
                        <div class="row g-3">
                            <!-- Sección -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="seccion_id" class="fw-bold">Sección</label>
                                    <select name="seccion_id" id="seccion_id" class="form-control @error('seccion_id') is-invalid @enderror">
                                        <option value="" disabled>Seleccione una sección</option>
                                        @foreach($secciones as $seccion)
                                            <option value="{{ $seccion->id }}" 
                                                {{ old('seccion_id', $modulo->seccion_id) == $seccion->id ? 'selected' : '' }}>
                                                {{ $seccion->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('seccion_id')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Ícono -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="icono" class="fw-bold">Ícono del Módulo</label>
                                    <select name="icono" id="icono" class="form-control @error('icono') is-invalid @enderror">
                                        <option value="" disabled>Seleccione un ícono</option>
                                        <option value="fa-lightbulb" {{ old('icono', $modulo->icono) == 'fa-lightbulb' ? 'selected' : '' }}>💡 Académico</option>
                                        <option value="fa-briefcase" {{ old('icono', $modulo->icono) == 'fa-briefcase' ? 'selected' : '' }}>💼 Administrativo</option>
                                        <option value="fa-chart-pie" {{ old('icono', $modulo->icono) == 'fa-chart-pie' ? 'selected' : '' }}>📊 Presupuestario</option>
                                        <option value="fa-users" {{ old('icono', $modulo->icono) == 'fa-users' ? 'selected' : '' }}>👥 Estudiantil</option>
                                        <option value="fa-flask" {{ old('icono', $modulo->icono) == 'fa-flask' ? 'selected' : '' }}>🧪 Laboratorio</option>
                                        <option value="fa-graduation-cap" {{ old('icono', $modulo->icono) == 'fa-graduation-cap' ? 'selected' : '' }}>🎓 Docencia</option>
                                        <option value="fa-building-columns" {{ old('icono', $modulo->icono) == 'fa-building-columns' ? 'selected' : '' }}>🏛️ Institucional</option>
                                    </select>
                                    @error('icono')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Tercera fila: Descripción -->
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="descripcion" class="fw-bold">Descripción</label>
                                    <textarea name="descripcion" id="descripcion" class="form-control @error('descripcion') is-invalid @enderror" placeholder="Ingrese la descripción">{{ old('descripcion', $modulo->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Botones -->
                        <div class="row g-3">
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('modulos.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop


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
