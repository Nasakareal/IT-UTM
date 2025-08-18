{{-- resources/views/pdf/acuse_lote.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloAcuse }}</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 10pt; color:#111; }
        .fondo { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; }
        .contenido { padding: 80px 60px; position: relative; z-index: 10; }
        .header { text-align: right; margin-bottom: 40px; }
        .section { margin-bottom: 20px; text-align: justify; }
        .footer { text-align: center; font-size: 10pt; margin-top: 28px; }
        .muted { color: #555; }
        ol { padding-left: 18px; margin: 6px 0 0 0; }
        li { margin: 2px 0; }
    </style>
</head>
<body>
@php
    // Si $usuario viene como "Usuario {id}" o vacío, usa nombre real del Auth
    $profesor = trim($usuario ?? '');
    if ($profesor === '' || preg_match('/^Usuario\s+\d+$/', $profesor)) {
        $au = \Illuminate\Support\Facades\Auth::user();
        $profesor = $au->nombres ?? $au->name ?? $profesor;
    }
@endphp

@if(function_exists('public_path') && file_exists(public_path('fondo.jpg')))
    <img src="{{ public_path('fondo.jpg') }}" class="fondo">
@endif

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
            <li><strong>Profesor:</strong> {{ $profesor }}</li>
            <li><strong>CURP/RFC:</strong> {{ $rfc }}</li>
            <li><strong>Certificado (CN):</strong> {{ $certCN }}</li>
            <li><strong>Fecha/Hora de firma:</strong> {{ \Carbon\Carbon::parse($fecha)->format('Y-m-d H:i:s') }}</li>
        </ul>
    </div>

    {{-- SOLO la lista de tipos de documentos --}}
    <div class="section">
        <p><strong>Documentos enviados en la unidad:</strong></p>
        @if(!empty($items))
            <ol>
                @foreach($items as $it)
                    <li>{{ $it['tipo'] ?? '' }}</li>
                @endforeach
            </ol>
        @else
            <p class="muted">Sin documentos listados.</p>
        @endif
    </div>

    {{-- Pie con hash único y programa DINÁMICO --}}
    <div class="footer">
        <p><strong>ATENTAMENTE</strong></p>
        <p>Huella Digital</p>
        <p>{{ $hashArchivo }}</p>
        <p><strong>SUBDIRECCIÓN DEL PROGRAMA EDUCATIVO DE {{ strtoupper($programa ?? '') }}</strong></p>
    </div>
</div>
</body>
</html>
