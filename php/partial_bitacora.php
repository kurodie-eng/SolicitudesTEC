<?php
session_start();
if (empty($_SESSION['id']) || !is_numeric($_SESSION['id']) || (int)$_SESSION['id_rol'] !== 3) {
    http_response_code(403);
    exit;
}
require_once 'conexion.php';

$stmt = $conexion->prepare(
    "SELECT
        s.id_sol, s.encabezado AS sol_encabezado, s.prioridad, s.fecha_creacion,
        e.nombre AS estado,
        ar.nombre AS area, ar.id_area,
        CONCAT(us.nombre, ' ', us.app) AS solicitante,
        IFNULL(CONCAT(uw.nombre, ' ', uw.app), '—') AS trabajador,
        b.id_bit, b.evidencia, b.tipo_accion
     FROM solicitud s
     JOIN estado_solicitud e ON s.id_estado = e.id_estado
     JOIN area ar ON s.id_area = ar.id_area
     JOIN usuario us ON us.id_us = s.id_us
     LEFT JOIN asignacion a ON a.id_sol = s.id_sol
         AND a.estado_asignacion IN ('activa', 'completada')
     LEFT JOIN usuario uw ON uw.id_us = a.id_trabajador
     LEFT JOIN bitacora b ON b.id_bit = (
         SELECT MAX(id_bit) FROM bitacora WHERE id_sol = s.id_sol
     )
     ORDER BY s.fecha_creacion DESC"
);
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($registros)): ?>
<tr>
    <td colspan="7" style="text-align:center; color:#8f98b2;">
        No hay solicitudes registradas.
    </td>
</tr>
<?php else:
    foreach ($registros as $r):
        $claseEstado = match($r['estado']) {
            'Pendiente'          => 'etiqueta-pendiente',
            'En Proceso'         => 'etiqueta-proceso',
            'En Revisión'        => 'etiqueta-pendiente',
            'Finalizada'         => 'etiqueta-completada',
            'Reporte Rechazado'  => 'etiqueta-cancelada',
            default              => ''
        };
        $clasePrioridad = match(strtolower($r['prioridad'])) {
            'alta'  => 'etiqueta-alta',
            'media' => 'etiqueta-media',
            'baja'  => 'etiqueta-baja',
            default => ''
        };
        $pdfUrl = null;
        if ($r['id_bit'] && $r['evidencia']) {
            $primera = explode(',', $r['evidencia'])[0];
            $pdfUrl  = dirname(trim($primera)) . '/reporte_' . $r['id_bit'] . '.pdf';
        }
        $textoBusqueda = strtolower(
            $r['sol_encabezado'] . ' ' .
            $r['solicitante']    . ' ' .
            $r['trabajador']     . ' ' .
            $r['area']
        );
        $tipoAccion = $r['tipo_accion'] ?? null;
        $tipoLabel  = $tipoAccion ?? 'Sin Asignar';
?>
<tr data-estado="<?= htmlspecialchars($r['estado']) ?>"
    data-area="<?= $r['id_area'] ?>"
    data-tipo="<?= htmlspecialchars($tipoLabel) ?>"
    data-texto="<?= htmlspecialchars($textoBusqueda) ?>">
    <td>
        <div style="font-weight:600; color:#1a2340;">
            <?= htmlspecialchars($r['sol_encabezado']) ?>
        </div>
        <div class="texto-apagado" style="font-size:11px;">
            <?= htmlspecialchars($r['area']) ?>
        </div>
    </td>
    <td><?= htmlspecialchars($r['solicitante']) ?></td>
    <td class="texto-apagado"><?= htmlspecialchars($r['trabajador']) ?></td>
    <td>
        <span class="etiqueta <?= $claseEstado ?>">
            <?= htmlspecialchars($r['estado']) ?>
        </span>
        <?php if ($r['prioridad'] !== 'Sin Asignar'): ?>
            <span class="etiqueta <?= $clasePrioridad ?>" style="margin-top:4px; display:inline-block;">
                <?= htmlspecialchars($r['prioridad']) ?>
            </span>
        <?php endif; ?>
    </td>
    <td class="texto-apagado" style="white-space:nowrap;">
        <?php if ($tipoAccion): ?>
            <span class="etiqueta" style="background:#e8f4fd; color:#1a6fa3; border-color:#b3d7f0;">
                <?= htmlspecialchars($tipoAccion) ?>
            </span>
        <?php else: ?>
            <span class="texto-apagado">Sin Asignar</span>
        <?php endif; ?>
    </td>
    <td class="texto-apagado" style="white-space:nowrap;">
        <?= date('d/m/Y H:i:s', strtotime($r['fecha_creacion'])) ?>
    </td>
    <td>
        <?php if ($pdfUrl): ?>
            <a href="<?= htmlspecialchars($pdfUrl) ?>" target="_blank"
               class="btn btn-fantasma btn-pequeno">Ver PDF</a>
        <?php else: ?>
            <span class="texto-apagado">—</span>
        <?php endif; ?>
    </td>
</tr>
<?php
    endforeach;
endif;
?>
