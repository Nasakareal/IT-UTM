@extends('layouts.app')

@section('title', 'Carpeta: ' . $carpeta->nombre)

@section('content')
<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">{{ $carpeta->nombre }}</h1>
        
        <!-- Información básica de la carpeta -->
        <p><strong>Color:</strong> {{ $carpeta->color ?? 'N/A' }}</p>
        
        <!-- Archivos en esta carpeta -->
        @if($carpeta->archivos->count())
            <h3>Archivos</h3>
            <ul class="list-unstyled ml-4">
                @foreach($carpeta->archivos as $archivo)
                    <li>
                        <i class="fa fa-file"></i>
                        <a href="{{ asset('storage/' . $archivo->ruta) }}" target="_blank">
                            {{ $archivo->nombre }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No hay archivos en esta carpeta.</p>
        @endif
        
        <!-- Subcarpetas (solo un nivel) -->
        @if($carpeta->children->count())
            <h3>Subcarpetas</h3>
            <ul class="list-unstyled ml-4">
                @foreach($carpeta->children as $child)
                    <li>
                        <i class="fa fa-folder"></i>
                        <a href="{{ route('carpetas.show', $child->id) }}">
                            {{ $child->nombre }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No hay subcarpetas.</p>
        @endif
        
        
    </div>
</div>
@endsection
