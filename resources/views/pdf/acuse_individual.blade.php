<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>{{ $tituloAcuse }}</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:14px;} .header{text-align:center;margin-bottom:20px;} .section{margin-bottom:20px;} .footer{text-align:center;font-size:12px;margin-top:40px;}</style>
</head>
<body>
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
    <p style="word-break:break-all;">{{ $hashArchivo }}</p>
  </div>

  <div class="footer">
    <p>{{ $atentamente }}</p>
    <p>Fecha de emisión: {{ $fecha_firma }}</p>
  </div>
</body>
</html>
