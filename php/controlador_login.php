<?php
if (!empty($_POST["btn-iniciar-sesion"])) {
    if (!empty($_POST["correo"]) and !empty($_POST["password"])) {

        // Definición de variables con consulta a la BD
        $correo   = trim($_POST["correo"]);
        $password = trim($_POST["password"]);

        // Consulta con sentencia pre-hecha
        $statement = $conexion->prepare("SELECT id_us, nombre, app, contrasena, id_rol, correo FROM usuario WHERE correo = ?");
        $statement->bind_param("s", $correo);
        $statement->execute();
        $resultado = $statement->get_result();

        if ($datos = $resultado->fetch_object()) {

            // Verificación del hash para regenerar la ID de la sesión
            if (password_verify($password, $datos->contrasena)) {
                session_regenerate_id(true);

                $_SESSION["id"]        = $datos->id_us;
                $_SESSION["nombre"]    = $datos->nombre;
                $_SESSION["app"]       = $datos->app;
                $_SESSION["id_rol"]    = $datos->id_rol;
                $_SESSION["correo"]    = $datos->correo;

                // Redirección dependiendo del rol del usuario
                switch ((int)$datos->id_rol) {
                    case 1:
                        header("Location: Solicitante.php");
                        break;
                    case 2:
                        header("Location: Trabajador.php");
                        break;
                    case 3:
                        header("Location: Administrador.php");
                        break;
                    default:
                        header("Location: login.php?error=" . urlencode("Rol no reconocido."));
                        break;
                }
                exit();

    // Mensajes de error en caso de recibir datos incorrectos
            } else {
                echo "<div class='alerta alerta-error'>Usuario o contraseña incorrectos</div>";
            }
        } else {
            echo "<div class='alerta alerta-error'>Correo o contraseña incorrectos</div>";
        }

        $statement->close();

    } else {
        echo "<div>Campos vacíos</div>";
    }
}
?>