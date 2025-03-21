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
            <div class="mb-5">
                <h2 class="p-2 mb-3 text-white" style="background-color: {{ $modulo->color ?? '#1976d2' }};">
                    {{ strtoupper($subsec->nombre) }}
                </h2>

                {{-- Submódulos con el nuevo diseño de tarjeta --}}
                @if($subsec->submodulos->count())
                    <div class="row ms-2 mb-4">
                        @foreach($subsec->submodulos as $submodulo)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card shadow-sm border-0" style="border-radius: 10px; overflow: hidden;">
                                    <!-- Encabezado -->
                                    <div class="card-header text-white text-center fw-bold" 
                                         style="background-color: #009688;">
                                        {{ $submodulo->titulo }}
                                    </div>

                                    <!-- Contenido -->
                                    <div class="card-body text-center">
                                        <p class="mb-1 text-dark fw-bold">{{ $submodulo->descripcion ?? 'Sin descripción' }}</p>
                                        
                                        <p class="mb-1 text-muted"><strong>Estatus:</strong> 
                                            <span class="{{ $submodulo->estatus == 'pendiente' ? 'text-warning' : ($submodulo->estatus == 'entregado' ? 'text-success' : 'text-danger') }}">
                                                {{ ucfirst($submodulo->estatus) }}
                                            </span>
                                        </p>

                                        @if($submodulo->fecha_limite)
                                            <p class="mb-2 text-muted"><strong>Fecha de entrega:</strong> 
                                                {{ \Carbon\Carbon::parse($submodulo->fecha_limite)->format('Y-m-d H:i:s') }}
                                            </p>
                                        @endif
                                    </div>

                                    <!-- Pie con botón de acción -->
                                    <div class="card-footer text-center bg-light">
                                        <button class="btn btn-info text-white ver-detalle-submodulo"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detalleSubmoduloModal"
                                            data-id="{{ $submodulo->id }}"
                                            data-titulo="{{ $submodulo->titulo }}"
                                            data-descripcion="{{ $submodulo->descripcion }}"
                                            data-estatus="{{ ucfirst($submodulo->estatus) }}"
                                            data-fecha="{{ $submodulo->fecha_limite ? \Carbon\Carbon::parse($submodulo->fecha_limite)->format('Y-m-d H:i:s') : 'No definida' }}"
                                            data-acuse="{{ $submodulo->acuse_pdf ? asset('storage/' . $submodulo->acuse_pdf) : '' }}">
                                        <i class="fa fa-info-circle"></i> Detalles
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Carpetas (con archivos y subcarpetas) --}}
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

<!-- Modal para ver detalles del submódulo -->
<div class="modal fade" id="detalleSubmoduloModal" tabindex="-1" aria-labelledby="detalleSubmoduloModalLabel" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Encabezado -->
            <div class="modal-header">
                <h5 class="modal-title" id="detalleSubmoduloModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <p><strong>Estatus:</strong> <span id="modalEstatus"></span></p>
                <p><strong>Fecha de entrega:</strong> <span id="modalFecha"></span></p>
                <p><strong>Descripción:</strong></p>
                <p id="modalDescripcion"></p>

                <div class="mt-3" id="acuseContainer" style="display: none;">
                    <a id="modalAcuse" href="#" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-file-pdf"></i> Ver Acuse
                    </a>
                </div>
            </div>

            <!-- Pie del modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

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

    /* Estilos para la tarjeta de submódulo */
    .card {
        transition: transform 0.2s, box-shadow 0.3s;
    }
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.ver-detalle-submodulo').on('click', function() {
            let titulo = $(this).data('titulo');
            let descripcion = $(this).data('descripcion');
            let estatus = $(this).data('estatus');
            let fecha = $(this).data('fecha');
            let acuse = $(this).data('acuse');

            $('#detalleSubmoduloModalLabel').text(titulo);
            $('#modalDescripcion').text(descripcion ? descripcion : 'No hay descripción.');
            $('#modalEstatus').text(estatus);
            $('#modalFecha').text(fecha);

            if (acuse) {
                $('#modalAcuse').attr('href', acuse);
                $('#acuseContainer').show();
            } else {
                $('#acuseContainer').hide();
            }

            console.log("Modal abierto para: " + titulo);
            $('#detalleSubmoduloModal').modal('show');
        });

        // Se vinculan los botones de cierre únicamente dentro del modal
        $('#detalleSubmoduloModal .btn-close, #detalleSubmoduloModal .btn-secondary').on('click', function() {
            $('#detalleSubmoduloModal').modal('hide');
        });
    });
</script>
@stop
