@extends('layouts.app')

@section('title', 'TI-UTM - Nuevo Tutorial')

@section('content')
    <div class="container my-4">
        <h1 class="mb-4">Crear Nuevo Tutorial</h1>

        <form action="{{ route('tutoriales.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Título --}}
            <div class="mb-3">
                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                <input type="text"
                       name="titulo"
                       id="titulo"
                       class="form-control @error('titulo') is-invalid @enderror"
                       value="{{ old('titulo') }}"
                       maxlength="255"
                       required>
                @error('titulo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Descripción --}}
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea name="descripcion"
                          id="descripcion"
                          class="form-control @error('descripcion') is-invalid @enderror"
                          rows="3">{{ old('descripcion') }}</textarea>
                @error('descripcion')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Tipo --}}
            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                <select name="tipo"
                        id="tipo"
                        class="form-select @error('tipo') is-invalid @enderror"
                        required>
                    <option value="" disabled {{ old('tipo') ? '' : 'selected' }}>Selecciona uno...</option>
                    <option value="video" {{ old('tipo')==='video' ? 'selected' : '' }}>Video</option>
                    <option value="imagenes" {{ old('tipo')==='imagenes' ? 'selected' : '' }}>Imágenes</option>
                </select>
                @error('tipo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- URL (solo si tipo=video) --}}
            <div class="mb-3" id="url-group" style="{{ old('tipo')==='video' ? '' : 'display:none;' }}">
                <label for="url" class="form-label">URL del Video <span class="text-danger">*</span></label>
                <input type="url"
                       name="url"
                       id="url"
                       class="form-control @error('url') is-invalid @enderror"
                       value="{{ old('url') }}">
                @error('url')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Imágenes (solo si tipo=imagenes) --}}
            <div class="mb-3" id="imagenes-group" style="{{ old('tipo')==='imagenes' ? '' : 'display:none;' }}">
                <label for="imagenes" class="form-label">Seleccionar Imágenes</label>
                <input type="file"
                       name="imagenes[]"
                       id="imagenes"
                       class="form-control @error('imagenes.*') is-invalid @enderror"
                       accept="image/*"
                       multiple>
                @error('imagenes.*')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Puedes subir varias imágenes (máx 2 MB c/u).</div>
            </div>

            {{-- Botón Enviar --}}
            <button type="submit" class="btn btn-primary">Guardar Tutorial</button>
            <a href="{{ route('tutoriales.index') }}" class="btn btn-secondary ms-2">Cancelar</a>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        // Mostrar/ocultar campos según selección de "tipo"
        document.addEventListener('DOMContentLoaded', function() {
            const tipoSelect = document.getElementById('tipo');
            const urlGroup = document.getElementById('url-group');
            const imagenesGroup = document.getElementById('imagenes-group');

            tipoSelect.addEventListener('change', function() {
                if (this.value === 'video') {
                    urlGroup.style.display = '';
                    imagenesGroup.style.display = 'none';
                } else if (this.value === 'imagenes') {
                    urlGroup.style.display = 'none';
                    imagenesGroup.style.display = '';
                } else {
                    urlGroup.style.display = 'none';
                    imagenesGroup.style.display = 'none';
                }
            });
        });
    </script>
@endsection
