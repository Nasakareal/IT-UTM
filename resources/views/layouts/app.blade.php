<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TI-UTM')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicons.ico') }}" type="image/x-icon">

    <!-- CSS núcleo -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">

    <style>
        /* ---------- Estilo base ------------------------------------------------ */
        body::before{
            content:'';
            position:fixed;inset:0;
            background:url('{{ asset('utm_logo_copia.png') }}') no-repeat center/800px auto;
            opacity:.15;           /* sutil, sin formar círculos raros */
            z-index:-1;
        }

        .top-bar{
            width:100%;position:fixed;top:0;left:0;z-index:1000;
            display:flex;justify-content:space-between;align-items:center;
            padding:15px 20px;
            background:#009688;color:#fff;font-weight:bold;font-size:18px;
        }
        .top-bar a{color:inherit;text-decoration:none;margin-left:15px;font-weight:bold}
        .top-bar a:hover{text-decoration:underline}
        .top-bar .menu{display:flex;align-items:center;gap:15px}

        .container-content{
            width:80%;max-width:1000px;margin:90px auto 20px;
            background:rgba(255,255,255,.92);
            border-radius:12px;padding:30px 30px 50px;
            box-shadow:0 0 10px rgba(0,0,0,.15);
            position:relative;z-index:1;
        }

        /* ---------- SECCIÓN FESTIVA SEPTIEMBRE -------------------------------- */
.top-bar.septiembre {
    position: relative;    /* para que el ::after se posicione respecto a este */
    background: linear-gradient(to right,
        #006847 0%,   #006847 33.33%,
        #FFFFFF 33.33%, #FFFFFF 66.67%,
        #CE1126 66.67%, #CE1126 100%
    );
    color: #000;
}

/* Escudo en el centro de la franja blanca */
.top-bar.septiembre::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 48px;
    height: 64px;
    background: url('{{ asset("eagle.png") }}') no-repeat center/contain;
    pointer-events: none;
    z-index: 1;
}


        /* Animación de banderas */
        .banderas{pointer-events:none;position:fixed;top:0;left:0;width:100%;height:100%;overflow:hidden;z-index:50}
        .banderas span{
            position:absolute;top:-60px;
            width:38px;height:25px;           /* tamaño bandera */
            background:url('{{ asset('mexico.png') }}') no-repeat center/contain;
            animation:caer linear infinite;
            opacity:0.9;will-change:transform;
        }
        @keyframes caer{
            0%   {transform:translateY(-60px) rotate(0deg);}
            100% {transform:translateY(110vh) rotate(360deg);}
        }
        /* posiciones y duraciones variadas (15 banderas de ejemplo) */
        @for($i=1;$i<=15;$i++)
        .banderas span:nth-child({{ $i }}){left:{{ rand(2,98) }}%;animation-duration:{{ rand(7,12) }}s;animation-delay:-{{ rand(0,120)/10 }}s;}
        @endfor
    </style>

    @yield('styles')
</head>

@php
    //$septiembre = true;          // ← Vista de prueba SIEMPRE encendida
    $septiembre = now()->month == 9;   // ← Activación automática real
@endphp

<body class="hold-transition sidebar-mini {{ $septiembre ? 'septiembre' : '' }}">

    <!-- Barra superior -->
    <div class="top-bar {{ $septiembre ? 'septiembre' : '' }}">
        <div>
            <a href="{{ route('home') }}"><i class="bi bi-house-door"></i> Inicio</a>
        </div>
        <div class="menu">
            <a href="{{ route('correspondencias.index') }}"><i class="bi bi-envelope"></i> Correspondencia</a>
            <a href="{{ route('certificados.subir') }}"><i class="bi bi-award"></i> .P12</a>  <!-- <a> cerrado -->
            <a href="{{ route('tutoriales.index') }}"><i class="bi bi-journal-code"></i> Tutoriales</a>

            @can('ver configuraciones')
                <a href="{{ route('settings.index') }}"><i class="bi bi-gear"></i> Configurar</a>
            @else
                <a href="{{ route('password.change.form') }}"><i class="bi bi-gear"></i> Configurar</a>
            @endcan

            <a href="{{ route('logout') }}" class="text-danger"
               onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                <i class="bi bi-door-open"></i> Salir
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
        </div>
    </div>

    <!-- Banderas animadas -->
    @if($septiembre)
        <div class="banderas">
            {{-- 15 spans; Blade los generará con el @for del CSS para posiciones --}}
            @for($i=1;$i<=15;$i++) <span></span> @endfor
        </div>
    @endif

    <!-- Contenido principal -->
    <div class="container-content">@yield('content')</div>

    <!-- JS núcleo -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    @yield('scripts')
    @stack('scripts')

    <!-- Modal Aviso de Privacidad -->
    <div class="modal fade" id="avisoPrivacidadModal" tabindex="-1" aria-labelledby="avisoPrivacidadLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="avisoPrivacidadLabel">Aviso de Privacidad</h5>
                </div>
                <div class="modal-body text-justify">
                    <p>Sistema TI-UTM informa que los datos personales recabados serán tratados de manera confidencial y utilizados exclusivamente para fines relacionados con la operación académica, administrativa y de gestión institucional de la Universidad Tecnológica de Morelia.</p>
                    <p>Los datos proporcionados serán protegidos conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares. No serán compartidos con terceros sin su consentimiento, salvo en los casos previstos por la ley.</p>
                    <p>El uso de este sistema implica la aceptación de este aviso de privacidad.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Mostrar aviso de privacidad si la sesión lo pide --}}
    @if(session('mostrar_aviso'))
        <script>
            window.addEventListener('load', () => new bootstrap.Modal('#avisoPrivacidadModal').show());
        </script>
        @php session()->forget('mostrar_aviso'); @endphp
    @endif
</body>
</html>
