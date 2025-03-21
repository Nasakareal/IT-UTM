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
    
    <style>
        /* 🔹 Barra superior FIJA */
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
        
        /* 🔹 Contenedor Principal */
        .container-content {
            width: 80%;
            max-width: 1000px;
            margin: 80px auto 20px auto;
            padding-top: 60px; /* Espacio para la barra */
        }
    </style>
    
    @yield('styles')
</head>
<body class="hold-transition sidebar-mini">
    
    <!-- 🔹 Barra superior -->
    <div class="top-bar">
        <div>
            <a href="{{ route('home') }}">Inicio</a>
        </div>
        <div class="menu">
            <a href="{{ route('correspondencias.index') }}"><i class="bi bi-envelope"></i> Correspondencia</a>
            <a href="#"><i class="bi bi-chat-left-text"></i> Chat</a>
            <a href="{{ route('settings.index') }}"><i class="bi bi-gear"></i> Configurar</a>
            <a href="{{ route('logout') }}" class="text-danger"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="bi bi-door-open"></i> Salir
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </div>

    <!-- 🔹 Contenido Dinámico -->
    <div class="container-content">
        @yield('content')
    </div>

    <!-- 📌 Cargar jQuery ANTES que Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- 📌 Scripts adicionales -->
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
    
</body>
</html>
