<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" href="{{ asset('favicons.ico') }}" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenidos | Universidad Tecnológica de Morelia</title>
    <!-- Puedes incluir aquí tus enlaces a fuentes o CSS externos si lo deseas -->
    <style>
        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: Arial, sans-serif;
        }

        /* Header con botón de Login en la esquina superior derecha */
        header {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 100;
        }
        header a.login-button {
            background: #004f39;
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
        }
        header a.login-button:hover {
            background: #003d2d;
        }

        /* Fondo con imagen de la UTM */
        .welcome-container {
            position: relative;
            background: url('{{ asset('images/utm_background.jpg') }}') no-repeat center center/cover;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            color: white;
        }
        /* Capa oscura para mejorar legibilidad */
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
        }
        /* Contenido principal */
        .content {
            position: relative;
            z-index: 2;
            max-width: 500px;
            padding: 20px;
        }
        /* Logo UTM */
        .utm-logo {
            width: 480px !important;
            max-width: 480px;
            height: auto;
            margin-bottom: 15px;
        }
        .title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .subtitle {
            font-size: 1.1rem;
            margin-bottom: 25px;
        }
        .buttons-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        /* Tarjetas para cada sistema */
        .system-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 8px;
        }
        .system-logo {
            width: 80px;
            max-width: 80px;
            height: auto;
        }
        /* Botones para los sistemas */
        .system-card a {
            display: inline-block;
            width: 100%;
            max-width: 250px;
            padding: 10px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 6px;
            text-transform: uppercase;
            transition: all 0.3s ease-in-out;
            text-decoration: none;
            background-color: #004f39;
            color: white;
        }
        .system-card a:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }
        /* Responsive: en pantallas más anchas, los botones en fila */
        @media (min-width: 600px) {
            .buttons-container {
                flex-direction: row;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Botón de Login en la esquina superior derecha -->
    <header>
        <a href="{{ route('login') }}" class="login-button">Login</a>
    </header>

    <div class="welcome-container">
        <div class="overlay"></div>
        <div class="content">
            <img src="{{ asset('logo_2025.png') }}" alt="UTM" class="utm-logo">
            <h1 class="title">Bienvenidos a la Plataforma UTM</h1>
            <p class="subtitle">Seleccione el sistema al que desea ingresar</p>
            <div class="buttons-container">
                <!-- Sistema de Inventarios -->
                <div class="system-card">
                    <img src="{{ asset('UTM.png') }}" alt="cargaHoraria" class="system-logo">
                    <a href="{{ request()->getHost() === 'localhost' ? '/cargaHoraria/login/' : '/cargaHoraria/login/' }}">
                        <i class="fa-solid fa-boxes-stacked"></i> Carga Horaria
                    </a>
                </div>
                <!-- Sistema Contable -->
                <div class="system-card">
                    <img src="{{ asset('img/logo_contable.png') }}" alt="Sistema Contable" class="system-logo">
                    <a href="{{ request()->getHost() === 'localhost' ? '/sistemaContable/public/login' : '/sistemaContable/public/login' }}">
                        <i class="fa-solid fa-calendar-alt"></i> Sistema Contable
                    </a>
                </div>
                <!-- Sistema Recursos Humanos -->
                <div class="system-card">
                    <img src="{{ asset('img/logo_humanos.png') }}" alt="Recursos Humanos" class="system-logo">
                    <a href="{{ request()->getHost() === 'localhost' ? '/sistemaHumanos/public/login' : '/sistemaHumanos/public/login' }}">
                        <i class="fa-solid fa-calendar-alt"></i> Recursos Humanos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        console.log("Página Welcome cargada");
    </script>
</body>
</html>
