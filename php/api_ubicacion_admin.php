<?php
session_start();
if (empty($_SESSION['id']) || !is_numeric($_SESSION['id']) || (int)$_SESSION['id_rol'] !== 3) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}
require_once 'conexion.php';

$stmt = $conexion->prepare(
    "SELECT
        u.id_us,
        CONCAT(u.nombre, ' ', u.app) AS nombre_completo,
        u.id_rol,
        r.nombre AS rol,
        u.disponible,
        u.ultima_lat,
        u.ultima_lng,
        UNIX_TIMESTAMP(u.ultima_conexion)     AS ts_conexion,
        UNIX_TIMESTAMP(u.ultima_ubicacion_ts) AS ts_ubicacion
     FROM usuario u
     JOIN rol r ON u.id_rol = r.id_rol
     WHERE u.id_rol != 3
     ORDER BY u.id_rol ASC, u.nombre ASC"
);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode($rows);
