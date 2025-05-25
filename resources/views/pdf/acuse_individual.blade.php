<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloAcuse }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
        }

        .fondo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .contenido {
            padding: 80px 60px;
            position: relative;
            z-index: 10;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 20px;
        }

        .footer {
            text-align: center;
            font-size: 10pt;
            margin-top: 40px;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    {{-- Imagen de fondo con ruta absoluta --}}
    <img src="{{ public_path('fondo.jpg') }}" class="fondo">

    <div class="contenido">
        <div class="header">
            <h2>{{ $institucion }}</h2>
            <p><strong>{{ $ciudad }}</strong></p>
            <h3>{{ $tituloAcuse }}</h3>
            <p><em>{{ $fecha }}</em></p>
        </div>

        <div class="section">
            <p>{{ $cuerpo }}</p>
            <ul>
                <li><strong>Materia:</strong> {{ $materia }}</li>
                <li><strong>Unidad:</strong> {{ $unidad }}</li>
                <li><strong>Documento:</strong> {{ $tipo }}</li>
            </ul>
        </div>

        <div class="section">
            <p>El documento fue entregado por:</p>
            <ul>
                <li><strong>Nombre:</strong> {{ $usuario }}</li>
                <li><strong>CURP:</strong> {{ $rfc }}</li>
                <li><strong>Fecha de Entrega:</strong> {{ $fecha_firma }}</li>
            </ul>
        </div>

        <div class="section">
            <p><strong>Hash SHA-256 del archivo:</strong></p>
            <p style="word-break: break-all;">{{ $hashArchivo }}</p>
        </div>

        <div class="footer">
            <p>{{ $atentamente }}</p>
            <p>Fecha de emisión: {{ $fecha_firma }}</p>
        </div>
    </div>

</body>
</html>
