<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 1) {
    header("Location: ../index.php");
    exit();
}

require_once "conexion.php";

function eliminarCarpetaRecursiva(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        $ruta = "$dir/$item";
        is_dir($ruta) ? eliminarCarpetaRecursiva($ruta) : unlink($ruta);
    }
    rmdir($dir);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "crear";

    if ($accion === "aprobar") {
        $id_sol = (int)($_POST["id_sol"] ?? 0);
        $id_us  = $_SESSION["id"];

        $stmtCheck = $conexion->prepare(
            "SELECT id_sol FROM solicitud WHERE id_sol = ? AND id_us = ? AND id_estado = 4"
        );
        $stmtCheck->bind_param("ii", $id_sol, $id_us);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows === 0) {
            $stmtCheck->close();
            $_SESSION["error"] = "No se puede aprobar esta solicitud.";
            header("Location: ../Solicitante.php");
            exit();
        }
        $stmtCheck->close();

        $stmtBit = $conexion->prepare(
            "UPDATE bitacora SET aprobado = true WHERE id_sol = ?"
        );
        $stmtBit->bind_param("i", $id_sol);
        $stmtBit->execute();
        $stmtBit->close();

        // Notificar al trabajador antes de cambiar el estado de la asignación
        require_once __DIR__ . '/correo.php';
        require_once __DIR__ . '/notificaciones.php';
        correoReporteAprobado($conexion, $id_sol);
        notifReporteAprobado($conexion, $id_sol);

        // Completar asignación — dispara triggers finalizar_solicitud y liberar_trabajador
        $stmtAsg = $conexion->prepare(
            "UPDATE asignacion SET estado_asignacion = 'completada', fecha_fin = NOW()
             WHERE id_sol = ? AND estado_asignacion = 'activa'"
        );
        $stmtAsg->bind_param("i", $id_sol);
        $stmtAsg->execute();
        $stmtAsg->close();

        $_SESSION["exito"]          = "Solicitud aprobada y finalizada.";
        $_SESSION["seccion_activa"] = "creadas";
        header("Location: ../Solicitante.php");
        exit();
    }

    if ($accion === "rechazar") {
        $id_sol = (int)($_POST["id_sol"] ?? 0);
        $razon  = trim($_POST["razon"]   ?? "");
        $id_us  = $_SESSION["id"];

        if ($id_sol < 1 || empty($razon)) {
            $_SESSION["error"] = "Debes indicar el motivo del rechazo.";
            header("Location: ../Solicitante.php");
            exit();
        }

        // Verificar que la solicitud pertenece al solicitante y está en revisión
        $stmtCheck = $conexion->prepare(
            "SELECT id_sol FROM solicitud WHERE id_sol = ? AND id_us = ? AND id_estado = 4"
        );
        $stmtCheck->bind_param("ii", $id_sol, $id_us);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows === 0) {
            $stmtCheck->close();
            $_SESSION["error"] = "No se puede rechazar esta solicitud.";
            header("Location: ../Solicitante.php");
            exit();
        }
        $stmtCheck->close();

        // Obtener la entrada de bitácora actual (la más reciente sin razon_rechazo)
        $stmtBit = $conexion->prepare(
            "SELECT id_bit, evidencia FROM bitacora
             WHERE id_sol = ? AND razon_rechazo IS NULL
             ORDER BY id_bit DESC LIMIT 1"
        );
        $stmtBit->bind_param("i", $id_sol);
        $stmtBit->execute();
        $bitRow = $stmtBit->get_result()->fetch_object();
        $stmtBit->close();

        // Eliminar carpeta de evidencias (imágenes + PDF)
        if ($bitRow && $bitRow->evidencia) {
            $primeraRuta = explode(',', $bitRow->evidencia)[0];
            $carpetaRel  = dirname(trim($primeraRuta));
            $carpetaAbs  = __DIR__ . '/../' . $carpetaRel;
            eliminarCarpetaRecursiva($carpetaAbs);
        }

        // Guardar razón de rechazo y limpiar evidencia (archivos ya eliminados)
        if ($bitRow) {
            $stmtUpd = $conexion->prepare(
                "UPDATE bitacora SET razon_rechazo = ?, evidencia = NULL WHERE id_bit = ?"
            );
            $stmtUpd->bind_param("si", $razon, $bitRow->id_bit);
            $stmtUpd->execute();
            $stmtUpd->close();
        }

        // Cambiar estado a "Reporte Rechazado" (id_estado = 5)
        $stmtSol = $conexion->prepare(
            "UPDATE solicitud SET id_estado = 5 WHERE id_sol = ?"
        );
        $stmtSol->bind_param("i", $id_sol);
        $stmtSol->execute();
        $stmtSol->close();

        require_once __DIR__ . '/correo.php';
        require_once __DIR__ . '/notificaciones.php';
        correoReporteRechazado($conexion, $id_sol, $razon);
        notifReporteRechazado($conexion, $id_sol, $razon);

        $_SESSION["exito"]          = "Reporte rechazado. El trabajador deberá enviar uno nuevo.";
        $_SESSION["seccion_activa"] = "creadas";
        header("Location: ../Solicitante.php");
        exit();
    }

    // Acción por defecto: crear solicitud
    $encabezado  = trim($_POST["titulo"]      ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $id_area     = (int)($_POST["id_area"]    ?? 0);
    $prioridad   = "Sin Asignar";
    $id_us       = $_SESSION["id"];

    $errores = [];
    if (empty($encabezado)) {
        $errores[] = "El título es obligatorio.";
    } elseif (strlen($encabezado) > 50) {
        $errores[] = "El título no puede exceder 50 caracteres.";
    }
    if (strlen($descripcion) <= 10) {
        $errores[] = "La descripción debe tener más de 10 caracteres.";
    } elseif (strlen($descripcion) > 120) {
        $errores[] = "La descripción no puede exceder 120 caracteres.";
    }
    if ($id_area < 1) {
        $errores[] = "Selecciona un área.";
    }

    if (!empty($errores)) {
        $_SESSION["error"] = implode(" | ", $errores);
        $_SESSION["old"]   = [
            'titulo'      => $encabezado,
            'descripcion' => $descripcion,
            'id_area'     => $_POST["id_area"] ?? '',
        ];
        header("Location: ../Solicitante.php");
        exit();
    }

    $stmt = $conexion->prepare(
        "INSERT INTO solicitud (id_us, id_area, encabezado, descripcion, prioridad)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iisss", $id_us, $id_area, $encabezado, $descripcion, $prioridad);

    if ($stmt->execute()) {
        $id_sol_nuevo = $stmt->insert_id;
        $stmt->close();
        require_once __DIR__ . '/correo.php';
        require_once __DIR__ . '/notificaciones.php';
        correoSolicitudCreada($conexion, $encabezado, $descripcion);
        notifSolicitudCreada($conexion, $id_sol_nuevo, $encabezado);
        $_SESSION["exito"] = "Solicitud enviada correctamente.";
    } else {
        $errorMsg = $stmt->error;
        $stmt->close();
        $_SESSION["error"] = "Error al enviar la solicitud: $errorMsg";
    }
    header("Location: ../Solicitante.php");
    exit();
}

header("Location: ../Solicitante.php");
exit();
?>
