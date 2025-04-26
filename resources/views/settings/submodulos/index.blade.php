@extends('layouts.app')

@section('title', 'TI-UTM - Submódulos')

@section('content')
<!-- Fila para el botón Regresar, alineado a la derecha -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url('/settings') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<!-- Fila principal con la tarjeta de Submódulos -->
<div class="row">
    <div class="col-md-12">
        <!-- Tarjeta principal -->
        <div class="card" style="border-radius: 8px; overflow: hidden;">
            <!-- Cabecera con fondo azul (#1976d2) y texto blanco -->
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #1976d2;">
                <h3 class="card-title text-white mb-0">Submódulos Registrados</h3>
                <!-- Botón "Crear Nuevo Submódulo" -->
                <a href="{{ url('/settings/submodulos/create') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                    <i class="fa-solid fa-plus"></i> Crear Nuevo Submódulo
                </a>
            </div>

            <!-- Contenido de la tarjeta -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="submodulos" class="table table-bordered table-hover table-sm mb-0">
                        <!-- Encabezado de la tabla -->
                        <thead style="background-color: #1976d2; color: #fff;">
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Título</center></th>
                                <th><center>Subsección</center></th>
                                <th><center>Fecha Límite</center></th>
                                <th><center>Fecha de Creación</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <!-- Cuerpo de la tabla -->
                        <tbody style="background-color: #fff;">
                            @foreach ($submodulos as $index => $submodulo)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td style="text-align: center">{{ $submodulo->titulo }}</td>
                                    <td style="text-align: center">{{ $submodulo->subsection->nombre ?? 'Sin subsección' }}</td>
                                    <td style="text-align: center" class="{{ now()->gt($submodulo->fecha_limite) ? 'text-danger' : '' }}">
                                        {{ $submodulo->fecha_limite ? \Carbon\Carbon::parse($submodulo->fecha_limite)->format('d-m-Y') : 'Sin fecha' }}
                                    </td>
                                    <td style="text-align: center">{{ $submodulo->created_at->format('d-m-Y') }}</td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            <!-- Vista pública (show) fuera del settings -->
                                            <a href="{{ url('/submodulos/' . $submodulo->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                            <!-- Edición en el área de configuración -->
                                            <a href="{{ url('/settings/submodulos/' . $submodulo->id . '/edit') }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <!-- Botón para descargar acuse si está disponible -->
                                            @if($submodulo->acuse_pdf)
                                                <a href="{{ asset('storage/' . $submodulo->acuse_pdf) }}" class="btn btn-secondary btn-sm" target="_blank">
                                                    <i class="fa-solid fa-file-pdf"></i> Acuse
                                                </a>
                                            @endif
                                            <!-- Formulario para eliminar -->
                                            <form action="{{ url('/settings/submodulos/' . $submodulo->id) }}" method="POST" style="display:inline-block;">
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

@section('scripts')
<script>
$(document).ready(function(){
    $('#submodulos').DataTable({
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",
        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ Submódulos",
            infoEmpty: "Mostrando 0 a 0 de 0 Submódulos",
            infoFiltered: "(Filtrado de _MAX_ total Submódulos)",
            lengthMenu: "Mostrar _MENU_ Submódulos",
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
    }).buttons().container().appendTo('#submodulos_wrapper .col-md-6:eq(0)');

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
            title: '¿Estás seguro de eliminar este submódulo?',
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
