@extends('layouts.app')

@section('title', 'TI-UTM - Subsecciones')

@section('content')
<!-- Botón para regresar -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url()->previous() }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<!-- Tarjeta principal para Subsecciones -->
<div class="row">
    <div class="col-md-12">
        <div class="card" style="border-radius: 8px; overflow: hidden;">
            <!-- Cabecera -->
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #1976d2;">
                <h3 class="card-title text-white mb-0">Subsecciones Registradas</h3>
                <a href="{{ route('subsections.create') }}" class="btn btn-sm" style="background-color: #FFFFFF; color: #000;">
                    <i class="fa-solid fa-plus"></i> Crear Nueva Subsección
                </a>
            </div>
            <!-- Cuerpo de la tarjeta -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="subsections" class="table table-bordered table-hover table-sm mb-0">
                        <thead style="background-color: #1976d2; color: #fff;">
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Módulo</center></th>
                                <th><center>Subsección Padre</center></th>
                                <th><center>Fecha de Creación</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody style="background-color: #fff;">
                            @foreach ($subsections as $index => $subsection)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td style="text-align: center">{{ $subsection->nombre }}</td>
                                    <td style="text-align: center">{{ $subsection->modulo->titulo ?? 'N/A' }}</td>
                                    <td style="text-align: center">
                                        {{ $subsection->parent ? $subsection->parent->nombre : 'N/A' }}
                                    </td>
                                    <td style="text-align: center">{{ $subsection->created_at->format('d-m-Y') }}</td>
                                    <td style="text-align: center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('subsections.show', $subsection->id) }}" class="btn btn-info btn-sm">
                                                <i class="fa-regular fa-eye"></i>
                                            </a>
                                            <a href="{{ route('subsections.edit', $subsection->id) }}" class="btn btn-success btn-sm">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                            <form action="{{ route('subsections.destroy', $subsection->id) }}" method="POST" style="display:inline-block;">
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
    </div>
</div>
@stop

@section('css')
<style>
    .table.table-striped tbody tr:nth-of-type(odd),
    .table.table-striped tbody tr:nth-of-type(even) {
        background-color: #fff !important;
    }
    .table th, .table td {
        text-align: center;
        vertical-align: middle;
    }
    .card-header {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
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
    $('#subsections').DataTable({
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",
        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ Subsecciones",
            infoEmpty: "Mostrando 0 a 0 de 0 Subsecciones",
            infoFiltered: "(Filtrado de _MAX_ total Subsecciones)",
            lengthMenu: "Mostrar _MENU_ Subsecciones",
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
    }).buttons().container().appendTo('#subsections_wrapper .col-md-6:eq(0)');

    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 15000
        });
    @endif

    $(document).on('click', '.delete-btn', function (e) {
        e.preventDefault();
        let form = $(this).closest('form');
        Swal.fire({
            title: '¿Estás seguro de eliminar esta subsección?',
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
