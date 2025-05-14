@extends('layouts.app')

@section('title', 'TI-UTM - Crear Usuario')

@section('content_header')
    <h1>Creación de un Nuevo Usuario</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10 offset-md-1"> 
            <div class="card card-outline card-primary mb-4">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Campo adicional: Nombre Completo -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="name" class="fw-bold">Nombre Completo</label>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}"
                                       required
                                >
                                @error('name')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Primera fila: Nombres / Selección de Profesor -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombres" class="fw-bold">Nombres</label>
                                <!-- Input de texto por defecto -->
                                <input type="text"
                                       name="nombres"
                                       id="nombres"
                                       class="form-control @error('nombres') is-invalid @enderror"
                                       value="{{ old('nombres') }}"
                                       required
                                       style="display: none;"
                                >
                                <!-- Select de profesores para rol Profesor -->
                                <select name="nombres"
                                        id="select_profesor"
                                        class="form-control @error('nombres') is-invalid @enderror"
                                        style="display: none;"
                                >
                                    <option value="" disabled selected>-- Selecciona profesor --</option>
                                    @foreach($profesores as $profe)
                                        <option value="{{ $profe->teacher_name }}"
                                                data-id="{{ $profe->teacher_id }}"
                                                {{ old('nombres') == $profe->teacher_name ? 'selected' : '' }}>
                                            {{ $profe->teacher_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('nombres')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror

                                <!-- Campo oculto para guardar teacher_id -->
                                <input type="hidden"
                                       name="teacher_id"
                                       id="teacher_id"
                                       value="{{ old('teacher_id') }}"
                                >
                            </div>
                        </div>

                        <!-- Segunda fila: Apellidos -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label for="apellido_paterno" class="fw-bold">Apellido Paterno</label>
                                <input type="text"
                                       name="apellido_paterno"
                                       id="apellido_paterno"
                                       class="form-control @error('apellido_paterno') is-invalid @enderror"
                                       value="{{ old('apellido_paterno') }}"
                                >
                                @error('apellido_paterno')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="apellido_materno" class="fw-bold">Apellido Materno</label>
                                <input type="text"
                                       name="apellido_materno"
                                       id="apellido_materno"
                                       class="form-control @error('apellido_materno') is-invalid @enderror"
                                       value="{{ old('apellido_materno') }}"
                                >
                                @error('apellido_materno')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Tercera fila: CURP, Correo Institucional, Correo Personal -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label for="curp" class="fw-bold">CURP</label>
                                <input type="text"
                                       name="curp"
                                       id="curp"
                                       class="form-control @error('curp') is-invalid @enderror"
                                       value="{{ old('curp') }}"
                                       maxlength="18"
                                       required
                                >
                                @error('curp')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="correo_institucional" class="fw-bold">Correo Institucional</label>
                                <input type="email"
                                       name="correo_institucional"
                                       id="correo_institucional"
                                       class="form-control @error('correo_institucional') is-invalid @enderror"
                                       value="{{ old('correo_institucional') }}"
                                       required
                                >
                                @error('correo_institucional')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="correo_personal" class="fw-bold">Correo Personal</label>
                                <input type="email"
                                       name="correo_personal"
                                       id="correo_personal"
                                       class="form-control @error('correo_personal') is-invalid @enderror"
                                       value="{{ old('correo_personal') }}"
                                       required
                                >
                                @error('correo_personal')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Cuarta fila: Categoría, Carácter, Rol -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-3">
                                <label for="categoria" class="fw-bold">Categoría</label>
                                <select name="categoria"
                                        id="categoria"
                                        class="form-control @error('categoria') is-invalid @enderror"
                                        required
                                >
                                    <option value="" disabled selected>Seleccione categoría</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat }}"
                                                {{ old('categoria') == $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categoria')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="caracter" class="fw-bold">Carácter</label>
                                <select name="caracter"
                                        id="caracter"
                                        class="form-control @error('caracter') is-invalid @enderror"
                                        required
                                >
                                    <option value="" disabled selected>Seleccione carácter</option>
                                    @foreach($caracteres as $car)
                                        <option value="{{ $car }}"
                                                {{ old('caracter') == $car ? 'selected' : '' }}>
                                            {{ $car }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('caracter')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="fw-bold">Rol</label>
                                <select name="role"
                                        id="role"
                                        class="form-control @error('role') is-invalid @enderror"
                                        required
                                >
                                    <option value="" disabled selected>Seleccione un rol</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}"
                                                {{ old('role') == $role->name ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Quinta fila: Área, Foto de Perfil -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label for="area" class="fw-bold">Área</label>
                                <input type="text"
                                       name="area"
                                       id="area"
                                       class="form-control @error('area') is-invalid @enderror"
                                       value="{{ old('area') }}"
                                >
                                @error('area')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="foto_perfil" class="fw-bold">Foto de Perfil</label>
                                <input type="file"
                                       name="foto_perfil"
                                       id="foto_perfil"
                                       class="form-control @error('foto_perfil') is-invalid @enderror"
                                >
                                @error('foto_perfil')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Sexta fila: Contraseña, Confirmación -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="fw-bold">Contraseña</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Ingrese o genere una contraseña"
                                           required
                                    >
                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            id="btnGenerate">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="fw-bold">Repetir Contraseña</label>
                                <input type="password"
                                       name="password_confirmation"
                                       id="password_confirmation"
                                       class="form-control"
                                       required
                                >
                            </div>
                        </div>

                        <hr class="mt-4">

                        <!-- Botones -->
                        <div class="row">
                            <div class="col text-end">
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
        .form-group label { font-weight: bold; }
        .card { max-width: 100%; }
    </style>
@stop

@section('scripts')
    <script>
        // Generar contraseña
        function genPass(len = 12) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+~';
            let pass = '';
            const arr = new Uint32Array(len);
            crypto.getRandomValues(arr);
            for (let i = 0; i < len; i++) pass += chars[arr[i] % chars.length];
            return pass;
        }
        document.getElementById('btnGenerate').addEventListener('click', () => {
            const p = genPass(12);
            document.getElementById('password').value = p;
            document.getElementById('password_confirmation').value = p;
            Swal.fire({
                icon: 'info',
                title: 'Contraseña generada',
                html: `<code style="user-select:all">${p}</code>`,
                showCancelButton: true,
                confirmButtonText: 'Copiar',
                cancelButtonText: 'Cerrar'
            }).then(r => {
                if (r.isConfirmed) {
                    navigator.clipboard.writeText(p);
                    Swal.fire({ icon: 'success', title: '¡Copiada!' });
                }
            });
        });

        // Toggle entre input de texto y select de profesor
        const roleEl       = document.getElementById('role');
        const textNombres  = document.getElementById('nombres');
        const selectProfe  = document.getElementById('select_profesor');
        const hiddenTeacher = document.getElementById('teacher_id');

        function toggleProfesorFields() {
            if (roleEl.value === 'Profesor') {
                textNombres.style.display     = 'none';  textNombres.required    = false;
                selectProfe.style.display     = 'block'; selectProfe.required    = true;
            } else {
                textNombres.style.display     = 'block'; textNombres.required    = true;
                selectProfe.style.display     = 'none';  selectProfe.required    = false;
                hiddenTeacher.value           = '';
            }
        }

        roleEl.addEventListener('change', toggleProfesorFields);
        selectProfe.addEventListener('change', e => {
            hiddenTeacher.value = e.target.selectedOptions[0].dataset.id;
        });
        window.addEventListener('load', () => {
            toggleProfesorFields();
            if (selectProfe.value) {
                hiddenTeacher.value = selectProfe.selectedOptions[0].dataset.id;
            }
        });

        // Validación con SweetAlert
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
                        </ul>`,
                    confirmButtonText: 'Aceptar'
                });
            @endif
        });
    </script>
@stop
