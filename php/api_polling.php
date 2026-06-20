<?php
session_start();
if (empty($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'no-session']);
    exit;
}

require_once 'conexion.php';
header('Content-Type: application/json; charset=utf-8');

$id  = (int)$_SESSION['id'];
$rol = (int)$_SESSION['id_rol'];
$desdeNotif = max(0, (int)($_GET['desde_notif'] ?? 0));

$resp = [];

// ── NOTIFICACIONES ────────────────────────────────────────────────────────────
$row = $conexion->query(
    "SELECT COALESCE(MAX(id_not), 0) AS mx, COUNT(*) AS cnt
     FROM notificacion WHERE id_us = $id"
)->fetch_object();
$resp['notif_max_id'] = (int)$row->mx;
$resp['notif_count']  = (int)$row->cnt;

if ($desdeNotif > 0 && $resp['notif_max_id'] > $desdeNotif) {
    $r2 = $conexion->query(
        "SELECT id_not, mensaje,
                DATE_FORMAT(fecha_envio,'%d/%m/%Y %H:%i') AS fecha_fmt
         FROM notificacion
         WHERE id_us = $id AND id_not > $desdeNotif
         ORDER BY fecha_envio ASC"
    );
    $resp['notificaciones_nuevas'] = $r2->fetch_all(MYSQLI_ASSOC);
}

// ── DATOS POR ROL ─────────────────────────────────────────────────────────────
if ($rol === 2) { // Trabajador
    $row = $conexion->query(
        "SELECT COALESCE(MAX(id_sol), 0) AS mx, COUNT(*) AS cnt
         FROM solicitud WHERE id_estado = 1"
    )->fetch_object();
    $resp['sol_max_id'] = (int)$row->mx;
    $resp['sol_count']  = (int)$row->cnt;

    $row2 = $conexion->query(
        "SELECT GROUP_CONCAT(
             CONCAT(a.id_asg,':',a.estado_asignacion,':',s.id_estado)
             ORDER BY a.id_asg
         ) AS fp,
         SUM(a.estado_asignacion = 'activa') AS cnt_activas
         FROM asignacion a
         JOIN solicitud s ON s.id_sol = a.id_sol
         WHERE a.id_trabajador = $id AND a.estado_asignacion != 'cancelada'"
    )->fetch_object();
    $resp['asg_fingerprint'] = md5($row2->fp ?? '');
    $resp['asg_activas']     = (int)($row2->cnt_activas ?? 0);
}

if ($rol === 1) { // Solicitante
    $row = $conexion->query(
        "SELECT GROUP_CONCAT(CONCAT(id_sol,':',id_estado) ORDER BY id_sol) AS fp,
                COUNT(*) AS cnt,
                SUM(id_estado != 3) AS cnt_activas
         FROM solicitud WHERE id_us = $id"
    )->fetch_object();
    $resp['sol_fingerprint'] = md5($row->fp ?? '');
    $resp['sol_count']       = (int)$row->cnt;
    $resp['sol_activas']     = (int)($row->cnt_activas ?? 0);
}

if ($rol === 3) { // Administrador
    $row = $conexion->query(
        "SELECT GROUP_CONCAT(
             CONCAT(s.id_sol,':',s.id_estado,':',COALESCE(b.id_bit,0),':',COALESCE(b.tipo_accion,''))
             ORDER BY s.id_sol
         ) AS fp
         FROM solicitud s
         LEFT JOIN bitacora b ON b.id_bit = (
             SELECT MAX(id_bit) FROM bitacora WHERE id_sol = s.id_sol
         )"
    )->fetch_object();
    $resp['bit_fingerprint'] = md5($row->fp ?? '');
}

echo json_encode($resp);
