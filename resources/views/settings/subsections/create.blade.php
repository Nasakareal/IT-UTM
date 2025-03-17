@extends('layouts.app')

@section('title', 'TI-UTM - Crear Subsección')

@section('content_header')
    <h1>Creación de una Nueva Subsección</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <!-- Card para el formulario -->
        <div class="col-md-10 offset-md-1"> 
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('subsections.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <!-- Nombre de la Subsección -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nombre" class="fw-bold">Nombre de la Subsección</label>
                                    <input type="text"
                                           name="nombre"
                                           id="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre') }}"
                                           placeholder="Ingrese el nombre"
                                           required>
                                    @error('nombre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <!-- Seleccionar Módulo -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="modulo_id" class="fw-bold">Módulo</label>
                                    <select name="modulo_id"
                                            id="modulo_id"
                                            class="form-control @error('modulo_id') is-invalid @enderror"
                                            required>
                                        <option value="" disabled selected>Seleccione un módulo</option>
                                        @foreach($modulos as $modulo)
                                            <option value="{{ $modulo->id }}" {{ old('modulo_id') == $modulo->id ? 'selected' : '' }}>
                                                {{ $modulo->titulo }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('modulo_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seleccionar Subsección Padre (opcional) -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="parent_id" class="fw-bold">Subsección Padre (opcional)</label>
                                    <select name="parent_id"
                                            id="parent_id"
                                            class="form-control @error('parent_id') is-invalid @enderror">
                                        <option value="" selected>Sin subsección padre</option>
                                        @foreach($subsections as $existingSubsection)
                                            <option value="{{ $existingSubsection->id }}" {{ old('parent_id') == $existingSubsection->id ? 'selected' : '' }}>
                                                {{ $existingSubsection->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('parent_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <hr>
                        <div class="row g-3">
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fa-solid fa-check"></i> Registrar
                                </button>
                                <a href="{{ route('subsections.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div><!-- .card-body -->
            </div><!-- .card -->
        </div><!-- .col-md-10 offset-md-1 -->
    </div><!-- .row -->
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
