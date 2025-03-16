@extends('layouts.app')

@section('title', 'TI-UTM - Editar Rol')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos del Rol</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row justify-content-center">
                            <!-- Nombre del Rol -->
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name">Nombre del Rol</label>
                                    <input type="text" name="name" id="name" class="form-control" 
                                           value="{{ old('name', $role->name) }}" 
                                           placeholder="Ingrese el nuevo nombre del rol" required>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Guardar Cambios
                                    </button>
                                    <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
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

