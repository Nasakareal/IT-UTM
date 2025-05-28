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
            text-align: right;
            margin-bottom: 40px; /* 🔹 MÁS ESPACIO para que no tape el logo */
        }

        .section {
            margin-bottom: 20px;
            text-align: justify;
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

    {{-- Imagen de fondo --}}
    <img src="{{ public_path('fondo.jpg') }}" class="fondo">

    <div class="contenido">
        <div class="header">
            <p><strong>Morelia Michoacán, a {{ \Carbon\Carbon::now()->translatedFormat('d') }} de {{ \Carbon\Carbon::now()->translatedFormat('F') }} de {{ \Carbon\Carbon::now()->year }}</strong></p>
        </div>

        <div class="section">
            <p><strong>Acuse de recepción del formato de seguimiento de planeación ({{ $tipo }}) del cuatrimestre Mayo–Agosto 2025.</strong></p>
        </div>

        <div class="section">
            <p><strong>Información:</strong></p>
            <ul>
                <li><strong>Asignatura:</strong> {{ $materia }}</li>
                <li><strong>Unidad:</strong> {{ $unidad }}</li>
                <li><strong>Documento:</strong> {{ $tipo }}</li>
                <li><strong>Programa Educativo:</strong> {{ $programa }}</li>
            </ul>
        </div>

        <div class="section">
            <p>
                Sobre el particular, conforme a la normativa vigente de la Universidad Tecnológica de Morelia, se hace constar que {{ $usuario }} entregó en tiempo y forma mediante el Sistema Web TIUTM el formato de seguimiento de planeación ({{ $tipo }}) del cuatrimestre Mayo–Agosto 2025.
            </p>
            <p>
                Es importante mencionar que esta Universidad procederá a la revisión y análisis de la información proporcionada y en caso de detectar errores o inconsistencias se reportarán al usuario solicitando la atención correspondiente.
            </p>
        </div>

        <div class="section">
            <p><strong>El documento fue entregado por:</strong></p>
            <ul>
                <li><strong>Nombre:</strong> {{ $usuario }}</li>
                <li><strong>CURP:</strong> {{ $rfc }}</li>
                <li><strong>Fecha de Entrega:</strong> {{ $fecha_firma }}</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>ATENTAMENTE</strong></p>
            <p>Huella Digital</p>
            <p>{{ $hashArchivo }}</p>
            <p><strong>SUBDIRECCIÓN DEL PROGRAMA EDUCATIVO DE {{ strtoupper($programa) }}</strong></p>
        </div>
    </div>

</body>
</html>
