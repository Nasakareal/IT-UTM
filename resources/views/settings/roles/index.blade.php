@extends('layouts.app')

@section('title', 'TI-UTM - Roles')

@section('content_header')
    <h1>Listado de Roles</h1>
@stop

@section('content')
<!-- Fila para el botón Regresar, alineado a la derecha -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url('/settings') }}" 
           class="btn btn-sm" 
           style="background-color: #FFFFFF; color: #000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<!-- Fila principal con la tarjeta de Roles -->
<div class="row">
    <div class="col-md-12">
        <!-- Tarjeta principal -->
        <div class="card" style="border-radius: 8px; overflow: hidden;">
            <!-- Cabecera con fondo azul (#1976d2) y texto blanco -->
            <div class="card-header d-flex justify-content-between align-items-center" 
                 style="background-color: #1976d2;">
                <h3 class="card-title text-white mb-0">Roles Registrados</h3>
                <!-- Botón "Crear Nuevo Rol" -->
                <a href="{{ url('/settings/roles/create') }}" 
                   class="btn btn-sm" 
                   style="background-color: #FFFFFF; color: #000;">
                    <i class="fa-solid fa-plus"></i> Crear Nuevo Rol
                </a>
            </div>

            <!-- Contenido de la tarjeta -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="roles" class="table table-bordered table-hover table-sm mb-0">
                        <!-- Encabezado de la tabla con el mismo color azul y texto blanco -->
                        <thead style="background-color: #1976d2; color: #fff;">
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Nombre del Rol</center></th>
                                <th><center>Fecha de Creación</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <!-- Cuerpo de la tabla en blanco -->
                        <tbody style="background-color: #fff;">
                            @foreach ($roles as $index => $rol)
                                <tr>
                                    <td><center>{{ $index + 1 }}</center></td>
                                    <td><center>{{ $rol->name }}</center></td>
                                    <td><center>{{ $rol->created_at->format('d-m-Y') }}</center></td>
                                    <td style="text-align: center;">
                                        <div class="btn-group" role="group">
                                            <a href="{{ url('/settings/roles/' . $rol->id) }}" 
                                               class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i> Ver
                                            </a>
                                            <a href="{{ url('/settings/roles/' . $rol->id . '/edit') }}" 
                                               class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i> Editar
                                            </a>
                                            <a href="{{ url('/settings/roles/' . $rol->id . '/permissions') }}" 
                                               class="btn btn-warning btn-sm">
                                                <i class="fa-solid fa-key"></i> Permisos
                                            </a>
                                            <form action="{{ url('/settings/roles/' . $rol->id) }}"
                                                  method="POST" class="d-inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm delete-btn">
                                                    <i class="fa-regular fa-trash-can"></i> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- table-responsive -->
            </div> <!-- card-body -->
        </div> <!-- card -->
    </div> <!-- col-md-12 -->
</div> <!-- row -->
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

    // Personalizamos el "dom" de DataTables para ubicar 
    // "Mostrar X Roles" y "Buscador" en la misma fila, con un estilo limpio
    $('#roles').DataTable({
        // DOM personalizado: 
        // - Fila superior con [Botones, Length] en la izquierda y [Filter] en la derecha
        // - Luego la tabla (t)
        // - Abajo, [info] y [paginación]
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
