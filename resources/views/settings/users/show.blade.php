@extends('layouts.app')

@section('title', 'TI-UTM - Ver Usuario')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">Datos Registrados</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Nombre y Correo --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Nombre</label>
                                <p class="form-control-static">{{ $user->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Correo Electrónico</label>
                                <p class="form-control-static">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Área y Estado --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="area">Área</label>
                                <p class="form-control-static">{{ $user->area ?? 'No especificada' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <p class="form-control-static">{{ $user->estado }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- Rol y Foto de Perfil --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Rol</label>
                                <p class="form-control-static">{{ $user->roles->pluck('name')->join(', ') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="foto_perfil">Foto de Perfil</label>
                                @if ($user->foto_perfil)
                                    <div>
                                        <img src="{{ asset('storage/' . $user->foto_perfil) }}" alt="Foto de Perfil" style="max-width: 150px; max-height: 150px;" class="img-thumbnail">
                                    </div>
                                @else
                                    <p class="form-control-static">No tiene foto de perfil.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        {{-- Botón de regreso --}}
                        <div class="col-md-12 text-center">
                            <div class="form-group">
                                <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </div>
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

    // Personalizamos el "dom" de DataTables para ubicar 
    // "Mostrar X Usuarios" y "Buscador" en la misma fila, con un estilo limpio
    $('#usuarios').DataTable({
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
