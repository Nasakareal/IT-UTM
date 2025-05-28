@extends('layouts.app')

@section('title', 'TI-UTM - Ver Módulo')

@section('head')
    <link rel="icon" href="{{ asset('favicons.ico') }}" type="image/x-icon">
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Encabezado del Módulo -->
        <div class="mb-4 text-center">
            <h1>{{ $modulo->titulo }}</h1>
            <p class="text-muted">{{ $modulo->categoria }} - {{ $modulo->anio }}</p>
        </div>

        @if(auth()->check() && auth()->user()->hasRole('Administrador'))
            <div class="mb-4 text-end">
                <a href="{{ route('subsections.create', ['modulo_id' => $modulo->id]) }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                    <i class="fa-solid fa-plus"></i> Crear Nueva Subsección
                </a>
            </div>
        @endif


        <!-- Iteramos subsecciones -->
        @foreach($subnivelesPrincipales as $subsec)
            <div class="mb-5">
                <h2 class="p-2 mb-3 text-white"
                    style="background-color: {{ $modulo->color ?? '#1976d2' }};">
                    {{ strtoupper($subsec->nombre) }}
                </h2>

                @if(auth()->check() && auth()->user()->hasRole('Administrador'))
                    <div class="mb-3 text-end">
                        <a href="{{ route('carpetas.create', ['subseccion_id' => $subsec->id]) }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                            <i class="fa-solid fa-folder-plus"></i> Crear Nueva Carpeta
                        </a>
                    </div>
                @endif

                @if($subsec->submodulos->count())
                    <div class="row ms-2 mb-4">
                        @php
                            $esAdmin = auth()->check() && auth()->user()->hasRole('Administrador');
                            $ahora = now();
                        @endphp

                        @foreach($subsec->submodulos as $submodulo)
                            @php
                                $archivoOficio   = $submodulo->archivos->where('nombre', 'oficio_entrega')->first();
                                $archivoPrograma = $submodulo->archivos->where('nombre', 'programa_austeridad')->first();
                                $estadoUsuario   = $submodulo->submoduloUsuarios->where('user_id', Auth::id())->first();
                                $estadoMostrar   = $estadoUsuario ? $estadoUsuario->estatus : $submodulo->estatus;
                            @endphp

                            @if($esAdmin || is_null($submodulo->fecha_apertura) || $submodulo->fecha_apertura <= $ahora)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card shadow-sm border-0"
                                        style="border-radius: 10px; overflow: hidden;">
                                        <div class="card-header text-white text-center fw-bold"
                                            style="background-color: {{ $modulo->color ?? '#009688' }};">
                                            {{ $submodulo->titulo }}
                                        </div>

                                        <div class="card-body text-center">
                                            <p class="mb-1 text-dark fw-bold">
                                                {{ $submodulo->descripcion ?? 'Sin descripción' }}
                                            </p>
                                            <p class="mb-1 text-muted">
                                                <strong>Estatus:</strong>
                                                <span class="
                                                    {{ strtolower($estadoMostrar)=='pendiente'   ? 'text-warning'
                                                      : (strtolower($estadoMostrar)=='entregado' ? 'text-success'
                                                      : 'text-danger') }}
                                                ">
                                                    {{ ucfirst($estadoMostrar) }}
                                                </span>
                                            </p>

                                            @if($submodulo->fecha_apertura)
                                                <p class="mb-1 text-muted">
                                                    <strong>Fecha Apertura:</strong>
                                                    {{ $submodulo->fecha_apertura->format('Y-m-d H:i') }}
                                                </p>
                                            @endif
                                            @if($submodulo->fecha_limite)
                                                <p class="mb-1 text-muted">
                                                    <strong>Fecha Límite:</strong>
                                                    {{ $submodulo->fecha_limite->format('Y-m-d H:i') }}
                                                </p>
                                            @endif
                                            @if($submodulo->fecha_cierre)
                                                <p class="mb-1 text-muted">
                                                    <strong>Fecha Cierre:</strong>
                                                    {{ $submodulo->fecha_cierre->format('Y-m-d H:i') }}
                                                </p>
                                            @endif

                                            @if($submodulo->documento_solicitado)
                                                <p class="mb-2">
                                                    <strong>Plantilla base:</strong>
                                                    <a href="{{ asset('storage/' . $submodulo->documento_solicitado) }}"
                                                       target="_blank">Descargar plantilla</a>
                                                </p>
                                            @endif
                                        </div>

                                        <div class="card-footer text-center bg-light">
                                            <button class="btn btn-info text-white ver-detalle-submodulo"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detalleSubmoduloModal"
                                                data-id="{{ $submodulo->id }}"
                                                data-titulo="{{ $submodulo->titulo }}"
                                                data-descripcion="{{ $submodulo->descripcion }}"
                                                data-estatus="{{ ucfirst($estadoMostrar) }}"
                                                data-fecha-apertura="{{ $submodulo->fecha_apertura? $submodulo->fecha_apertura->format('Y-m-d H:i') : '' }}"
                                                data-fecha-limite="{{ $submodulo->fecha_limite? $submodulo->fecha_limite->format('Y-m-d H:i') : '' }}"
                                                data-fecha-cierre="{{ $submodulo->fecha_cierre? $submodulo->fecha_cierre->format('Y-m-d H:i') : '' }}"
                                                data-base="{{ $submodulo->documento_solicitado? asset('storage/' . $submodulo->documento_solicitado) : '' }}"
                                                data-acuse="{{ route('submodulos.generarAcuse', $submodulo->id) }}"
                                                data-oficio="{{ $archivoOficio? asset('storage/' . $archivoOficio->ruta) : '' }}"
                                                data-programa="{{ $archivoPrograma? asset('storage/' . $archivoPrograma->ruta) : '' }}">
                                                <i class="fa fa-info-circle"></i> Detalles
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                    </div>
                @endif

                @if($subsec->carpetas->count())
                    <ul class="list-unstyled ms-4">
                        @foreach($subsec->carpetas as $carpeta)
                            @include('partials.folder_tree', ['folder' => $carpeta])
                        @endforeach
                    </ul>
                @else
                    <p class="ms-4">No hay carpetas en esta subsección.</p>
                @endif

                <!-- Tarjeta estilo Proyecto Institucional para botón Ver Documentos por Unidad -->
                @if(strtoupper($subsec->nombre) === 'GESTIÓN ACADÉMICA' && $modulo->id == 5)
                    <div class="col-md-3 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-success text-white text-center rounded-top">
                                Documentos por Unidad
                            </div>
                            <div class="card-body text-center">
                                <p class="text-muted">Accede a los formatos y documentos por unidad</p>
                                <a href="{{ route('modulo5.gestion') }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-file-alt"></i> Ver Documentos por Unidad
                                </a>
                            </div>
                        </div>
                    </div>
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
<div class="modal fade" id="detalleSubmoduloModal" tabindex="-1"
     aria-labelledby="detalleSubmoduloModalLabel" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Encabezado -->
            <div class="modal-header">
                <h5 class="modal-title" id="detalleSubmoduloModalLabel"></h5>
                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Cuerpo -->
            <div class="modal-body">
                <p><strong>Estatus:</strong> <span id="modalEstatus"></span></p>
                <p><strong>Fecha Apertura:</strong> <span id="modalFechaApertura"></span></p>
                <p><strong>Fecha Límite:</strong> <span id="modalFechaLimite"></span></p>
                <p><strong>Fecha Cierre:</strong> <span id="modalFechaCierre"></span></p>
                <p><strong>Descripción:</strong></p>
                <p id="modalDescripcion"></p>

                <!-- Enlace plantilla base -->
                <div id="plantillaContainer" style="display:none;">
                    <p><strong>Plantilla base:</strong>
                        <a id="linkPlantilla" href="#" target="_blank">Descargar plantilla</a>
                    </p>
                </div>

                <!-- Área para mostrar archivos ya subidos (descarga) -->
                <div id="archivosExistentes" style="display:none;">
                    <p>Oficio de entrega ya subido:
                        <a id="linkOficio" href="#" target="_blank">Descargar PDF</a>
                    </p>
                    <p>Programa de Austeridad ya subido:
                        <a id="linkPrograma" href="#" target="_blank">Descargar PDF</a>
                    </p>
                </div>

                <!-- FORMULARIO PARA SUBIR LOS DOS PDFs -->
                <form id="formSubirArchivos"
                      action="{{ route('submodulos.subirArchivos') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      style="display:none;">
                    @csrf
                    <input type="hidden" name="submodulo_id" id="submodulo_id">

                    <div class="mb-3">
                        <label for="oficio_entrega" class="form-label">
                            1. Oficio de entrega (PDF máx. 2Mb):
                        </label>
                        <input type="file" class="form-control" id="oficio_entrega" name="oficio_entrega" accept=".pdf">
                    </div>

                    <div class="mb-3">
                        <label for="programa_austeridad" class="form-label">
                            2. Programa de Austeridad y Ahorro (PDF máx. 12Mb):
                        </label>
                        <input type="file" class="form-control" id="programa_austeridad" name="programa_austeridad" accept=".pdf">
                    </div>

                    <!-- NUEVOS CAMPOS PARA E.FIRMA -->
                    <div class="mb-3">
                        <label for="efirma_p12" class="form-label">
                            3. Certificado e.firma (.p12):
                        </label>
                        <input type="file" class="form-control" id="efirma_p12" name="efirma_p12" accept=".p12" required>
                    </div>
                    <div class="mb-3">
                        <label for="efirma_pass" class="form-label">
                            Contraseña e.firma:
                        </label>
                        <input type="password" class="form-control" id="efirma_pass" name="efirma_pass" required>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnEnviarArchivos">
                        Enviar y Firmar
                    </button>
                </form>


                <!-- ACUSE: se muestra si existe -->
                <div class="mt-3" id="acuseContainer" style="display: none;">
                    <a id="modalAcuse" href="#" target="_blank"
                       class="btn btn-outline-secondary">
                        <i class="fa fa-file-pdf"></i> Ver Acuse
                    </a>
                </div>
            </div>

            <!-- Pie del modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@section('css')
