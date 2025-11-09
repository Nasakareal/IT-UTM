<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TI-UTM')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicons.ico') }}" type="image/x-icon">

    <!-- ===== CSS: Bootstrap 4 + AdminLTE 3 (BS4) ===== -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- Iconos y extras (no conflictúan con BS4) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- DataTables (tema Bootstrap 4) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap4.min.css">

    <!-- Tu CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <style>
        /* Marca de agua ligera */
        body::before{
            content:''; position:fixed; inset:0;
            background:url('{{ asset('utm_logo_copia.png') }}') no-repeat center/800px auto;
            opacity:.15; z-index:-1;
        }

        /* ===== Top bar responsive ===== */
        .top-bar{
            width:100%; position:fixed; top:0; left:0; z-index:990; /* < 1040 (backdrop) */
            display:flex; justify-content:space-between; align-items:center;
            padding:15px 20px; background:#009688; color:#fff; font-weight:bold; font-size:18px;
        }
        .top-bar a{ color:inherit; text-decoration:none; font-weight:bold }
        .top-bar a:hover{ text-decoration:underline }

        .top-bar .menu{ display:flex; align-items:center; gap:15px }
        .top-bar .menu a{ margin-left:0 } /* evitamos empuje lateral, ya usamos gap */

        .menu-toggle{
            display:none; /* visible sólo en móvil (via media query) */
            background:transparent; border:0; color:#fff;
            padding:6px 10px; border-radius:8px;
        }

        /* Contenedor principal */
        .container-content{
            width:80%; max-width:1000px; margin:90px auto 20px;
            background:rgba(255,255,255,.92);
            border-radius:12px; padding:30px 30px 50px;
            box-shadow:0 0 10px rgba(0,0,0,.15);
            position:relative; z-index:1;
        }

        /* SweetAlert arriba de todo */
        .swal2-container{ z-index:2147483647 !important; }

        /* Asegurar layering correcto del modal BS4 por si algo externo lo altera */
        .modal{ z-index:1060 !important; }
        .modal-backdrop{ z-index:1050 !important; }

        /* ====== Breakpoints ====== */

        /* <= 992px (tablets) */
        @media (max-width: 992px){
          .top-bar{ padding:12px 14px; font-size:16px; }
          .top-bar .menu a{ font-size:15px; }
          .container-content{ width:92%; margin-top:80px; padding:20px; }
        }

        /* <= 768px (móvil) */
        @media (max-width: 768px){
          .top-bar{ padding:10px 12px; font-size:15px; }
          .menu-toggle{ display:inline-flex; align-items:center; justify-content:center; }
          .top-bar .menu{
            display:none; /* oculto por defecto en móvil */
            position:fixed;
            top:56px;                         /* bajo la barra */
            left:10px; right:10px;
            background:#ffffff;
            color:#000;
            border-radius:12px;
            box-shadow:0 8px 20px rgba(0,0,0,.15);
            padding:10px;
            flex-direction:column;
            gap:8px;
            z-index:1000;
          }
          .top-bar .menu a{
            color:#000;
            width:100%;
            padding:10px 12px;
            border-radius:8px;
          }
          .top-bar .menu a:hover{
            background:#f3f4f6;
            text-decoration:none;
          }
          .top-bar .menu.open{ display:flex; } /* se muestra cuando .open */
          .container-content{ width:94%; margin-top:76px; padding:16px; }
        }

        /* <= 360px (muy chico) */
        @media (max-width: 360px){
          .top-bar{ font-size:14px; }
          .top-bar .menu a{ font-size:14px; padding:9px 10px; }
          .container-content{ margin-top:72px; }
        }
    </style>

    @yield('styles')
</head>

<body class="hold-transition sidebar-mini">

    <!-- Barra superior -->
    <div class="top-bar">
        <div>
            <a href="{{ route('home') }}"><i class="bi bi-house-door"></i> Inicio</a>
        </div>

        <!-- Botón hamburguesa (sólo móvil) -->
        <button class="menu-toggle d-lg-none" aria-controls="topMenu" aria-expanded="false" aria-label="Abrir menú">
            <i class="bi bi-list" style="font-size:1.6rem;line-height:1"></i>
        </button>

        <!-- Menú -->
        <div class="menu" id="topMenu">
            <a href="{{ route('correspondencias.index') }}"><i class="bi bi-envelope"></i> Correspondencia</a>
            <a href="{{ route('certificados.subir') }}"><i class="bi bi-award"></i> .P12</a>
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

    <!-- Contenido principal -->
    <div class="container-content">@yield('content')</div>

    <!-- ===== JS: jQuery + Popper 1 + Bootstrap 4 + AdminLTE 3 ===== -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Popper 1.x para Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <!-- Bootstrap 4.6 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <!-- AdminLTE (BS4) -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    <!-- DataTables + Buttons (tema Bootstrap 4) -->
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    @yield('scripts')
    @stack('scripts')

    <!-- Modal Aviso de Privacidad (Bootstrap 4 syntax) -->
    <div class="modal fade" id="avisoPrivacidadModal" tabindex="-1" role="dialog" aria-labelledby="avisoPrivacidadLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="avisoPrivacidadLabel">Aviso de Privacidad</h5>
                    <!-- BS4 close button -->
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                      <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-justify">
                    <p>Sistema TI-UTM informa que los datos personales recabados serán tratados de manera confidencial y utilizados exclusivamente para fines relacionados con la operación académica, administrativa y de gestión institucional de la Universidad Tecnológica de Morelia.</p>
                    <p>Los datos proporcionados serán protegidos conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares. No serán compartidos con terceros sin su consentimiento, salvo en los casos previstos por la ley.</p>
                    <p>El uso de este sistema implica la aceptación de este aviso de privacidad.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Mostrar aviso de privacidad si la sesión lo pide (BS4) --}}
    @if(session('mostrar_aviso'))
        <script>$(function(){ $('#avisoPrivacidadModal').modal('show'); });</script>
        @php session()->forget('mostrar_aviso'); @endphp
    @endif

    <script>
      $(document).on('show.bs.modal', '.modal', function () {
        $(this).appendTo('body');
      });
      (function(){
        var css = '.modal{z-index:1060!important}.modal-backdrop{z-index:1050!important}.top-bar{z-index:990!important}';
        var s   = document.createElement('style'); s.textContent = css; document.head.appendChild(s);
      })();

      // ====== Navbar móvil: toggle hamburguesa ======
      document.addEventListener('DOMContentLoaded', function(){
        var btn  = document.querySelector('.menu-toggle');
        var menu = document.getElementById('topMenu');
        if(!btn || !menu) return;

        btn.addEventListener('click', function(){
          var isOpen = menu.classList.toggle('open');
          btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Cierra al tocar fuera
        document.addEventListener('click', function(e){
          if(!menu.classList.contains('open')) return;
          if(!menu.contains(e.target) && !btn.contains(e.target)){
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
          }
        });

        // Cierra al navegar
        menu.querySelectorAll('a').forEach(function(a){
          a.addEventListener('click', function(){
            menu.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
          });
        });
      });
    </script>
</body>
</html>
