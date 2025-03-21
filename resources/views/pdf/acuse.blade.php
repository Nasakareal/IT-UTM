<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acuse de Recepción</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { font-size: 20px; }
        .content { margin: 0 40px; }
        .qr { margin-top: 30px; text-align: right; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; }
        .linea { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <strong>{{ $dataAcuse['institucion'] }}</strong><br>
        {{ $dataAcuse['ciudad'] }}, a {{ $dataAcuse['fecha'] }}
    </div>
    
    <div class="content">
        <h2 style="text-align:center;">Acuse de recepción del reporte</h2>
        <p style="text-align:center;"><em>{{ $dataAcuse['tituloAcuse'] }}</em></p>
        <p style="text-align:center;">{{ $dataAcuse['subtitulo'] }}</p>
        
        <div class="linea"></div>
        
        <p>{{ $dataAcuse['cuerpo'] }}</p>
        
        <div class="linea"></div>
        
        <p><strong>ATENTAMENTE</strong></p>
        <p>{{ $dataAcuse['atentamente'] }}</p>
        
        <div class="qr">
            <img src="{{ $qrDataUri }}" alt="Código QR">
            <p>Huella digital: {{ $qrContent }}</p>
        </div>
    </div>
    
    <div class="footer">
        Impresión {{ $dataAcuse['impresion'] }}
    </div>
</body>
</html>
