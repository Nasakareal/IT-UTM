@extends('layouts.app')

@section('title', 'TI-UTM - Calificaciones')

@section('content')
<!-- Botón regresar -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url('/settings') }}"
           class="btn btn-sm"
           style="background-color:#FFFFFF;color:#000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

@php
    // 1) Orden deseado de columnas (si un tipo no existe, se omite)
    $ordenDeseado = [
        // Tutorías
        'Presentación del Tutor',
        '1er Tutoría Grupal',
        '2da Tutoría Grupal',
        '3er Tutoría Grupal',
        'Registro de Proyecto Institucional',
        'Informe Parcial',
        'Informe Global',
        // Gestión
        'Presentación de la Asignatura',
        'Planeación didáctica',
        'Reporte de Evaluación Continua por Unidad de Aprendizaje (SIGO)',
        'Informe de Estudiantes No Acreditados',
        'Control de Asesorías',
        'Seguimiento de la Planeación',
    ];

    // 2) Construir el conjunto de tipos presentes en los datos
    $tiposPresentes = [];
    foreach ($resumenPorDocumento as $prof) {
        foreach ($prof['docs'] as $d) {
            if (!empty($d['tipo'])) $tiposPresentes[$d['tipo']] = true;
        }
    }
    $tiposPresentes = array_keys($tiposPresentes);

    // 3) Filtrar y ordenar según $ordenDeseado; agregar extras al final
    $tiposOrdenados = array_values(array_intersect($ordenDeseado, $tiposPresentes));
    $extras = array_values(array_diff($tiposPresentes, $tiposOrdenados));
    sort($extras, SORT_NATURAL | SORT_FLAG_CASE);
    $columnasTipos = array_merge($tiposOrdenados, $extras);

    // Helper de formato
    $fmt = function($v){ return is_null($v) ? '—' : number_format((float)$v, 2); };
@endphp

<!-- Tabla principal -->
<div class="row">
    <div class="col-md-12">
        <div class="card" style="border-radius:8px; overflow:hidden;">
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#1976d2;">
                <h3 class="card-title text-white mb-0">Promedios por Tipo de Documento</h3>
                <span class="badge bg-light text-dark">
                    Total profesores: {{ count($resumenPorDocumento ?? []) }}
                </span>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="tabla-promedios" class="table table-bordered table-hover table-sm mb-0">
                        <thead style="background-color:#1976d2; color:#fff;">
                            <tr>
                                <th><center>Número</center></th>
                                <th><center>Profesor</center></th>
                                @foreach ($columnasTipos as $tipo)
                                    <th><center>{{ $tipo }}</center></th>
                                @endforeach
                                <th class="table-secondary"><center>Promedio General</center></th>
                            </tr>
                        </thead>
                        <tbody style="background-color:#fff;">
                            @foreach ($resumenPorDocumento as $i => $prof)
                                @php
                                    // Mapa rápido tipo -> promedio (solo necesitamos "promedio")
                                    $map = [];
                                    foreach ($prof['docs'] as $d) {
                                        $map[$d['tipo']] = $d['promedio'] ?? null;
                                    }
                                    // Promedio general: promedio simple de los promedios disponibles
                                    $vals = [];
                                    foreach ($columnasTipos as $t) {
                                        if (array_key_exists($t, $map) && !is_null($map[$t])) {
                                            $vals[] = (float)$map[$t];
                                        }
                                    }
                                    $promGeneral = count($vals) ? round(array_sum($vals)/count($vals), 2) : null;
                                @endphp
                                <tr>
                                    <td style="text-align:center;">{{ $i + 1 }}</td>
                                    <td style="text-align:center;">{{ $prof['nombre'] }}</td>

                                    @foreach ($columnasTipos as $tipo)
                                        <td style="text-align:center;">
                                            {{ $fmt($map[$tipo] ?? null) }}
                                        </td>
                                    @endforeach

                                    <td class="table-secondary" style="text-align:center;">
                                        {{ $fmt($promGeneral) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> <!-- /table-responsive -->
            </div> <!-- /card-body -->
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .table th, .table td { text-align:center; vertical-align:middle; }
    .card-header { border-top-left-radius:8px; border-top-right-radius:8px; }
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function(){
    $('#tabla-promedios').DataTable({
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",
        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ profesores",
            infoEmpty: "Mostrando 0 a 0 de 0 profesores",
            infoFiltered: "(Filtrado de _MAX_ total profesores)",
            lengthMenu: "Mostrar _MENU_ profesores",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscador:",
            zeroRecords: "Sin resultados encontrados",
            paginate: { first:"Primero", last:"Último", next:"Siguiente", previous:"Anterior" }
        },
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        buttons: [
            {
                extend: 'collection',
                text: 'Opciones',
                buttons: [
                    { extend: 'copy',  text: 'Copiar'   },
                    { extend: 'pdf',   text: 'PDF'      },
                    { extend: 'csv',   text: 'CSV'      },
                    { extend: 'excel', text: 'Excel'    },
                    { extend: 'print', text: 'Imprimir' }
                ]
            },
            { extend: 'colvis', text: 'Visor de columnas' }
        ]
    });
});
</script>
@endsection
