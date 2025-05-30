@extends('layouts.app')

@section('title', 'TI-UTM - Documentos del Profesor')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow border-0" style="border-radius: 10px; overflow: hidden;">
            <!-- Encabezado estilizado -->
            <div class="card-header text-white" style="background-color: #1976d2;">
                <h3 class="mb-0">
                    <i class="fas fa-folder-open me-2"></i> Documentos Académicos de: {{ $profesor->nombres }}
                </h3>
            </div>

            <div class="card-body">
                @if($documentos->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover align-middle">
                            <thead style="background-color: #E3F2FD;">
                                <tr class="text-center">
                                    <th>Materia</th>
                                    <th>Grupo</th>
                                    <th>Unidad</th>
                                    <th>Tipo de Documento</th>
                                    <th>Archivo</th>
                                    <th>Acuse</th>
                                    <th>Fecha de Subida</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documentos as $doc)
                                    <tr class="text-center">
                                        <td>{{ $doc->materia }}</td>
                                        <td>{{ $doc->grupo }}</td>
                                        <td>{{ $doc->unidad }}</td>
                                        <td>{{ $doc->tipo_documento }}</td>
                                        <td>
                                            @if($doc->archivo)
                                                <a href="{{ asset('storage/' . $doc->archivo) }}"
                                                   class="btn btn-outline-primary btn-sm"
                                                   target="_blank">
                                                   <i class="fas fa-file-alt"></i> Ver
                                                </a>
                                            @else
                                                <em>—</em>
                                            @endif
                                        </td>
                                        <td>
                                            @if($doc->acuse_pdf)
                                                <a href="{{ asset('storage/' . $doc->acuse_pdf) }}"
                                                   class="btn btn-outline-secondary btn-sm"
                                                   target="_blank">
                                                   <i class="fas fa-file-pdf"></i> Acuse
                                                </a>
                                            @else
                                                <em>—</em>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($doc->created_at)->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('documentos-profesores.destroy', $doc->id) }}"
                                                  onsubmit="return confirm('¿Seguro que deseas eliminar este documento?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning text-center mt-3">
                        Este profesor no ha subido ningún documento académico.
                    </div>
                @endif

                <div class="text-center mt-4">
                    <a href="{{ route('documentos-profesores.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@stop


@section('css')
<style>
    /* Quita cualquier franja si tuvieras 'table-striped' por otro lado */
    .table.table-striped tbody tr:nth-of-type(odd),
    .table.table-striped tbody tr:nth-of-type(even) {
        background-color: #fff !important;
    }

    /* Centra texto y ajusta vertical */
    .table th, .table td {
        text-align: center;
        vertical-align: middle;
    }

    /* Redondea la cabecera de la tarjeta */
    .card-header {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    /* Estilo para la fila de controles DataTables (Mostrar X y Buscador) */
    /* Ajusta la separación y la alineación */
    .dataTables_length label,
    .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 8px; /* Separación entre texto y select/input */
        margin-bottom: 0;
    }

    /* Ajusta el select y el input del buscador */
    .dataTables_length select,
    .dataTables_filter input {
        border-radius: 4px;
        border: 1px solid #ccc;
        padding: 4px 8px;
        height: auto;
    }
</style>
@stop

@section('scripts')
<script>
$(document).ready(function(){

    $('#usuarios').DataTable({
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",

        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ Roles",
            infoEmpty: "Mostrando 0 a 0 de 0 Roles",
            infoFiltered: "(Filtrado de _MAX_ total Roles)",
            lengthMenu: "Mostrar _MENU_ Roles",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscador:",
            zeroRecords: "Sin resultados encontrados",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        buttons: [
            {
                extend: 'collection',
                text: 'Opciones',
                buttons: [
                    { extend: 'copy', text: 'Copiar' },
                    { extend: 'pdf', text: 'PDF' },
                    { extend: 'csv', text: 'CSV' },
                    { extend: 'excel', text: 'Excel' },
                    { extend: 'print', text: 'Imprimir' }
                ]
            },
            { extend: 'colvis', text: 'Visor de columnas' }
        ],
    }).buttons().container().appendTo('#roles_wrapper .col-md-6:eq(0)');

    // Mensaje de éxito (SweetAlert)
    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 15000
        });
    @endif

    // Confirmación de eliminación
    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let form = $(this).closest('form');
        Swal.fire({
            title: '¿Estás seguro de eliminar este rol?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@stop
