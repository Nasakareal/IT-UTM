@extends('layouts.app')

@section('title', 'TI-UTM ‑ Editar Usuario')

@section('content_header')
    <h1>Edición de Usuario</h1>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10 offset-md-1">
            <div class="card card-outline card-success mb-4"><!-- success -->
                <div class="card-header bg-success text-white"><!-- success -->
                    <h3 class="card-title">Actualice los datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('users.update', $user->id) }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Nombre completo -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="name" class="fw-bold">Nombre Completo</label>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $user->name) }}"
                                       required>
                                @error('name')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Nombres o selector de profesor -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombres" class="fw-bold">Nombres</label>

                                <input type="text"
                                       name="nombres"
                                       id="input_nombres"
                                       class="form-control @error('nombres') is-invalid @enderror"
                                       value="{{ old('nombres', $user->nombres) }}">

                                <select name="nombres"
                                        id="select_nombres"
                                        class="form-control @error('nombres') is-invalid @enderror"
                                        style="display:none">
                                    <option value="" disabled>-- Seleccione profesor --</option>
                                    @foreach($profesores as $profe)
                                        <option value="{{ $profe->teacher_name }}"
                                                data-id="{{ $profe->teacher_id }}"
                                                {{ old('nombres', $user->nombres) == $profe->teacher_name ? 'selected' : '' }}>
                                            {{ $profe->teacher_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('nombres')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror

                                <input type="hidden"
                                       name="teacher_id"
                                       id="teacher_id"
                                       value="{{ old('teacher_id', $user->teacher_id) }}">
                            </div>
                        </div>

                        <!-- Apellidos -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label class="fw-bold" for="apellido_paterno">Apellido Paterno</label>
                                <input type="text"
                                       name="apellido_paterno"
                                       id="apellido_paterno"
                                       class="form-control @error('apellido_paterno') is-invalid @enderror"
                                       value="{{ old('apellido_paterno', $user->apellido_paterno) }}">
                                @error('apellido_paterno')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold" for="apellido_materno">Apellido Materno</label>
                                <input type="text"
                                       name="apellido_materno"
                                       id="apellido_materno"
                                       class="form-control @error('apellido_materno') is-invalid @enderror"
                                       value="{{ old('apellido_materno', $user->apellido_materno) }}">
                                @error('apellido_materno')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- CURP y correos -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label class="fw-bold" for="curp">CURP</label>
                                <input type="text"
                                       name="curp"
                                       id="curp"
                                       maxlength="18"
                                       class="form-control @error('curp') is-invalid @enderror"
                                       value="{{ old('curp', $user->curp) }}"
                                       required>
                                @error('curp')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold" for="correo_institucional">Correo Institucional</label>
                                <input type="email"
                                       name="correo_institucional"
                                       id="correo_institucional"
                                       class="form-control @error('correo_institucional') is-invalid @enderror"
                                       value="{{ old('correo_institucional', $user->correo_institucional) }}"
                                       required>
                                @error('correo_institucional')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="fw-bold" for="correo_personal">Correo Personal</label>
                                <input type="email"
                                       name="correo_personal"
                                       id="correo_personal"
                                       class="form-control @error('correo_personal') is-invalid @enderror"
                                       value="{{ old('correo_personal', $user->correo_personal) }}"
                                       required>
                                @error('correo_personal')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Categoría, carácter, rol -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-3">
                                <label class="fw-bold" for="categoria">Categoría</label>
                                <select name="categoria"
                                        id="categoria"
                                        class="form-control @error('categoria') is-invalid @enderror"
                                        required>
                                    <option value="" disabled>Seleccione categoría</option>
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat }}"
                                            {{ old('categoria', $user->categoria) == $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categoria')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="fw-bold" for="caracter">Carácter</label>
                                <select name="caracter"
                                        id="caracter"
                                        class="form-control @error('caracter') is-invalid @enderror"
                                        required>
                                    <option value="" disabled>Seleccione carácter</option>
                                    @foreach($caracteres as $car)
                                        <option value="{{ $car }}"
                                            {{ old('caracter', $user->caracter) == $car ? 'selected' : '' }}>
                                            {{ $car }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('caracter')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold" for="role">Rol</label>
                                <select name="role"
                                        id="role"
                                        class="form-control @error('role') is-invalid @enderror"
                                        required>
                                    <option value="" disabled>Seleccione un rol</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}"
                                            {{ old('role', $user->roles->first()->name ?? '') == $role->name ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Área y foto -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="fw-bold" for="area">Área</label>
                                <input type="text"
                                       name="area"
                                       id="area"
                                       class="form-control @error('area') is-invalid @enderror"
                                       value="{{ old('area', $user->area) }}">
                                @error('area')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold" for="foto_perfil">Foto de Perfil</label>
                                <input type="file"
                                       name="foto_perfil"
                                       id="foto_perfil"
                                       class="form-control @error('foto_perfil') is-invalid @enderror">
                                @error('foto_perfil')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <!-- Contraseña (opcional al editar) -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="fw-bold" for="password">Contraseña</label>
                                <div class="input-group">
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Dejar en blanco para no cambiar">
                                    <button type="button" class="btn btn-outline-secondary" id="btnGenerate">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold" for="password_confirmation">Repetir Contraseña</label>
                                <input type="password"
                                       name="password_confirmation"
                                       id="password_confirmation"
                                       class="form-control">
                            </div>
                        </div>

                        <hr class="mt-4">

                        <!-- Botones -->
                        <div class="row">
                            <div class="col text-end">
                                <button type="submit" class="btn btn-success me-2"><!-- success -->
                                    <i class="fa-solid fa-save"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
                </div><!-- /.card-body -->
            </div><!-- /.card -->
        </div>
    </div>
@stop

@push('scripts')
<script>
/* -------- funciones JS -------- */
function genPass(len = 12) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-';
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
    Swal.fire({ icon:'info', title:'Contraseña generada', html:`<code>${p}</code>` });
});

const roleEl      = document.getElementById('role');
const txtNombres  = document.getElementById('input_nombres');
const selProfe    = document.getElementById('select_nombres');
const hidTeacher  = document.getElementById('teacher_id');

function toggleFields() {
    if (roleEl.value === 'Profesor') {
        txtNombres.style.display = 'none'; txtNombres.required = false;
        selProfe.style.display   = 'block'; selProfe.required = true;
    } else {
        txtNombres.style.display = 'block'; txtNombres.required = true;
        selProfe.style.display   = 'none';  selProfe.required = false;
        hidTeacher.value = '';
    }
}
roleEl.addEventListener('change', toggleFields);
selProfe.addEventListener('change', e => {
    hidTeacher.value = e.target.selectedOptions[0].dataset.id;
});
window.addEventListener('load', () => {
    toggleFields();
    if (selProfe.value) hidTeacher.value = selProfe.selectedOptions[0].dataset.id;
});
</script>
@endpush
