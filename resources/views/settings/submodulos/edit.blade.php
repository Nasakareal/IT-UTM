@extends('layouts.app')

@section('title', 'TI-UTM - Editar Submódulo')

@section('content')
<div class="row justify-content-center">
    <!-- col-md-10 offset-md-1 = más ancho del card -->
    <div class="col-md-10 offset-md-1"> 
        <div class="card card-outline card-success mb-4">
            <div class="card-header">
                <h3 class="card-title">Llene los Datos</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('submodulos.update', $submodulo->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <!-- Primera fila: Título y Subsección -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="titulo" class="fw-bold">Título del Submódulo</label>
                                <input type="text" name="titulo" id="titulo"
                                       class="form-control @error('titulo') is-invalid @enderror"
                                       value="{{ old('titulo', $submodulo->titulo) }}" placeholder="Ingrese el título" required>
                                @error('titulo')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="subsection_id" class="fw-bold">Subsección</label>
                                <select name="subsection_id" id="subsection_id" class="form-control @error('subsection_id') is-invalid @enderror" required>
                                    <option value="" disabled selected>Seleccione una subsección</option>
                                    @foreach($subsections as $subsection)
                                        <option value="{{ $subsection->id }}" {{ old('subsection_id', $submodulo->subsection_id) == $subsection->id ? 'selected' : '' }}>
                                            {{ $subsection->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subsection_id')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Segunda fila: Fecha Límite y Estatus -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="fecha_limite" class="fw-bold">Fecha Límite</label>
                                <input type="datetime-local" name="fecha_limite" id="fecha_limite"
                                       class="form-control @error('fecha_limite') is-invalid @enderror"
                                       value="{{ old('fecha_limite', $submodulo->fecha_limite) }}">
                                @error('fecha_limite')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="estatus" class="fw-bold">Estatus</label>
                                <select name="estatus" id="estatus" class="form-control @error('estatus') is-invalid @enderror">
                                    <option value="pendiente" {{ old('estatus', $submodulo->estatus) == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="entregado" {{ old('estatus', $submodulo->estatus) == 'entregado' ? 'selected' : '' }}>Entregado</option>
                                    <option value="extemporaneo" {{ old('estatus', $submodulo->estatus) == 'extemporaneo' ? 'selected' : '' }}>Extemporáneo</option>
                                </select>
                                @error('estatus')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Tercera fila: Descripción -->
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label for="descripcion" class="fw-bold">Descripción</label>
                                <textarea name="descripcion" id="descripcion"
                                          class="form-control @error('descripcion') is-invalid @enderror"
                                          placeholder="Ingrese la descripción">{{ old('descripcion', $submodulo->descripcion) }}</textarea>
                                @error('descripcion')
                                    <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
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
                            <a href="{{ route('submodulos.index') }}" class="btn btn-secondary">
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
