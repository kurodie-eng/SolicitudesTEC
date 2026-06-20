<?php
session_start();
if (empty($_SESSION['id']) || !is_numeric($_SESSION['id']) || (int)$_SESSION['id_rol'] !== 2) {
    http_response_code(403);
    exit;
}
require_once 'conexion.php';

$stmt = $conexion->prepare(
    "SELECT s.id_sol, s.encabezado, s.descripcion, s.prioridad, s.fecha_creacion, s.fecha_limite,
            e.nombre AS estado,
            u.nombre AS solicitante_nombre, u.app AS solicitante_app,
            a.nombre AS area
     FROM solicitud s
     JOIN estado_solicitud e ON s.id_estado = e.id_estado
     JOIN usuario u ON s.id_us = u.id_us
     JOIN area a ON s.id_area = a.id_area
     WHERE s.id_estado = 1
     ORDER BY s.fecha_creacion DESC"
);
$stmt->execute();
$solicitudes = $stmt->get_result();

if ($solicitudes->num_rows === 0):
?>
<p style="color:#8f98b2; text-align:center; padding:16px;">No hay solicitudes pendientes.</p>
<?php
else:
    while ($s = $solicitudes->fetch_object()):
        $prioridad   = strtolower($s->prioridad);
        $solicitante = htmlspecialchars($s->solicitante_nombre . ' ' . $s->solicitante_app);
        $fecha       = date('d/m/Y H:i:s', strtotime($s->fecha_creacion));
        $fechalimite = date('d/m/Y H:i:s', strtotime($s->fecha_limite));
?>
<div class="tarjeta-solicitud solicitud-item" data-id="<?= $s->id_sol ?>">
    <div class="barra-prioridad <?= $prioridad ?>"></div>
    <div class="cuerpo-solicitud">
        <div class="solicitud-titulo-texto">
            <h3><?= htmlspecialchars($s->encabezado) ?></h3>
        </div>
        <div class="solicitud-meta">
            <span><strong>Usuario:</strong> <?= $solicitante ?></span>
            <span><strong>Área:</strong> <?= htmlspecialchars($s->area) ?></span>
            <span><strong>Fecha de publicación:</strong> <?= $fecha ?></span>
            <span><strong>Fecha límite:</strong> <?= $fechalimite ?></span>
        </div>
        <p style="font-size:12px; margin-top:5px; color:#4d5a7a;">
            <?= htmlspecialchars($s->descripcion) ?>
        </p>
        <p class="status" style="font-size:12px; margin-top:5px; color:#4d5a7a;">
            <strong>Estado:</strong> <?= htmlspecialchars($s->estado) ?>
        </p>
    </div>
    <div class="solicitud-acciones buttons">
        <button class="btn btn-exito btn-mediano" onclick="aceptarSolicitud(this, <?= $s->id_sol ?>)">Aceptar</button>
    </div>
    <div class="cancel-btn" style="display:none; gap:6px;">
        <button class="btn btn-primario btn-pequeno create-report" onclick="crearReporte(<?= $s->id_sol ?>)">Crear Reporte</button>
        <button class="btn btn-fantasma btn-pequeno" onclick="cancelarSolicitud(this, <?= $s->id_sol ?>)">Cancelar</button>
    </div>
</div>
<?php
    endwhile;
endif;
$stmt->close();
