@extends('layouts.app')

@section('title', 'TI-UTM - Crear Módulo')

@section('content_header')
    <h1>Creación de un Nuevo Módulo</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <!-- col-md-10 offset-md-1 = más ancho del card -->
        <div class="col-md-10 offset-md-1"> 
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('modulos.store') }}" method="POST">
                        @csrf
                        <!-- Primera fila: Título, Año, Categoría -->
                        <div class="row g-3">
                            <!-- Título -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="titulo" class="fw-bold">Título del Módulo</label>
                                    <input type="text"
                                           name="titulo"
                                           id="titulo"
                                           class="form-control @error('titulo') is-invalid @enderror"
                                           value="{{ old('titulo') }}"
                                           placeholder="Ingrese el título"
                                           required>
                                    @error('titulo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Año -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="anio" class="fw-bold">Año</label>
                                    <input type="text"
                                           name="anio"
                                           id="anio"
                                           class="form-control @error('anio') is-invalid @enderror"
                                           value="{{ old('anio') }}"
                                           placeholder="Ingrese el año">
                                    @error('anio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Categoría -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="categoria" class="fw-bold">Categoría</label>
                                    <input type="text"
                                           name="categoria"
                                           id="categoria"
                                           class="form-control @error('categoria') is-invalid @enderror"
                                           value="{{ old('categoria') }}"
                                           placeholder="Ingrese la categoría"
                                           required>
                                    @error('categoria')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Segunda fila: Color, Sección (opcional) -->
                        <div class="row g-3">
                            
                            <!-- Sección -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="seccion_id" class="fw-bold">Sección</label>
                                    <select name="seccion_id" id="seccion_id" class="form-control @error('seccion_id') is-invalid @enderror">
                                        <option value="" disabled selected>Seleccione una sección</option>
                                        @foreach($secciones as $seccion)
                                            <option value="{{ $seccion->id }}" {{ old('seccion_id') == $seccion->id ? 'selected' : '' }}>
                                                {{ $seccion->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('seccion_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                             <!-- Descripción -->
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="descripcion" class="fw-bold">Descripción</label>
                                    <textarea name="descripcion"
                                              id="descripcion"
                                              class="form-control @error('descripcion') is-invalid @enderror"
                                              placeholder="Ingrese la descripción">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Tercera fila: Descripción y Link -->
                        <div class="row g-3">
                           
                        </div>

                        <hr>

                        <!-- Botones -->
                        <div class="row g-3">
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fa-solid fa-check"></i> Registrar
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
