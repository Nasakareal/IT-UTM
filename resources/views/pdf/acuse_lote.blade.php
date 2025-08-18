{{-- resources/views/pdf/acuse_lote.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloAcuse }}</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 10pt; }
        .fondo { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }
        .contenido { padding: 80px 60px; position: relative; z-index: 10; }
        .header { text-align: right; margin-bottom: 40px; }
        .section { margin-bottom: 20px; text-align: justify; }
        .footer { text-align: center; font-size: 10pt; margin-top: 28px; }

        table { width:100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #444; padding: 6px; vertical-align: top; }
        th:nth-child(1){width:28px}
        th:nth-child(2){width:24%}
        th:nth-child(3){width:28%}
        th:nth-child(4){width:80px}
        th:nth-child(5){width:26%}
        th:nth-child(6){width:16%}
        .mono { font-family: DejaVu Sans Mono, DejaVu Sans, monospace; word-break: break-all; }
        .muted { color: #555; }
    </style>
</head>
<body>
    <img src="{{ public_path('fondo.jpg') }}" class="fondo">

    <div class="contenido">
        <div class="header">
            <p><strong>
                Morelia Michoacán, a {{ \Carbon\Carbon::parse($fecha)->translatedFormat('d') }}
                de {{ \Carbon\Carbon::parse($fecha)->translatedFormat('F') }}
                de {{ \Carbon\Carbon::parse($fecha)->year }}
            </strong></p>
        </div>

        <div class="section">
            <p><strong>{{ $tituloAcuse }}</strong></p>
        </div>

        <div class="section">
            <p><strong>Información del lote:</strong></p>
            <ul>
                <li><strong>Folio de lote:</strong> {{ $loteId }}</li>
                <li><strong>Asignatura:</strong> {{ $materia }}</li>
                <li><strong>Grupo:</strong> {{ $grupo }}</li>
                <li><strong>Unidad:</strong> {{ $unidad }}</li>
                <li><strong>Profesor:</strong> {{ $usuario }}</li>
                <li><strong>CURP/RFC:</strong> {{ $rfc }}</li>
                <li><strong>Certificado (CN):</strong> {{ $certCN }}</li>
                <li><strong>Fecha/Hora de firma:</strong> {{ \Carbon\Carbon::parse($fecha)->format('Y-m-d H:i:s') }}</li>
            </ul>
        </div>

        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo de documento</th>
                        <th>Archivo</th>
                        <th>Bytes</th>
                        <th>Hash SHA-256</th>
                        <th>Firma (.sig)</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($items as $i => $it)
                    <tr>
                        <td style="text-align:center">{{ $i+1 }}</td>
                        <td>{{ $it['tipo'] }}</td>
                        <td>{{ $it['nombre'] }}</td>
                        <td style="text-align:right">{{ (int)($it['bytes'] ?? 0) }}</td>
                        <td class="mono">{{ $it['hash'] }}</td>
                        <td class="mono">{{ $it['sig'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if(empty($items))
                <p class="muted">No hubo documentos firmados en este lote.</p>
            @endif
        </div>

        <div class="footer">
            <p><strong>ATENTAMENTE</strong></p>
            <p>Huella Digital</p>
            <p class="muted">Este acuse consolida la firma digital de los documentos listados. Las firmas son desacopladas (.sig) calculadas con SHA-256.</p>
        </div>
    </div>
</body>
</html>
