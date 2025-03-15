<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="{{ asset('favicons.ico') }}" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TI-UTM - Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Barra superior FIJA */
        .top-bar {
            width: 100%;
            background-color: #009688;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            color: white;
            font-size: 18px;
            font-weight: bold;
        }
        .top-bar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .top-bar a:hover {
            text-decoration: underline;
        }

        /* Contenedor que centra la info-box */
        .info-container {
            text-align: center;
            margin-top: 100px; /* Espacio desde la barra superior */
            margin-bottom: 20px;
        }

        /* Recuadro amarillo que se ajusta al contenido */
        .info-box {
            display: inline-block;           /* Se ajusta al ancho del texto */
            background-color: #F5EFE3;
            padding: 15px 20px;
            border-radius: 10px;
            font-size: 28px;                 /* Ajusta el tamaño base */
            font-weight: bold;
            box-sizing: border-box;
            text-align: center;              /* Centra el texto dentro del recuadro */
        }
        .info-box p {
            margin: 0; /* Elimina márgenes extra dentro del <p> */
        }

        .tiutm {
            display: inline-block;
            letter-spacing: -2px;
        }
        .red { color: #FF6347; font-weight: bold; }
        .green { color: #FFD700; font-weight: bold; }
        .gold { color: #00B29E; font-weight: bold; }

        /* Contenedor para el logo y el login en columna */
        .login-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1px; /* Espacio vertical entre el logo y el formulario */
            margin-top: 20px;
        }
        /* Estilo para el logo */
        .login-logo img {
            max-width: 250px; /* Tamaño máximo del logo */
            height: auto;
        }
        
        /* Media Queries para dispositivos móviles */
        @media (max-width: 768px) {
            .info-box {
                font-size: 20px;   /* Reduce el tamaño en pantallas pequeñas */
                padding: 10px 15px;
            }
        }
    </style>
</head>
<body class="hold-transition login-page">

    <!-- Barra superior -->
    <div class="top-bar">
        <a href="https://ut-morelia.edu.mx/" target="_blank">Universidad Tecnológica de Morelia</a>
    </div>

    <!-- Contenedor que centra la info-box -->
    <div class="info-container">
        <div class="info-box">
            <p>
                Tablero de información para la Universidad Tecnológica de Morelia.<br>
                <span class="tiutm">
                    <span class="red">T</span>
                    <span class="green">I</span>
                    <span class="red">-</span>
                    <span class="gold">UTM</span>
                </span>
            </p>
        </div>
    </div>

    <!-- Contenedor con Logo y Formulario -->
    <div class="login-container">
        <!-- Logo encima -->
        <div class="login-logo">
            <img src="{{ asset('original.png') }}" alt="Logo TI-UTM">
        </div>

        <!-- Formulario de Login -->
        <div class="login-box">
            <div class="card">
                <div class="card-body login-card-body">
                    <p class="login-box-msg">Inicia sesión</p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST">
                        @csrf
                        <div class="input-group mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Correo electrónico" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-envelope"></span>
                                </div>
                            </div>
                        </div>

                        <div class="input-group mb.3">
                            <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                            <div class="input-group-append">
                                <div class="input-group-text">
                                    <span class="fas fa-lock"></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-8">
                                <div class="icheck-primary">
                                    <input type="checkbox" name="remember" id="remember">
                                    <label for="remember">Recordarme</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <button type="submit" class="btn btn-primary btn-block">Ingresar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> <!-- Fin login-box -->
    </div> <!-- Fin login-container -->

    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/plugins/jquery/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