<style>
    ul.list-unstyled { font-size: 1.1rem; }
    ul.list-unstyled li { margin-bottom: 0.5rem; }
    .folder-toggle { cursor: pointer; }
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
            let titulo      = $(this).data('titulo'),
                descripcion = $(this).data('descripcion'),
                estatus     = $(this).data('estatus'),
                fechaA      = $(this).data('fecha-apertura'),
                fechaL      = $(this).data('fecha-limite'),
                fechaC      = $(this).data('fecha-cierre'),
                base        = $(this).data('base'),
                acuse       = $(this).data('acuse'),
                oficio      = $(this).data('oficio'),
                programa    = $(this).data('programa'),
                id          = $(this).data('id');

            $('#detalleSubmoduloModalLabel').text(titulo);
            $('#modalDescripcion').text(descripcion || 'No hay descripción.');
            $('#modalEstatus').text(estatus);
            $('#modalFechaApertura').text(fechaA || 'No definida');
            $('#modalFechaLimite').text(fechaL || 'No definida');
            $('#modalFechaCierre').text(fechaC || 'No definida');
            $('#submodulo_id').val(id);

            // Plantilla base
            if (base) {
                $('#linkPlantilla').attr('href', base);
                $('#plantillaContainer').show();
            } else {
                $('#plantillaContainer').hide();
            }

            // Acuse
            if (acuse) {
                $('#modalAcuse').attr('href', acuse);
                $('#acuseContainer').show();
            } else {
                $('#acuseContainer').hide();
            }

            // Archivos entregados
            if (oficio || programa) {
                $('#formSubirArchivos').hide();
                $('#archivosExistentes').show();
                if (oficio)   $('#linkOficio').attr('href', oficio);
                if (programa) $('#linkPrograma').attr('href', programa);
            } else {
                $('#archivosExistentes').hide();
                $('#formSubirArchivos').show();
            }

            $('#detalleSubmoduloModal').modal('show');
        });

        $('#detalleSubmoduloModal .btn-close, #detalleSubmoduloModal .btn-secondary')
          .on('click', function() {
            $('#detalleSubmoduloModal').modal('hide');
        });

        $('#formSubirArchivos').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // refrescar modal para mostrar acuse y ocultar form
                        $('.ver-detalle-submodulo[data-id="'+ response.submodulo_id +'"]').click();
                        alert('Archivos subidos correctamente.');
                    } else {
                        alert('Error al subir archivos.');
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseText);
                }
            });
        });
    });
</script>
@endsection
