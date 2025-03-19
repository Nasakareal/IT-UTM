@extends('layouts.app')

@section('title', 'TI-UTM - Crear Comunicado')

@section('content_header')
    <h1>Creación de un Nuevo Comunicado</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10 offset-md-1"> 
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('comunicados.store') }}" method="POST">
                        @csrf

                        <!-- Primera fila: Título y Tipo -->
                        <div class="row g-3">
                            <!-- Título -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="titulo" class="fw-bold">Título del Comunicado</label>
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

                            <!-- Tipo -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="tipo" class="fw-bold">Tipo</label>
                                    <input type="text"
                                           name="tipo"
                                           id="tipo"
                                           class="form-control @error('tipo') is-invalid @enderror"
                                           value="{{ old('tipo') }}"
                                           placeholder="Ingrese el Tipo"
                                           required>
                                    @error('tipo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Segunda fila: Contenido -->
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="contenido" class="fw-bold">Contenido</label>
                                    <textarea name="contenido"
                                              id="contenido"
                                              class="form-control @error('contenido') is-invalid @enderror"
                                              placeholder="Ingrese el contenido">{{ old('contenido') }}</textarea>
                                    @error('contenido')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Botones -->
                        <div class="row g-3">
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fa-solid fa-check"></i> Registrar
                                </button>
                                <a href="{{ route('comunicados.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Cargar CKEditor con la versión más reciente -->
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>


    <script>
        CKEDITOR.replace('contenido', {
            extraAllowedContent: 'b strong i em u p br ul ol li',
            removePlugins: 'easyimage, cloudservices',
            height: 250,
            notification_duration: 0
        });
    </script>


@stop

@section('styles')
    <style>
        .form-group label {
            font-weight: bold;
        }
        .card {
            max-width: 100%;
        }
        .cke_notification { display: none !important; }
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
