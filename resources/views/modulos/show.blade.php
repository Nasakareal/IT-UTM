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
            <div id="subsections-sortable">
        @endif

        @if(auth()->check() && auth()->user()->hasRole('Administrador'))
            <div class="mb-4 text-end">
                <a href="{{ route('subsections.create', ['modulo_id' => $modulo->id]) }}"
                   class="btn btn-sm" style="background-color:#FFFFFF;color:#000;">
                    <i class="fa-solid fa-plus"></i> Crear Nueva Subsección
                </a>
            </div>
        @endif

        <!-- Iteramos subsecciones -->
        @foreach($subnivelesPrincipales as $subsec)
            {{--  ⬇️  se añade data-id para ordenar sin romper nada  --}}
            <div class="mb-5 subsection-item" data-id="{{ $subsec->id }}">
                <h3 class="p-2 mb-3 text-white"
                    style="background-color:{{ $modulo->color ?? '#1976d2' }};">
                    {{ strtoupper($subsec->nombre) }}
                </h3>

                @if(auth()->check() && auth()->user()->hasRole('Administrador'))
                    <div class="mb-3 text-end">
                        <a href="{{ route('carpetas.create', ['subseccion_id' => $subsec->id]) }}"
                           class="btn btn-sm" style="background-color:#FFFFFF;color:#000;">
                            <i class="fa-solid fa-folder-plus"></i> Crear Nueva Carpeta
                        </a>
                    </div>
                @endif

                @if($subsec->submodulos->count())
                    <div class="row ms-2 mb-4">
                        @php
                            $esAdmin = auth()->check() && auth()->user()->hasRole('Administrador');
                            $ahora   = now();
                        @endphp

                        @foreach($subsec->submodulos as $submodulo)
                            @php
                                $archivoOficio = $submodulo->archivos->where('nombre','oficio_entrega')->sortByDesc('id')->first();

                                $archivoPrograma = $submodulo->archivos->where('nombre','programa_austeridad')->first();
                                $estadoUsuario   = $submodulo->submoduloUsuarios->where('user_id',Auth::id())->first();
                                $estadoMostrar   = $estadoUsuario ? $estadoUsuario->estatus : $submodulo->estatus;
                            @endphp

                            @if($esAdmin || is_null($submodulo->fecha_apertura) || $submodulo->fecha_apertura <= $ahora)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card shadow-sm border-0" style="border-radius:10px;overflow:hidden;">
                                        <div class="card-header text-white text-center fw-bold"
                                             style="background-color:{{ $modulo->color ?? '#009688' }};">
                                            {{ $submodulo->titulo }}
                                        </div>

                                        <div class="card-body text-center">
                                            <p class="mb-1 text-dark fw-bold">
                                                {{ $submodulo->descripcion ?? 'Sin descripción' }}
                                            </p>
                                            <p class="mb-1 text-muted">
                                                <strong>Estatus:</strong>
                                                <span class="{{ strtolower($estadoMostrar)=='pendiente' ? 'text-warning'
                                                                    : (strtolower($estadoMostrar)=='entregado' ? 'text-success'
                                                                    : 'text-danger') }}">
                                                    {{ ucfirst($estadoMostrar) }}
                                                </span>
                                            </p>

                                            @if($submodulo->fecha_apertura)
                                                <p class="mb-1 text-muted"><strong>Fecha Apertura:</strong>
                                                    {{ $submodulo->fecha_apertura->format('Y-m-d H:i') }}</p>
                                            @endif
                                            @if($submodulo->fecha_limite)
                                                <p class="mb-1 text-muted"><strong>Fecha Límite:</strong>
                                                    {{ $submodulo->fecha_limite->format('Y-m-d H:i') }}</p>
                                            @endif
                                            @if($submodulo->fecha_cierre)
                                                <p class="mb-1 text-muted"><strong>Fecha Cierre:</strong>
                                                    {{ $submodulo->fecha_cierre->format('Y-m-d H:i') }}</p>
                                            @endif

                                            @if($submodulo->documento_solicitado)
                                                <p class="mb-2"><strong>Plantilla base:</strong>
                                                    <a href="{{ asset('storage/'.$submodulo->documento_solicitado) }}"
                                                       target="_blank">Descargar plantilla</a>
                                                </p>
                                            @endif
                                        </div>

                                        <div class="card-footer text-center bg-light">
                                            <button class="btn btn-info text-white ver-detalle-submodulo"
                                                data-bs-toggle="modal" data-bs-target="#detalleSubmoduloModal"
                                                data-id="{{ $submodulo->id }}"
                                                data-titulo="{{ $submodulo->titulo }}"
                                                data-descripcion="{{ $submodulo->descripcion }}"
                                                data-estatus="{{ ucfirst($estadoMostrar) }}"
                                                data-fecha-apertura="{{ $submodulo->fecha_apertura? $submodulo->fecha_apertura->format('Y-m-d H:i') : '' }}"
                                                data-fecha-limite="{{ $submodulo->fecha_limite? $submodulo->fecha_limite->format('Y-m-d H:i') : '' }}"
                                                data-fecha-cierre="{{ $submodulo->fecha_cierre? $submodulo->fecha_cierre->format('Y-m-d H:i') : '' }}"
                                                data-base="{{ $submodulo->documento_solicitado ? asset('storage/'.$submodulo->documento_solicitado) : '' }}"
                                                {{-- ⬇️ solo manda la URL si el oficio tiene firma --}}
                                                data-acuse="{{ ($archivoOficio && $archivoOficio->firma_sat) ? route('submodulos.generarAcuse',$submodulo->id) : '' }}"
                                                data-oficio="{{ $archivoOficio ? asset('storage/'.$archivoOficio->ruta) : '' }}"
                                                data-programa="{{ $archivoPrograma ? asset('storage/'.$archivoPrograma->ruta) : '' }}">
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
                            @include('partials.folder_tree',['folder'=>$carpeta])
                        @endforeach
                    </ul>
                @else
                    <p class="ms-4">No hay carpetas en esta subsección.</p>
                @endif

                @if(strtoupper($subsec->nombre)==='GESTIÓN ACADÉMICA' && $modulo->id==5)
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

        @if(auth()->check() && auth()->user()->hasRole('Administrador'))
            </div> {{-- /#subsections-sortable --}}
        @endif

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
                            1. Formato de entrega (PDF máx. 8Mb):
                        </label>
                        <input type="file" class="form-control" id="oficio_entrega" name="oficio_entrega" accept=".pdf,.doc,.docx,.xls,.xlsxm .xml" required>
                    </div>

                    <!-- CAMPOS PARA E.FIRMA -->
                    <div class="mb-3">
                        <label for="efirma_p12" class="form-label">
                            2. Certificado e.firma (.p12):
                        </label>
                        <input type="file"
                           id="efirma_p12"
                           name="efirma_p12"
                           class="form-control"
                           accept=".p12,.pfx"
                           required>

                    </div>
                    <div class="mb-3">
                        <label for="efirma_pass" class="form-label">
                            Contraseña e.firma:
                        </label>
                        <input type="password" class="form-control" id="efirma_pass" name="efirma_pass" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="accion" value="solo_enviar" class="btn btn-secondary">
                            Solo Enviar
                        </button>

                        <button type="submit" name="accion" value="firmar" class="btn btn-primary">
                            Enviar y Firmar
                        </button>
                    </div>
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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });


    $(document).ready(function() {
        let accion = null;

        // Capturar botón presionado y manejar required dinámico
        $('#formSubirArchivos button[type="submit"]').on('click', function() {
            accion = $(this).val(); // 'solo_enviar' o 'firmar'

            if (accion === 'firmar') {
                $('#efirma_p12').attr('required', true);
                $('#efirma_pass').attr('required', true);
            } else {
                $('#efirma_p12').removeAttr('required');
                $('#efirma_pass').removeAttr('required');
            }
        });

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
            if (oficio) {
                $('#formSubirArchivos').hide();
                $('#archivosExistentes').show();
                $('#linkOficio').attr('href', oficio);
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

            const form = this;
            const formData = new FormData(form);

            // Validar archivo oficio_entrega por extensión
            const file = $('#oficio_entrega')[0].files[0];
            if (file) {
                const ext = file.name.split('.').pop().toLowerCase();
                const extensionesPermitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'xml'];

                if (!extensionesPermitidas.includes(ext)) {
                    alert('El archivo debe ser PDF, DOC, DOCX, XLS, XLSX o XML.');
                    return;
                }
            }

            // Validar campos de e.firma si se va a firmar
            if (accion === 'firmar') {
                const p12 = $('#efirma_p12')[0].files[0];
                const pass = $('#efirma_pass').val();

                if (!p12) {
                    alert('Debes seleccionar un archivo .p12 para firmar.');
                    return;
                }

                if (!pass || pass.trim() === '') {
                    alert('Debes escribir la contraseña de la e.firma.');
                    return;
                }
            }

            formData.append('accion', accion);

            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            position: 'center',
                            icon: 'success',
                            title: 'Archivos subidos correctamente.',
                            showConfirmButton: false,
                            timer: 3000
                        });

                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al subir archivos.',
                            text: response.message || 'Intenta nuevamente.'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error inesperado',
                        text: xhr.responseText
                    });
                }
            });
        });
    });
