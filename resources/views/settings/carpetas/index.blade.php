@extends('layouts.app')

@section('title', 'TI-UTM - Carpetas')

@section('content')
<!-- Fila para el botón Regresar, alineado a la derecha -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url('/settings') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<!-- Fila principal con la tarjeta de Carpetas -->
<div class="row">
    <div class="col-md-12">
        <!-- Tarjeta principal -->
        <div class="card" style="border-radius: 8px; overflow: hidden;">
            <!-- Cabecera con fondo azul (#1976d2) y texto blanco -->
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #1976d2;">
                <h3 class="card-title text-white mb-0">Carpetas Registradas</h3>
                <!-- Botón "Crear Nueva Carpeta" -->
                <a href="{{ route('carpetas.create') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                    <i class="fa-solid fa-plus"></i> Crear Nueva Carpeta
                </a>
            </div>

            <!-- Contenido de la tarjeta -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="carpetas" class="table table-bordered table-hover table-sm mb-0">
                        <!-- Encabezado de la tabla -->
                        <thead style="background-color: #1976d2; color: #fff;">
                            <tr>
                                <th>Número</th>
                                <th>Nombre</th>
                                <th>Carpeta Padre</th>
                                <th>Fecha de Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <!-- Cuerpo de la tabla -->
                        <tbody style="background-color: #fff;">
                            @foreach ($carpetas as $index => $carpeta)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $carpeta->nombre }}</td>
                                    <td>
                                        <!-- Si tiene carpeta padre, muestra su nombre -->
                                        @if($carpeta->parent)
                                            {{ $carpeta->parent->nombre }}
                                        @else
                                            Ninguna
                                        @endif
                                    </td>
                                    <td>{{ $carpeta->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Mostrar detalle de la carpeta -->
                                            <a href="{{ route('carpetas.show', $carpeta->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>

                                            <!-- Editar carpeta -->
                                            <a href="{{ route('carpetas.edit', $carpeta->id) }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>

                                            <!-- Subir archivos -->
                                            <form action="{{ route('carpetas.upload', $carpeta->id) }}" method="POST" enctype="multipart/form-data" style="display: inline;">
                                                @csrf
                                                <input type="file" name="archivo" id="archivo_{{ $carpeta->id }}" style="display: none;" onchange="this.form.submit()">
                                                <label for="archivo_{{ $carpeta->id }}" class="btn btn-warning btn-sm mb-0">
                                                    <i class="bi bi-file-earmark-arrow-up"></i>
                                                </label>
                                            </form>

                                            <!-- Eliminar carpeta -->
                                            <form action="{{ route('carpetas.destroy', $carpeta->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- .table-responsive -->
            </div> <!-- .card-body -->
        </div> <!-- .card -->
    </div> <!-- .col-md-12 -->
</div> <!-- .row -->
@stop

@section('css')
<style>
    /* Elimina franjas si se aplicara 'table-striped' en otro lado */
    .table.table-striped tbody tr:nth-of-type(odd),
    .table.table-striped tbody tr:nth-of-type(even) {
        background-color: #fff !important;
    }
    /* Centrado y alineación vertical en la tabla */
    .table th, .table td {
        text-align: center;
        vertical-align: middle;
    }
    /* Redondeo de la cabecera de la tarjeta */
    .card-header {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    /* Estilos para controles de DataTables */
    .dataTables_length label,
    .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
    }
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
    // Inicialización de DataTables para la tabla de carpetas
    $('#carpetas').DataTable({
        order: [[0, 'desc']],
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",
        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ Carpetas",
            infoEmpty: "Mostrando 0 a 0 de 0 Carpetas",
            infoFiltered: "(Filtrado de _MAX_ total Carpetas)",
            lengthMenu: "Mostrar _MENU_ Carpetas",
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
    }).buttons().container().appendTo('#carpetas_wrapper .col-md-6:eq(0)');

    // Mensaje de éxito con SweetAlert
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
            title: '¿Estás seguro de eliminar esta carpeta?',
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

