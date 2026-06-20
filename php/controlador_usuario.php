<?php
session_start();
// Comprobación para darle acceso solo al Administrador.
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"])) {
    header("Location: ../login.php");
    exit();
}
// Conexión
require_once "conexion.php";
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["accion"])) {
    if ($_POST["accion"] === "agregar") {
        // 1.- Variables para todos los campos
        $nombre     = trim($_POST["nombre"]     ?? "");
        $app        = trim($_POST["app"]        ?? "");
        $apm        = trim($_POST["apm"]        ?? "") ?: null; // Opcional, NULL si está vacío.
        $correo     = trim($_POST["correo"]      ?? "");
        $password   = $_POST["password"]        ?? "";
        $password2  = $_POST["password2"]       ?? "";
        $id_rol     = (int)($_POST["id_rol"]    ?? 0);
        $disponible = isset($_POST["disponible"]) ? (int)$_POST["disponible"] : 1;

        // 2.- Validaciones
        $errores = [];
        if (empty($nombre) || empty($app) || empty($correo)) {
            $errores[] = "Los campos 'Nombre', 'Apellido Paterno' y 'Correo' son obligatorios.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo electrónico no tiene un formato válido.";
        }
        if (strlen($password) < 8) {
            $errores[] = "La contraseña debe tener al menos 8 caracteres.";
        }
        if ($password !== $password2) {
            $errores[] = "Las contraseñas no coinciden.";
        }
        if (!in_array($id_rol, [1, 2])) {
            $errores[] = "Rol no válido.";
        }
        if (!empty($errores)) {
            // Regresa los errores y los muestra en un mensaje
            $msg = implode(" | ", $errores);
            $_SESSION["error"] = $msg;
            $_SESSION["seccion_activa"] = "admin-usuarios";
            header("Location: ../Administrador.php");
            exit();
        }

        // 3. Verificar que el correo no exista en la BD
        $statementCheck = $conexion->prepare(
            "SELECT id_us FROM usuario WHERE correo = ?"
        );
        $statementCheck->bind_param("s", $correo);
        $statementCheck->execute();
        $statementCheck->store_result();
        if ($statementCheck->num_rows > 0) {
            $statementCheck->close();
            $_SESSION["error"] = "El correo electrónico ya está registrado.";
            $_SESSION["seccion_activa"] = "admin-usuarios";
            header("Location: ../Administrador.php");
            exit();
        }
        $statementCheck->close();

        // 4. Hasheo de contraseña e inserción
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $statement = $conexion->prepare(
            "INSERT INTO usuario (nombre, app, apm, correo, contrasena, id_rol, disponible)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $statement->bind_param(
            "sssssii",
            $nombre, $app, $apm, $correo,
            $hash, $id_rol, $disponible
        );
        if ($statement->execute()) {
            $statement->close();
            $_SESSION["exito"] = "Usuario registrado correctamente.";
        } else {
            $errorMsg = $statement->error;
            $statement->close();
            $_SESSION["error"] = "Error al guardar: $errorMsg";
        }
        $_SESSION["seccion_activa"] = "admin-usuarios";
        header("Location: ../Administrador.php");
        exit();
    }

    if ($_POST["accion"] === "editar") {
        // 1.- Variables para todos los campos
        $id_us    = (int)($_POST["id_us"]    ?? 0);
        $nombre   = trim($_POST["nombre"]    ?? "");
        $app      = trim($_POST["app"]       ?? "");
        $apm      = trim($_POST["apm"]       ?? "") ?: null; // Opcional, NULL si está vacío.
        $correo   = trim($_POST["correo"]    ?? "");
        $password = $_POST["password"]       ?? "";
        $password2= $_POST["password2"]      ?? "";
        $id_rol   = (int)($_POST["id_rol"]   ?? 0);

        // 2.- Validaciones
        $errores = [];
        if (empty($nombre) || empty($app) || empty($correo)) {
            $errores[] = "Los campos 'Nombre', 'Apellido Paterno' y 'Correo' son obligatorios.";
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo electrónico no tiene un formato válido.";
        }
        if (!in_array($id_rol, [1, 2])) {
            $errores[] = "Rol no válido.";
        }
        // Validación de contraseña solo si se quiere cambiar
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $errores[] = "La contraseña debe tener al menos 8 caracteres.";
            }
            if ($password !== $password2) {
                $errores[] = "Las contraseñas no coinciden.";
            }
        }

        // 3.- Verificar que el correo no exista en la BD (excluyendo al propio usuario)
        $statementCheck = $conexion->prepare(
            "SELECT id_us FROM usuario WHERE correo = ? AND id_us != ?"
        );
        $statementCheck->bind_param("si", $correo, $id_us);
        $statementCheck->execute();
        $statementCheck->store_result();
        if ($statementCheck->num_rows > 0) {
            $errores[] = "El correo electrónico ya está registrado.";
        }
        $statementCheck->close();

        if (!empty($errores)) {
            // Regresa los errores y los muestra en un mensaje
            $_SESSION["error"] = implode(" | ", $errores);
            $_SESSION["seccion_activa"] = "admin-usuarios";
            header("Location: ../Administrador.php");
            exit();
        }

        // 4.- Actualización con o sin cambio de contraseña
        if (!empty($password)) {
            // 4a. Con nuevo hasheo de contraseña
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $statement = $conexion->prepare(
                "UPDATE usuario SET nombre=?, app=?, apm=?, correo=?, contrasena=?, id_rol=?
                 WHERE id_us=?"
            );
            $statement->bind_param(
                "sssssii",
                $nombre, $app, $apm, $correo, $hash, $id_rol, $id_us
            );
        } else {
            // 4b. Sin cambio de contraseña
            $statement = $conexion->prepare(
                "UPDATE usuario SET nombre=?, app=?, apm=?, correo=?, id_rol=?
                 WHERE id_us=?"
            );
            $statement->bind_param(
                "ssssii",
                $nombre, $app, $apm, $correo, $id_rol, $id_us
            );
        }
        if ($statement->execute()) {
            $statement->close();
            $_SESSION["exito"] = "Usuario actualizado correctamente.";
        } else {
            $errorMsg = $statement->error;
            $statement->close();
            $_SESSION["error"] = "Error al actualizar: $errorMsg";
        }
        $_SESSION["seccion_activa"] = "admin-usuarios";
        header("Location: ../Administrador.php");
        exit();
    }

    if ($_POST["accion"] === "eliminar") {
        $id_us = (int)($_POST["id_us"] ?? 0);
        // El AND id_rol != 3  hace imposible el eliminar admins por medio de este if.
        $statement = $conexion->prepare(
            "DELETE FROM usuario WHERE id_us = ? AND id_rol != 3"
        );
        $statement->bind_param("i", $id_us);
        try {
            if ($statement->execute() && $statement->affected_rows > 0) {
                $_SESSION["exito"] = "Usuario eliminado correctamente.";
            } else {
                $_SESSION["error"] = "No se pudo eliminar el usuario.";
            }
        } catch (mysqli_sql_exception $e) {
            $_SESSION["error"] = "No se puede eliminar este usuario porque tiene solicitudes o asignaciones registradas en el sistema.";
        }
        $statement->close();
        $_SESSION["seccion_activa"] = "admin-usuarios";
        header("Location: ../Administrador.php");
        exit();
    }
}
// Si de algún modo se llega aquí sin un POST correcto, salir.
header("Location: ../Administrador.php");
exit();
?>