</script>


@if(auth()->check() && auth()->user()->hasRole('Administrador'))
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(function () {
    /* ----- 1. Subsections ----- */
    $('#subsections-sortable').sortable({
        handle: 'h3',
        items : '.subsection-item',
        update() {
            const orden = [];
            $('#subsections-sortable .subsection-item').each(function(i) {
                orden.push({ id: $(this).data('id'), orden: i + 1 });
            });
            $.post('{{ route("subsections.sort") }}', { orden, _token: '{{ csrf_token() }}' });
        }
    });

    /* ----- 2. Submódulos dentro de cada subsección ----- */
    $('.row.ms-2.mb-4').each(function() {
        $(this).sortable({
            handle: '.card-header',
            items: '.col-md-6',
            update() {
                const orden = [];
                $(this).children('.col-md-6').each(function(i) {
                    orden.push({
                        id: $(this).find('.ver-detalle-submodulo').data('id'),
                        orden: i + 1
                    });
                });
                $.post('{{ route("submodulos.sort") }}', { orden, _token: '{{ csrf_token() }}' });
            }
        });
    });

    /* ----- 3. Carpetas dentro de cada subsección ----- */
    $('ul.list-unstyled.ms-4').each(function() {
        $(this).sortable({
            handle: '.folder-toggle',
            items: '.carpeta-item',
            update() {
                const orden = [];
                $(this).children('.carpeta-item').each(function(i) {
                    orden.push({ id: $(this).data('id'), orden: i + 1 });
                });
                $.post('{{ route("carpetas.sort") }}', { orden, _token: '{{ csrf_token() }}' });
            }
        });
    });
});
</script>
@endif


@endsection
