@extends('layouts.app')

@section('title', 'TI-UTM - Ver Módulo')

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Opcional: Encabezado del módulo -->
        <div class="mb-4 text-center">
            <h1>{{ $modulo->titulo }}</h1>
            <p class="text-muted">{{ $modulo->categoria }} - {{ $modulo->anio }}</p>
        </div>

        <!-- Estructura jerárquica de subsecciones y carpetas -->
        @foreach($subnivelesPrincipales as $subsec)
            <div class="mb-4">
                <!-- Título del subnivel principal -->
                <h2 class="p-2 mb-3 text-white" style="background-color: #1976d2;">
                    {{ strtoupper($subsec->nombre) }}
                </h2>

                @if($subsec->carpetas->count())
                    <ul class="list-unstyled ms-4">
                        @foreach($subsec->carpetas as $carpeta)
                            <li>
                                <i class="fa fa-folder text-primary"></i>
                                <a href="{{ route('carpetas.show', $carpeta->id) }}" class="text-primary">
                                    {{ $carpeta->nombre }}
                                </a>
                                <!-- Si la carpeta tiene subcarpetas, se muestran recursivamente -->
                                @if($carpeta->children->count())
                                    @include('partials.folder_tree', ['folders' => $carpeta->children])
                                @endif
                            </li>
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
    /* Estilos para la lista de carpetas */
    ul.list-unstyled {
        font-size: 1.1rem;
    }
    ul.list-unstyled li {
        margin-bottom: 0.5rem;
    }
</style>
@endsection

@section('scripts')
<script>
    // Puedes agregar aquí scripts específicos si los requieres
</script>
@endsection
