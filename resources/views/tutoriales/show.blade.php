@extends('layouts.app')

@section('title', 'TI-UTM - Detalle del Tutorial')

@section('content')
    <div class="container my-4">
        <h1 class="mb-4 text-info">Detalle del Tutorial</h1>

        <div class="card border-info shadow-sm" style="border-radius: 10px; overflow: hidden;">
            <div class="card-body">
                {{-- Título --}}
                <h3 class="card-title">{{ $tutorial->titulo }}</h3>

                {{-- Descripción --}}
                @if($tutorial->descripcion)
                    <p class="card-text">{{ $tutorial->descripcion }}</p>
                @endif

                {{-- Tipo --}}
                <p>
                    <strong>Tipo:</strong>
                    <span class="badge bg-info text-white">{{ ucfirst($tutorial->tipo) }}</span>
                </p>

                @if($tutorial->tipo === 'video')
                    {{-- Mostrar video embebido si es posible --}}
                    <p><strong>URL del Video:</strong></p>
                    <a href="{{ $tutorial->url }}" target="_blank">
                        {{ $tutorial->url }}
                    </a>

                    {{-- Si quieres incrustar un iframe de YouTube, revisa si la URL contiene “youtube.com” --}}
                    @if(str_contains($tutorial->url, 'youtube.com') || str_contains($tutorial->url, 'youtu.be'))
                        <div class="mt-3 ratio ratio-16x9">
                            <iframe
                                src="{{ str_replace('watch?v=', 'embed/', $tutorial->url) }}"
                                title="Video Tutorial"
                                allowfullscreen>
                            </iframe>
                        </div>
                    @endif
                @else
                    {{-- Galería de imágenes --}}
                    <p><strong>Imágenes:</strong></p>
                    @if(is_array($tutorial->imagenes) && count($tutorial->imagenes) > 0)
                        <div class="row g-3">
                            @foreach($tutorial->imagenes as $imgPath)
                                <div class="col-6 col-md-4">
                                    <div class="card">
                                        <a href="{{ asset('storage/' . $imgPath) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $imgPath) }}"
                                                 class="img-fluid rounded"
                                                 alt="Imagen del tutorial">
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No hay imágenes disponibles.</p>
                    @endif
                @endif

                {{-- Fecha de creación y actualización --}}
                <hr>
                <p class="text-secondary small">
                    Creado: {{ $tutorial->created_at->format('d/m/Y H:i') }} <br>
                    Actualizado: {{ $tutorial->updated_at->format('d/m/Y H:i') }}
                </p>
            </div>
            <div class="card-footer text-end bg-light">
                <a href="{{ route('tutoriales.index') }}" class="btn btn-info btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
@endsection
