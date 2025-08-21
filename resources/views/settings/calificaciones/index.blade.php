@extends('layouts.app')

@section('title', 'TI-UTM - Calificaciones')

@section('content')
<!-- Botón regresar -->
<div class="row mb-2">
    <div class="col-md-12 text-right">
        <a href="{{ url('/settings') }}" class="btn btn-sm" style="background-color:#FFFFFF;color:#000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card" style="border-radius:8px; overflow:hidden;">
            <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#1976d2;">
                <h3 class="card-title text-white mb-0">Resumen de calificaciones por profesor</h3>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="calificaciones" class="table table-bordered table-hover table-sm mb-0">
                        <thead style="background-color:#1976d2; color:#fff;">
                            <tr>
                                <th><center>#</center></th>
                                <th><center>Profesor</center></th>
                                <th><center>Teacher ID</center></th>
                                <th><center>Esperados</center></th>
                                <th><center>Entregados</center></th>
                                <th><center>Calificados</center></th>
                                <th><center>Promedio</center></th>
                                <th><center>Cumplimiento</center></th>
                            </tr>
                        </thead>
                        <tbody style="background-color:#fff;">
                            @foreach ($resumen as $idx => $r)
                                @php
                                    $esperados    = (int)($r['esperados'] ?? 0);
                                    $entregados   = (int)($r['entregados'] ?? 0);
                                    $calificados  = (int)($r['calificados'] ?? 0);
                                    $promedio     = $r['promedio'] ?? null;
                                    $cumplimiento = $esperados > 0 ? round(($entregados / $esperados) * 100, 0) : null;
                                @endphp
                                <tr>
                                    <td style="text-align:center">{{ $idx + 1 }}</td>
                                    <td style="text-align:center">{{ $r['nombre'] }}</td>
                                    <td style="text-align:center">{{ $r['teacher_id'] }}</td>
                                    <td style="text-align:center">{{ $esperados }}</td>
                                    <td style="text-align:center">{{ $entregados }}</td>
                                    <td style="text-align:center">{{ $calificados }}</td>
                                    <td style="text-align:center">
                                        {{ is_null($promedio) ? '—' : number_format($promedio, 2) }}
                                    </td>
                                    <td style="text-align:center">
                                        {{ is_null($cumplimiento) ? '—' : ($cumplimiento.'%') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" style="text-align:right">Totales:</th>
                                <th style="text-align:center"></th> {{-- Esperados total --}}
                                <th style="text-align:center"></th> {{-- Entregados total --}}
                                <th style="text-align:center"></th> {{-- Calificados total --}}
                                <th style="text-align:center"></th> {{-- Promedio general promedio (opcional) --}}
                                <th style="text-align:center"></th> {{-- Cumplimiento promedio (opcional) --}}
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div> <!-- card -->
    </div>
</div>
@endsection

@section('css')
<style>
    /* Quita franjas si se agrega table-striped por error */
    .table.table-striped tbody tr:nth-of-type(odd),
    .table.table-striped tbody tr:nth-of-type(even) {
        background-color: #fff !important;
    }
    .table th, .table td { text-align:center; vertical-align:middle; }
    .card-header { border-top-left-radius:8px; border-top-right-radius:8px; }
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    const table = $('#calificaciones').DataTable({
        dom: "<'row p-3'<'col-md-6 d-flex align-items-center'B l><'col-md-6 text-right'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3'<'col-sm-5'i><'col-sm-7'p>>",
        pageLength: 10,
        language: {
            emptyTable: "No hay información",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ en total)",
            lengthMenu: "Mostrar _MENU_",
            loadingRecords: "Cargando...",
            processing: "Procesando...",
            search: "Buscador:",
            zeroRecords: "Sin resultados",
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
                    { extend: 'copy',  text: 'Copiar'  },
                    { extend: 'pdf',   text: 'PDF'     },
                    { extend: 'csv',   text: 'CSV'     },
                    { extend: 'excel', text: 'Excel'   },
                    { extend: 'print', text: 'Imprimir'}
                ]
            },
            { extend: 'colvis', text: 'Visor de columnas' }
        ],
        footerCallback: function (row, data, start, end, display) {
            const api = this.api();
            const toInt = v => typeof v === 'string' ? (v.replace(/[^\d.-]/g,'')*1||0) : (typeof v === 'number' ? v : 0);

            // indices de columnas: 3=Esperados, 4=Entregados, 5=Calificados
            const sumCol = idx => api.column(idx, {page:'current'}).data().reduce((a,b)=>a+toInt(b),0);

            const totalEsperados   = sumCol(3);
            const totalEntregados  = sumCol(4);
            const totalCalificados = sumCol(5);

            $(api.column(3).footer()).html(totalEsperados);
            $(api.column(4).footer()).html(totalEntregados);
            $(api.column(5).footer()).html(totalCalificados);

            // Opcional: promedio de “Promedio (general)” y cumplimiento promedio
            // 6=Promedio (general), 7=Cumplimiento
            const parseProm = v => {
                if (typeof v !== 'string') return (typeof v === 'number') ? v : NaN;
                const num = parseFloat(v.replace(',', '.'));
                return isNaN(num) ? NaN : num;
            };
            const promVals = api.column(6, {page:'current'}).data().toArray().map(parseProm);
            const promValidos = promVals.filter(n => !isNaN(n));
            const promProm = promValidos.length ? (promValidos.reduce((a,b)=>a+b,0) / promValidos.length) : NaN;
            $(api.column(6).footer()).html(isNaN(promProm) ? '—' : promProm.toFixed(2));

            const parsePct = v => {
                if (typeof v !== 'string') return NaN;
                const n = parseFloat(v.replace('%','').replace(',', '.'));
                return isNaN(n) ? NaN : n;
            };
            const pctVals = api.column(7, {page:'current'}).data().toArray().map(parsePct);
            const pctValidos = pctVals.filter(n => !isNaN(n));
            const pctProm = pctValidos.length ? (pctValidos.reduce((a,b)=>a+b,0) / pctValidos.length) : NaN;
            $(api.column(7).footer()).html(isNaN(pctProm) ? '—' : (Math.round(pctProm) + '%'));
        }
    });

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
});
</script>
@endsection
