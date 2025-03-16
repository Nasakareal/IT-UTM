@extends('layouts.app')

@section('title', 'TI-UTM - Crear Usuario')

@section('content_header')
    <h1>Creación de un Nuevo Usuario</h1>
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
                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf
                        <!-- Primera fila: Nombre, Email, Área -->
                        <div class="row g-3">
                            <!-- Nombre -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="name" class="fw-bold">Nombre del Usuario</label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}"
                                           placeholder="Ingrese el nombre"
                                           required>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="email" class="fw-bold">Email</label>
                                    <input type="email"
                                           name="email"
                                           id="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}"
                                           placeholder="Ingrese el correo electrónico"
                                           required>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Área -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="area" class="fw-bold">Área</label>
                                    <input type="text"
                                           name="area"
                                           id="area"
                                           class="form-control @error('area') is-invalid @enderror"
                                           value="{{ old('area') }}"
                                           placeholder="Ingrese el área"
                                           required>
                                    @error('area')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Segunda fila: Rol, Contraseña, Confirmación -->
                        <div class="row g-3">
                            <!-- Rol -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="role" class="fw-bold">Rol</label>
                                    <select name="role"
                                            id="role"
                                            class="form-control @error('role') is-invalid @enderror"
                                            required>
                                        <option value="" disabled selected>Seleccione un rol</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}"
                                                {{ old('role') == $role->name ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Contraseña -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="password" class="fw-bold">Contraseña</label>
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Ingrese la contraseña"
                                           required
                                           pattern="^(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$"
                                           title="La contraseña debe tener al menos 8 caracteres, incluir un dígito y un carácter especial.">
                                    <small class="form-text text-muted mt-1">
                                        La contraseña debe tener al menos 8 caracteres, un dígito y un carácter especial.
                                    </small>

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="password_confirmation" class="fw-bold">Repetir Contraseña</label>
                                    <input type="password"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           class="form-control"
                                           placeholder="Confirme la contraseña"
                                           required>
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
                                <a href="{{ route('users.index') }}" class="btn btn-secondary">
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
        /* Ajustes generales para dar más espacio a los campos */
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
