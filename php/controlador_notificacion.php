<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"])) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit();
}

require_once "conexion.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit();
}

$id_not = (int)($_POST["id_not"] ?? 0);
$id_us  = $_SESSION["id"];

if ($id_not < 1) {
    echo json_encode(['ok' => false]);
    exit();
}

// Elimina solo si la notificación pertenece al usuario actual
$stmt = $conexion->prepare(
    "DELETE FROM notificacion WHERE id_not = ? AND id_us = ?"
);
$stmt->bind_param("ii", $id_not, $id_us);
$stmt->execute();
$ok = $stmt->affected_rows > 0;
$stmt->close();

echo json_encode(['ok' => $ok]);
