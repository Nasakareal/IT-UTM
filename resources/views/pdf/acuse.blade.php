<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloAcuse ?? 'Acuse de Recepción' }}</title>
    <style>
        @page { margin: 0; }

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
            margin-bottom: 40px;
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

    <img src="{{ public_path('fondo.jpg') }}" class="fondo">

    <div class="contenido">
        <div class="header">
            <p><strong>Morelia, Michoacán, a {{ \Carbon\Carbon::now()->translatedFormat('d \d\e F \d\e Y') }}</strong></p>
        </div>

        <div class="section">
            <p><strong>
                Acuse de recepción del submódulo "{{ $materia ?? 'No especificado' }}" correspondiente al cuatrimestre Mayo–Agosto 2025.
            </strong></p>
        </div>

        <div class="section">
            <p><strong>Información:</strong></p>
            <ul>
                <li><strong>Submódulo:</strong> {{ $materia ?? 'No especificado' }}</li>
                <li><strong>Tipo de documento:</strong> {{ $tipo ?? 'No especificado' }}</li>
                <li><strong>Programa Educativo:</strong> {{ $programa ?? 'No especificado' }}</li>
            </ul>
        </div>

        <div class="section">
            <p>
                Conforme a la normativa vigente de la Universidad Tecnológica de Morelia, se hace constar que <strong>{{ $usuario ?? 'Usuario desconocido' }}</strong> entregó en tiempo y forma el documento correspondiente al submódulo <strong>"{{ $materia ?? 'No especificado' }}"</strong> del cuatrimestre Mayo–Agosto 2025.
            </p>
            <p>
                La Universidad procederá a la revisión y análisis del documento, y en caso de inconsistencias se solicitará la atención correspondiente.
            </p>
        </div>

        <div class="section">
            <p><strong>El documento fue entregado por:</strong></p>
            <ul>
                <li><strong>Nombre:</strong> {{ $usuario ?? 'No especificado' }}</li>
                <li><strong>RFC/Curp:</strong> {{ $rfc ?? 'N/A' }}</li>
                <li><strong>Fecha de Entrega:</strong> {{ $fecha_firma ?? 'No definida' }}</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>ATENTAMENTE</strong></p>
            <p>{{ $atentamente ?? 'Universidad Tecnológica de Morelia' }}</p>
            <p>{{ $hashArchivo ?? 'HASH no disponible' }}</p>
        </div>
    </div>

</body>
</html>
