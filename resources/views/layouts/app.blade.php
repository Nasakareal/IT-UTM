<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TI-UTM')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicons.ico') }}" type="image/x-icon">
    
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    
    <style>
        /* 🔹 Imagen de fondo sin estirarse */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('{{ asset('utm_logo_copia.png') }}');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 800px auto;
            z-index: -1;
        }

        .top-bar {
            width: 100%;
            background-color: #009688;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        .top-bar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            margin-left: 15px;
        }
        .top-bar a:hover {
            text-decoration: underline;
        }
        .top-bar .menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .container-content {
            width: 80%;
            max-width: 1000px;
            margin: 80px auto 20px auto;
            padding-top: 60px;
            background-color: rgba(255, 255, 255, 0.90);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
        }
    </style>
    
    @yield('styles')
</head>
<body class="hold-transition sidebar-mini">
    
    <div class="top-bar">
        <div>
            <a href="{{ route('home') }}"><i class="bi bi-house-door"></i>Inicio</a>
        </div>
        <div class="menu">
            <a href="{{ route('correspondencias.index') }}"><i class="bi bi-envelope"></i> Correspondencia</a>
            <a href="{{ route('certificados.subir') }}"><i class="bi bi-award"> .P12</i>
            <a href="{{ route('tutoriales.index') }}"><i class="bi bi-journal-code"></i> Tutoriales</a>
            @can('ver configuraciones')
                <a href="{{ route('settings.index') }}"><i class="bi bi-gear"></i> Configurar</a>
            @else
                <a href="{{ route('password.change.form') }}"><i class="bi bi-gear"></i> Configurar</a>
            @endcan
            <a href="{{ route('logout') }}" class="text-danger"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bi bi-door-open"></i> Salir
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </div>

    <div class="container-content">
        @yield('content')
    </div>

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
    <!-- Modal de Aviso de Privacidad -->
    <div class="modal fade" id="avisoPrivacidadModal" tabindex="-1" aria-labelledby="avisoPrivacidadLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title" id="avisoPrivacidadLabel">Aviso de Privacidad</h5>
          </div>
          <div class="modal-body text-justify">
            <p>
              Sistema TI UTM informa que los datos personales recabados serán tratados de manera confidencial y utilizados exclusivamente para fines relacionados con la operación académica, administrativa y de gestión institucional de la Universidad Tecnológica de Morelia.
            </p>
            <p>
              Los datos proporcionados serán protegidos conforme a lo establecido en la Ley Federal de Protección de Datos Personales en Posesión de los Particulares. No serán compartidos con terceros sin su consentimiento, salvo en los casos previstos por la ley.
            </p>
            <p>
              El uso de este sistema implica la aceptación de este aviso de privacidad.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

</body>

@if(session('mostrar_aviso'))
    <script>
        window.addEventListener('load', function () {
            var modal = new bootstrap.Modal(document.getElementById('avisoPrivacidadModal'));
            modal.show();
        });
    </script>
    @php
        session()->forget('mostrar_aviso');
    @endphp
@endif

</html>
