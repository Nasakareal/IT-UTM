@extends('layouts.app')

@section('title', 'TI-UTM - Calificaciones')

@section('content')
<!-- Botón regresar + botón exportar -->
<div class="row mb-2">
    <div class="col-md-6">
        <a href="{{ url('/settings') }}" class="btn btn-sm" style="background-color:#FFFFFF;color:#000;">
            <i class="fa-solid fa-arrow-left"></i> Regresar
        </a>
    </div>
    <div class="col-md-6 text-end">
        <a href="{{ route('calificaciones.export') }}" class="btn btn-success btn-sm">
            <i class="fa-solid fa-file-download"></i> Descargar todo
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <!-- Encabezado general -->
        <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert" style="border-radius:8px;">
            <div>
                <strong>Resumen por tipo de documento ({{ $unidadHasta }})</strong><br>
                <small>
                    Las tutorías se habilitan por tercios del cuatrimestre (1/3, 2/3, 3/3).
                    La “Unidad hasta” mostrada en cada tarjeta es la mediana de la unidad vigente entre las materias del profesor.
                </small>
            </div>
            <div>
                <span class="badge bg-primary">Total profesores: {{ count($resumenPorDocumento ?? []) }}</span>
            </div>
        </div>

        @forelse ($resumenPorDocumento as $prof)
            @php
                // Indexar docs por tipo para acceso rápido
                $map = [];
                $tipos = [];
                foreach ($prof['docs'] as $d) {
                    $t = $d['tipo'];
                    $map[$t] = $d;
                    $tipos[] = $t;
                }
                $tipos = array_values(array_unique($tipos));
                sort($tipos, SORT_NATURAL | SORT_FLAG_CASE);

                // Helpers para mostrar valores o rayita
                $fmt = function($v, $dec = 0, $suf = '') {
                    if (is_null($v)) return '<span class="text-muted">—</span>';
                    return number_format($v, $dec) . $suf;
                };
            @endphp

            <div class="card mb-3" style="border-radius:8px; overflow:hidden;">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color:#1976d2;">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="text-white mb-0">{{ $prof['nombre'] }}</h5>
                        <span class="badge bg-light text-dark ms-2">Teacher ID: {{ $prof['teacher_id'] }}</span>
                        @if(!empty($prof['categoria']))
                            <span class="badge bg-secondary ms-1">{{ $prof['categoria'] }}</span>
                        @endif
                    </div>
                    <span class="badge bg-light text-dark">Unidad hasta: {{ $prof['unidad_hasta'] }}</span>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle tabla-profesor">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:220px;">Métrica \ Tipo</th>
                                    @foreach ($tipos as $tipo)
                                        <th class="text-center" style="min-width:170px;">{{ $tipo }}</th>
                                    @endforeach
                                    <th class="text-center table-secondary">Total / Prom</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Fila: Esperados --}}
                                <tr>
                                    <td class="fw-semibold">Esperados</td>
                                    @php $sumEsp = 0; @endphp
                                    @foreach ($tipos as $tipo)
                                        @php
                                            $val = $map[$tipo]['esperados'] ?? 0;
                                            $sumEsp += (int)$val;
                                        @endphp
                                        <td class="text-center">{!! $fmt($val) !!}</td>
                                    @endforeach
                                    <td class="text-center table-secondary">{!! $fmt($sumEsp) !!}</td>
                                </tr>

                                {{-- Fila: Entregados --}}
                                <tr>
                                    <td class="fw-semibold">Entregados</td>
                                    @php $sumEnt = 0; @endphp
                                    @foreach ($tipos as $tipo)
                                        @php
                                            $val = $map[$tipo]['entregados'] ?? 0;
                                            $sumEnt += (int)$val;
                                        @endphp
                                        <td class="text-center">{!! $fmt($val) !!}</td>
                                    @endforeach
                                    <td class="text-center table-secondary">{!! $fmt($sumEnt) !!}</td>
                                </tr>

                                {{-- Fila: Cumplimiento (%) – promedio simple de columnas con dato --}}
                                <tr>
                                    <td class="fw-semibold">Cumplimiento</td>
                                    @php $cumplVals = []; @endphp
                                    @foreach ($tipos as $tipo)
                                        @php
                                            $val = $map[$tipo]['cumplimiento'] ?? null;
                                            if (!is_null($val)) $cumplVals[] = (float)$val;
                                        @endphp
                                        <td class="text-center">{!! is_null($val) ? $fmt(null) : $fmt($val, 0, '%') !!}</td>
                                    @endforeach
                                    @php
                                        $cumplProm = count($cumplVals) ? round(array_sum($cumplVals)/count($cumplVals)) : null;
                                    @endphp
                                    <td class="text-center table-secondary">{!! is_null($cumplProm) ? $fmt(null) : $fmt($cumplProm, 0, '%') !!}</td>
                                </tr>

                                {{-- Fila: Promedio (calificaciones) – promedio simple de columnas con dato --}}
                                <tr>
                                    <td class="fw-semibold">Promedio</td>
                                    @php $promVals = []; @endphp
                                    @foreach ($tipos as $tipo)
                                        @php
                                            $val = $map[$tipo]['promedio'] ?? null;
                                            if (!is_null($val)) $promVals[] = (float)$val;
                                        @endphp
                                        <td class="text-center">{!! is_null($val) ? $fmt(null) : $fmt($val, 2) !!}</td>
                                    @endforeach
                                    @php
                                        $promProm = count($promVals) ? round(array_sum($promVals)/count($promVals), 2) : null;
                                    @endphp
                                    <td class="text-center table-secondary">{!! is_null($promProm) ? $fmt(null) : $fmt($promProm, 2) !!}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-warning">No hay información para mostrar.</div>
        @endforelse

    </div>
</div>
@endsection

@section('css')
<style>
    .card-header { border-top-left-radius:8px; border-top-right-radius:8px; }
    .table th, .table td { vertical-align: middle; }
    .tabla-profesor tbody tr td:first-child { white-space: nowrap; }
</style>
@endsection

@section('scripts')
<script>
    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: '{{ session('success') }}',
            showConfirmButton: false,
            timer: 8000
        });
    @endif
</script>
@endsection
