@extends('layouts.app')

@section('title', 'TI-UTM - Ver Módulo')

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Encabezado del Módulo -->
        <div class="mb-4 text-center">
            <h1>{{ $modulo->titulo }}</h1>
            <p class="text-muted">{{ $modulo->categoria }} - {{ $modulo->anio }}</p>
        </div>

        <!-- Iteramos subsecciones -->
        @foreach($subnivelesPrincipales as $subsec)
            <div class="mb-4">
                <h2 class="p-2 mb-3 text-white" style="background-color: {{ $modulo->color ?? '#1976d2' }};">
                    {{ strtoupper($subsec->nombre) }}
                </h2>
                @if($subsec->carpetas->count())
                    <ul class="list-unstyled ms-4">
                        @foreach($subsec->carpetas as $carpeta)
                            @include('partials.folder_tree', ['folder' => $carpeta])
                        @endforeach
                    </ul>
                @else
                    <p class="ms-4">No hay carpetas en esta subsección.</p>
                @endif
            </div>
        @endforeach

        <!-- Botón de regreso -->
        <div class="text-center mt-4">
            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    ul.list-unstyled {
        font-size: 1.1rem;
    }
    ul.list-unstyled li {
        margin-bottom: 0.5rem;
    }
    .folder-toggle {
        cursor: pointer;
    }
</style>
@endsection

