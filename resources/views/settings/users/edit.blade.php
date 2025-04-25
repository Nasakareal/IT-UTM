@extends('layouts.app')

@section('title', 'TI-UTM - Editar Usuario')

@section('content_header')
    <h1>Edición de Usuario</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10 offset-md-1"> 
            <div class="card card-outline card-success mb-4">
                <div class="card-header">
                    <h3 class="card-title">Modifique los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Primera fila: Nombres, Apellido Paterno, Apellido Materno -->
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="nombres" class="fw-bold">Nombres</label>
                                <input type="text" name="nombres" id="nombres"
                                       class="form-control @error('nombres') is-invalid @enderror"
                                       value="{{ old('nombres', $user->nombres) }}" required>
                                @error('nombres')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="apellido_paterno" class="fw-bold">Apellido Paterno</label>
                                <input type="text" name="apellido_paterno" id="apellido_paterno"
                                       class="form-control @error('apellido_paterno') is-invalid @enderror"
                                       value="{{ old('apellido_paterno', $user->apellido_paterno) }}">
                                @error('apellido_paterno')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="apellido_materno" class="fw-bold">Apellido Materno</label>
                                <input type="text" name="apellido_materno" id="apellido_materno"
                                       class="form-control @error('apellido_materno') is-invalid @enderror"
                                       value="{{ old('apellido_materno', $user->apellido_materno) }}">
                                @error('apellido_materno')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                        </div>

                        <!-- Segunda fila: CURP, Correo Institucional, Correo Personal -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label for="curp" class="fw-bold">CURP</label>
                                <input type="text" name="curp" id="curp"
                                       class="form-control @error('curp') is-invalid @enderror"
                                       value="{{ old('curp', $user->curp) }}" maxlength="18" required>
                                @error('curp')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="correo_institucional" class="fw-bold">Correo Institucional</label>
                                <input type="email" name="correo_institucional" id="correo_institucional"
                                       class="form-control @error('correo_institucional') is-invalid @enderror"
                                       value="{{ old('correo_institucional', $user->correo_institucional) }}" required>
                                @error('correo_institucional')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="correo_personal" class="fw-bold">Correo Personal</label>
                                <input type="email" name="correo_personal" id="correo_personal"
                                       class="form-control @error('correo_personal') is-invalid @enderror"
                                       value="{{ old('correo_personal', $user->correo_personal) }}" required>
                                @error('correo_personal')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                        </div>

                        <!-- Tercera fila: Categoría, Carácter, Estado -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label for="categoria" class="fw-bold">Categoría</label>
                                <select name="categoria" id="categoria"
                                        class="form-control @error('categoria') is-invalid @enderror" required>
                                    <option value="" disabled>Seleccione categoría</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat }}"
                                            {{ old('categoria', $user->categoria) == $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categoria')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="caracter" class="fw-bold">Carácter</label>
                                <select name="caracter" id="caracter"
                                        class="form-control @error('caracter') is-invalid @enderror" required>
                                    <option value="" disabled>Seleccione carácter</option>
                                    @foreach($caracteres as $car)
                                        <option value="{{ $car }}"
                                            {{ old('caracter', $user->caracter) == $car ? 'selected' : '' }}>
                                            {{ $car }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('caracter')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="estado" class="fw-bold">Estado</label>
                                <input type="text" name="estado" id="estado"
                                       class="form-control @error('estado') is-invalid @enderror"
                                       value="{{ old('estado', $user->estado) }}" required>
                                @error('estado')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                        </div>

                        <!-- Cuarta fila: Área, Foto de Perfil -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label for="area" class="fw-bold">Área</label>
                                <input type="text" name="area" id="area"
                                       class="form-control @error('area') is-invalid @enderror"
                                       value="{{ old('area', $user->area) }}">
                                @error('area')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="foto_perfil" class="fw-bold">Foto de Perfil</label>
                                <input type="file" name="foto_perfil" id="foto_perfil"
                                       class="form-control @error('foto_perfil') is-invalid @enderror">
                                @if($user->foto_perfil)
                                    <small class="text-muted">Actual: <a href="{{ asset('storage/'.$user->foto_perfil) }}" target="_blank">ver imagen</a></small>
                                @endif
                                @error('foto_perfil')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                        </div>

                        <!-- Quinta fila: Rol, Nueva Contraseña, Confirmación -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label for="role" class="fw-bold">Rol</label>
                                <select name="role" id="role"
                                        class="form-control @error('role') is-invalid @enderror" required>
                                    <option value="" disabled>Seleccione un rol</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}"
                                            {{ old('role', $user->roles->pluck('name')->first()) == $role->name ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="fw-bold">Nueva Contraseña (opcional)</label>
                                <input type="password" name="password" id="password"
                                       class="form-control @error('password') is-invalid @enderror">
                                @error('password')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="password_confirmation" class="fw-bold">Repetir Contraseña</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="form-control">
                            </div>
                        </div>

                        <hr class="mt-4">

                        <!-- Botones -->
                        <div class="row">
                            <div class="col text-end">
                                <button type="submit" class="btn btn-success me-2">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
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

