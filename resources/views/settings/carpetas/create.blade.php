@extends('layouts.app')

@section('title', 'TI-UTM - Crear Carpeta')

@section('content_header')
    <h1>Creación de una Nueva Carpeta</h1>
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
                    <form action="{{ route('carpetas.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <!-- Nombre -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="nombre" class="fw-bold">Nombre de la Carpeta</label>
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
                            <!-- Color -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="color" class="fw-bold">Color</label>
                                    <input type="color"
                                           name="color"
                                           id="color"
                                           class="form-control @error('color') is-invalid @enderror"
                                           value="{{ old('color', '#009688') }}">
                                    @error('color')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Segunda fila: Carpeta Padre y Subsección -->
                        <div class="row g-3">
                            <!-- Carpeta Padre (opcional) -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="parent_id" class="fw-bold">Carpeta Padre (opcional)</label>
                                    <select name="parent_id"
                                            id="parent_id"
                                            class="form-control @error('parent_id') is-invalid @enderror">
                                        <option value="" selected>Sin carpeta padre</option>
                                        @foreach($allCarpetas as $carpeta)
                                            <option value="{{ $carpeta->id }}" {{ old('parent_id') == $carpeta->id ? 'selected' : '' }}>
                                                {{ $carpeta->nombre }}
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
                            
                            <!-- Subsección (opcional) -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="subsection_id" class="fw-bold">Subsección (opcional)</label>
                                    <select name="subsection_id"
                                            id="subsection_id"
                                            class="form-control @error('subsection_id') is-invalid @enderror">
                                        <option value="" selected>Sin subsección</option>
                                        @foreach($subsections as $subsection)
                                            <option value="{{ $subsection->id }}" {{ old('subsection_id') == $subsection->id ? 'selected' : '' }}>
                                                {{ $subsection->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('subsection_id')
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
                                <a href="{{ route('carpetas.index') }}" class="btn btn-secondary">
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
            @if($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Error en el formulario',
                    html: `
                        <ul style="text-align: left;">
                            @foreach($errors->all() as $error)
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
