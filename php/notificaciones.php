<?php

if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Location: ../index.php');
    exit;
}

function crearNotificacion(mysqli $db, int $id_us, ?int $id_sol, string $mensaje): void {
    $stmt = $db->prepare(
        "INSERT INTO notificacion (id_us, id_sol, mensaje) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("iis", $id_us, $id_sol, $mensaje);
    $stmt->execute();
    $stmt->close();
}

function notifSolicitudCreada(mysqli $db, int $id_sol, string $encabezado): void {
    $stmt = $db->prepare(
        "SELECT id_us FROM usuario WHERE id_rol = 2 AND disponible = 1"
    );
    $stmt->execute();
    $trabajadores = $stmt->get_result();
    $stmt->close();

    $msg = "Nueva solicitud de soporte: \"$encabezado\". Ingresa al sistema para aceptarla.";
    while ($t = $trabajadores->fetch_object()) {
        crearNotificacion($db, $t->id_us, $id_sol, $msg);
    }
}

function notifSolicitudAceptada(mysqli $db, int $id_sol): void {
    $stmt = $db->prepare(
        "SELECT id_us, encabezado, prioridad FROM solicitud WHERE id_sol = ?"
    );
    $stmt->bind_param("i", $id_sol);
    $stmt->execute();
    $sol = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$sol) return;
    crearNotificacion(
        $db, $sol->id_us, $id_sol,
        "Tu solicitud \"{$sol->encabezado}\" fue aceptada por un técnico. Prioridad: {$sol->prioridad}."
    );
}

function notifReporteEnviado(mysqli $db, int $id_sol): void {
    $stmt = $db->prepare(
        "SELECT id_us, encabezado FROM solicitud WHERE id_sol = ?"
    );
    $stmt->bind_param("i", $id_sol);
    $stmt->execute();
    $sol = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$sol) return;
    crearNotificacion(
        $db, $sol->id_us, $id_sol,
        "El técnico envió el reporte de solución para \"{$sol->encabezado}\". Revísalo para aprobarlo o rechazarlo."
    );
}

function notifReporteAprobado(mysqli $db, int $id_sol): void {
    $stmt = $db->prepare(
        "SELECT a.id_trabajador, s.encabezado
         FROM asignacion a
         JOIN solicitud s ON s.id_sol = a.id_sol
         WHERE a.id_sol = ? AND a.estado_asignacion = 'activa'
         LIMIT 1"
    );
    $stmt->bind_param("i", $id_sol);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$row) return;
    crearNotificacion(
        $db, $row->id_trabajador, $id_sol,
        "Tu reporte para \"{$row->encabezado}\" fue aprobado. ¡Solicitud finalizada!"
    );
}

function notifReporteRechazado(mysqli $db, int $id_sol, string $razon): void {
    $stmt = $db->prepare(
        "SELECT a.id_trabajador, s.encabezado
         FROM asignacion a
         JOIN solicitud s ON s.id_sol = a.id_sol
         WHERE a.id_sol = ? AND a.estado_asignacion = 'activa'
         LIMIT 1"
    );
    $stmt->bind_param("i", $id_sol);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_object();
    $stmt->close();

    if (!$row) return;
    crearNotificacion(
        $db, $row->id_trabajador, $id_sol,
        "Tu reporte para \"{$row->encabezado}\" fue rechazado. Motivo: \"$razon\". Debes reenviar el reporte."
    );
}
