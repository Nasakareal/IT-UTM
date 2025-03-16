@extends('layouts.app')

@section('title', 'TI-UTM - Módulos')

@section('content')
<!-- Fila para el botón Regresar, alineado a la derecha -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url('/settings') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<!-- Fila principal con la tarjeta de Módulos -->
<div class="row">
    <div class="col-md-12">
        <!-- Tarjeta principal -->
        <div class="card" style="border-radius: 8px; overflow: hidden;">
            <!-- Cabecera con fondo azul (#1976d2) y texto blanco -->
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #1976d2;">
                <h3 class="card-title text-white mb-0">Módulos Registrados</h3>
                <!-- Botón "Crear Nuevo Módulo" -->
                <a href="{{ url('/settings/modulos/create') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                    <i class="fa-solid fa-plus"></i> Crear Nuevo Módulo
                </a>
            </div>

            <!-- Contenido de la tarjeta -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="modulos" class="table table-bordered table-hover table-sm mb-0">
                        <!-- Encabezado de la tabla -->
                        <thead style="background-color: #1976d2; color: #fff;">
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Título</center></th>
                                <th><center>Año</center></th>
                                <th><center>Categoría</center></th>
                                <th><center>Fecha de Creación</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <!-- Cuerpo de la tabla -->
                        <tbody style="background-color: #fff;">
                            @foreach ($modulos as $index => $modulo)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td style="text-align: center">{{ $modulo->titulo }}</td>
                                    <td style="text-align: center">{{ $modulo->anio }}</td>
                                    <td style="text-align: center">{{ $modulo->categoria }}</td>
                                    <td style="text-align: center">{{ $modulo->created_at->format('d-m-Y') }}</td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            <!-- Vista pública (show) fuera del settings -->
                                            <a href="{{ url('/modulos/' . $modulo->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                            <!-- Edición en el área de configuración -->
                                            <a href="{{ url('/settings/modulos/' . $modulo->id . '/edit') }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <!-- Formulario para eliminar -->
                                            <form action="{{ url('/settings/modulos/' . $modulo->id) }}" method="POST" style="display:inline-block;">
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
    // Inicialización de DataTables para la tabla de módulos
    $('#modulos').DataTable({
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",
        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ Módulos",
            infoEmpty: "Mostrando 0 a 0 de 0 Módulos",
            infoFiltered: "(Filtrado de _MAX_ total Módulos)",
            lengthMenu: "Mostrar _MENU_ Módulos",
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
    }).buttons().container().appendTo('#modulos_wrapper .col-md-6:eq(0)');

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
            title: '¿Estás seguro de eliminar este módulo?',
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
