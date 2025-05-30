@extends('layouts.app')

@section('title', 'TI-UTM - Documentos de Profesores')

@section('content')
<!-- Botón Regresar -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url('/settings') }}" 
           class="btn btn-sm" 
           style="background-color: #FFFFFF; color: #000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<!-- Tabla de Profesores -->
<div class="row">
    <div class="col-md-12">
        <div class="card" style="border-radius: 8px; overflow: hidden;">
            <div class="card-header d-flex justify-content-between align-items-center" 
                 style="background-color: #3F51B5;">
                <h3 class="card-title text-white mb-0">Profesores con Documentos Académicos</h3>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="profesores" class="table table-bordered table-hover table-sm mb-0">
                        <thead style="background-color: #3F51B5; color: #fff;">
                            <tr>
                                <th><center>N°</center></th>
                                <th><center>Nombre</center></th>
                                <th><center>Correo</center></th>
                                <th><center>Área</center></th>
                                <th><center>Acciones</center></th>
                            </tr>
                        </thead>
                        <tbody style="background-color: #fff;">
                            @foreach ($profesores as $index => $profesor)
                                <tr>
                                    <td style="text-align: center">{{ $index + 1 }}</td>
                                    <td style="text-align: center">{{ $profesor->nombres }}</td>
                                    <td style="text-align: center">{{ $profesor->correo_institucional }}</td>
                                    <td style="text-align: center">{{ $profesor->area ?? '-' }}</td>
                                    <td style="text-align: center">
                                        <a href="{{ route('documentos-profesores.show', $profesor->id) }}" 
                                           class="btn btn-sm btn-info">
                                            <i class="fa-regular fa-eye"></i> Ver Documentos
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- table-responsive -->
            </div> <!-- card-body -->
        </div> <!-- card -->
    </div>
</div>
@stop

@section('scripts')
<script>
$(document).ready(function(){
    $('#profesores').DataTable({
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",

        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ Profesores",
            infoEmpty: "Mostrando 0 a 0 de 0 Profesores",
            infoFiltered: "(Filtrado de _MAX_ total Profesores)",
            lengthMenu: "Mostrar _MENU_ Profesores",
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
