<?php
    session_start();
    include "php/conexion.php";
    include "php/controlador_login.php";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — ITSRV Soporte Técnico</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="pagina-login">
<!-- .pagina-login: display flex, centra el contenido en 100vh -->
    <div class="login-contenedor">
        <!-- max-width 420px -->
        <div class="login-tarjeta">

            <div class="login-logo">
                <div class="marca-emblema"><img src="img/logo_tec_.png" alt="logo del Instituto Tecnológico Superior de Rioverde" width="48"></div>
                <div>
                    <div class="login-logo-nombre">ITSRV</div>
                    <div class="login-logo-subtitulo">Instituto Tecnológico Superior de Rioverde</div>
                </div>
            </div>

            <div class="login-titulo">Bienvenido</div>
            <div class="login-subtitulo">Ingresa tus credenciales para acceder al sistema.</div>
            <!-- action="" envía el POST al mismo archivo (en este caso el propio index.php) -->
            <form action="" method="post">

                <div class="grupo-form">
                    <label class="etiqueta-form" for="correo">Correo electrónico</label>
                    <div class="campo-c">
                    <!-- .campo-c hace referencia a la posición relativa para el botón/input -->
                        <input class="campo-form" type="email" id="correo" name="correo" placeholder="correo@ejemplo.com" required>
                    </div>
                </div>

                <div class="grupo-form">
                    <label class="etiqueta-form" for="password">Contraseña</label>
                    <div class="campo-c">
                        <input class="campo-form" type="password" id="password" name="password" required>
                        <button type="button" id="togglePass" class="btn-mostrar-contrasena" aria-label="Mostrar contraseña">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <!-- input que funciona como botón. Envía los datos para su validación en la BD -->
                <input type="submit" class="btn-iniciar-sesion" name="btn-iniciar-sesion" value="Iniciar Sesión">
            </form>

        </div>

        <div class="login-pie">
            Sistema de Gestión de Soporte Técnico --- ITSRV © 2026
        </div>
    </div>

    <script src="js/inicio-sesion.js"></script>
</body>
</html>