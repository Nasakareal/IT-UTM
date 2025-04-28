<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acuse de Recepción</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .content { margin: 0 40px; }
        .qr { margin-top: 30px; text-align: right; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; }
        .linea { margin: 10px 0; border-bottom: 1px solid #ccc; }
        pre.signature {
            display: block;
            white-space: pre-wrap;
            word-wrap: break-word;
            overflow-wrap: break-word;
            background: #f5f5f5;
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <strong>{{ $dataAcuse['institucion'] }}</strong><br>
        {{ $dataAcuse['ciudad'] }}, a {{ $dataAcuse['fecha'] }}
    </div>
    
    <div class="content">
        <h2 style="text-align:center;">{{ $dataAcuse['tituloAcuse'] }}</h2>
        <p style="text-align:center;"><em>{{ $dataAcuse['subtitulo'] }}</em></p>
        
        <div class="linea"></div>
        
        <p>{{ $dataAcuse['cuerpo'] }}</p>

        <div class="linea"></div>

        <p><strong>ATENTAMENTE</strong></p>
        <p>{{ $dataAcuse['atentamente'] }}</p>
        
        <div class="qr">
            <img src="{{ $qrDataUri }}" alt="Código QR">
            <p><strong>Hash del archivo:</strong> {{ $hashArchivo }}</p>
        </div>
        
        <div class="linea"></div>
        
        <p><strong>Firma electrónica de:</strong> {{ $dataAcuse['firmante'] }} (RFC: {{ $dataAcuse['rfc'] }})</p>
        <p><strong>Fecha y hora de firma:</strong> {{ $dataAcuse['fecha_firma'] }}</p>
        <p><strong>Cadena Base64 de la firma:</strong></p>
        <pre class="signature">{{ $dataAcuse['firma_dig'] }}</pre>
        
        <div class="linea"></div>
        
        
    </div>
    
    <div class="footer">
        Impresión: {{ $dataAcuse['impresion'] }}
    </div>
</body>
</html>
