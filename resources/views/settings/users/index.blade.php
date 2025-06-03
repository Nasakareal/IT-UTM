@extends('layouts.app')

@section('title', 'TI-UTM - Usuarios')

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

<!-- Fila principal con la tarjeta de Usuarios -->
<div class="row">
    <div class="col-md-12">
        <!-- Tarjeta principal -->
        <div class="card" style="border-radius: 8px; overflow: hidden;">
            <!-- Cabecera con fondo azul (#1976d2) y texto blanco -->
            <div class="card-header d-flex justify-content-between align-items-center" 
                 style="background-color: #1976d2;">
                <h3 class="card-title text-white mb-0">Usuarios Registrados</h3>
                <!-- Botón "Crear Nuevo Usuaro" -->
                <a href="{{ url('/settings/users/create') }}" 
                   class="btn btn-sm" 
                   style="background-color: #FFFFFF; color: #000;">
                    <i class="fa-solid fa-plus"></i> Crear Nuevo Usuario
                </a>
            </div>

            <!-- Contenido de la tarjeta -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="users" class="table table-bordered table-hover table-sm mb-0">
                        <!-- Encabezado de la tabla con el mismo color azul y texto blanco -->
                        <thead style="background-color: #1976d2; color: #fff;">
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Nombres del Usuario</center></th>
                                <th><center>Rol</center></th>
                                <th><center>Correo Institucional</center></th>
                                <th><center>Área</center></th>
                                <th><center>Estado</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <!-- Cuerpo de la tabla en blanco -->
                        <tbody style="background-color: #fff;">
                            @foreach ($users as $index => $user)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td style="text-align: center">{{ $user->nombres }}</td>
                                    <td style="text-align: center">{{ $user->roles->pluck('name')->join(', ') }}</td>
                                    <td style="text-align: center">{{ $user->correo_institucional }}</td>
                                    <td style="text-align: center">
                                        @php
                                            $areas = explode(',', $user->area);
                                        @endphp
                                        @if (count($areas) > 2)
                                            {{ implode(', ', array_slice($areas, 0, 2)) . '...' }}
                                        @else
                                            {{ $user->area }}
                                        @endif
                                    </td>

                                    <td style="text-align: center">{{ $user->estado }}</td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ url('/settings/users/' . $user->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                            <a href="{{ url('/settings/users/' . $user->id . '/edit') }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            {{-- Formulario de Eliminar --}}
                                            <form action="{{ url('/settings/users/' . $user->id) }}" 
                                                  method="POST" style="display:inline-block;">
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
    $('#users').DataTable({
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",

        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ Usuarios",
            infoEmpty: "Mostrando 0 a 0 de 0 Usuarios",
            infoFiltered: "(Filtrado de _MAX_ total Usuarios)",
            lengthMenu: "Mostrar _MENU_ Usuarios",
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
