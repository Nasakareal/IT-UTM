@extends('layouts.app')

@section('title', 'TI-UTM - Cambiar Contraseña')

@section('content')
<!-- Fila para el botón Regresar, alineado a la derecha -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url('/') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<!-- Fila principal con la tarjeta de Cambio de Contraseña -->
<div class="row">
    <div class="col-md-12">
        <div class="card" style="border-radius: 8px; overflow: hidden;">
            <div class="card-header" style="background-color: #1976d2;">
                <h3 class="card-title text-white mb-0">Cambio de Contraseña Obligatorio</h3>
            </div>

            <div class="card-body">
                <form action="{{ route('password.change.update') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <small class="form-text text-muted">
                            La contraseña debe contener al menos 8 caracteres, una letra mayúscula, una minúscula, un número y un carácter especial (@, $, !, %, *, #, ? o &).
                        </small>
                        @error('password')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                        @error('password_confirmation')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .card-header {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
</style>
@stop

@section('scripts')
<script>
    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 5000
        });
    @endif
</script>
@stop
