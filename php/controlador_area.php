<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 3) {
    header("Location: ../index.php");
    exit();
}

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["accion"])) {

    if ($_POST["accion"] === "agregar") {
        $nombre       = trim($_POST["nombre"]        ?? "");
        $id_categoria = (int)($_POST["id_categoria"] ?? 0);

        if (empty($nombre) || $id_categoria <= 0) {
            $_SESSION["error"] = "El nombre y la categoría son obligatorios.";
            $_SESSION["seccion_activa"] = "admin-areas";
            header("Location: ../Administrador.php");
            exit();
        }

        $stmt = $conexion->prepare("INSERT INTO area (nombre, id_categoria) VALUES (?, ?)");
        $stmt->bind_param("si", $nombre, $id_categoria);
        try {
            if ($stmt->execute()) {
                $_SESSION["exito"] = "Área \"$nombre\" agregada correctamente.";
            } else {
                $_SESSION["error"] = "Error al agregar el área.";
            }
        } catch (mysqli_sql_exception $e) {
            $_SESSION["error"] = $e->getCode() === 1062
                ? "Ya existe un área con ese nombre."
                : "Error al agregar el área.";
        }
        $stmt->close();
        $_SESSION["seccion_activa"] = "admin-areas";
        header("Location: ../Administrador.php");
        exit();
    }

    if ($_POST["accion"] === "editar") {
        $id_area      = (int)($_POST["id_area"]      ?? 0);
        $nombre       = trim($_POST["nombre"]         ?? "");
        $id_categoria = (int)($_POST["id_categoria"]  ?? 0);

        if ($id_area <= 0 || empty($nombre) || $id_categoria <= 0) {
            $_SESSION["error"] = "Datos inválidos.";
            $_SESSION["seccion_activa"] = "admin-areas";
            header("Location: ../Administrador.php");
            exit();
        }

        $stmt = $conexion->prepare("UPDATE area SET nombre=?, id_categoria=? WHERE id_area=?");
        $stmt->bind_param("sii", $nombre, $id_categoria, $id_area);
        try {
            if ($stmt->execute()) {
                $_SESSION["exito"] = "Área actualizada correctamente.";
            } else {
                $_SESSION["error"] = "Error al actualizar el área.";
            }
        } catch (mysqli_sql_exception $e) {
            $_SESSION["error"] = $e->getCode() === 1062
                ? "Ya existe un área con ese nombre."
                : "Error al actualizar el área.";
        }
        $stmt->close();
        $_SESSION["seccion_activa"] = "admin-areas";
        header("Location: ../Administrador.php");
        exit();
    }

    if ($_POST["accion"] === "eliminar") {
        $id_area = (int)($_POST["id_area"] ?? 0);

        $stmt = $conexion->prepare("DELETE FROM area WHERE id_area=?");
        $stmt->bind_param("i", $id_area);
        try {
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION["exito"] = "Área eliminada correctamente.";
            } else {
                $_SESSION["error"] = "No se pudo eliminar el área.";
            }
        } catch (mysqli_sql_exception $e) {
            $_SESSION["error"] = "No se puede eliminar esta área porque tiene solicitudes asociadas.";
        }
        $stmt->close();
        $_SESSION["seccion_activa"] = "admin-areas";
        header("Location: ../Administrador.php");
        exit();
    }
}

header("Location: ../Administrador.php");
exit();
?>
