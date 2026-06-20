<?php
session_start();
if (empty($_SESSION['id']) || !is_numeric($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}
require_once 'conexion.php';

header('Content-Type: application/json');

$id_us  = (int)$_SESSION['id'];
$accion = $_POST['accion'] ?? '';

if ($accion === 'conexion') {
    $stmt = $conexion->prepare("UPDATE usuario SET ultima_conexion = NOW() WHERE id_us = ?");
    $stmt->bind_param("i", $id_us);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['ok' => true]);

} elseif ($accion === 'ubicacion') {
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;

    if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Coordenadas inválidas']);
        exit;
    }

    $stmt = $conexion->prepare(
        "UPDATE usuario
         SET ultima_conexion = NOW(), ultima_lat = ?, ultima_lng = ?, ultima_ubicacion_ts = NOW()
         WHERE id_us = ?"
    );
    $stmt->bind_param("ddi", $lat, $lng, $id_us);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['ok' => true]);

} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Acción desconocida']);
}
