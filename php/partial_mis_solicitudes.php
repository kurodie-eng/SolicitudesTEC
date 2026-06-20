<?php
session_start();
if (empty($_SESSION['id']) || !is_numeric($_SESSION['id']) || (int)$_SESSION['id_rol'] !== 1) {
    http_response_code(403);
    exit;
}
require_once 'conexion.php';

$stmt = $conexion->prepare(
    "SELECT s.id_sol, s.encabezado, s.prioridad, s.fecha_creacion, s.fecha_limite, s.id_estado,
            e.nombre AS estado, ar.nombre AS area,
            a.fecha_fin AS fecha_fin_asignacion,
            b.id_bit, b.evidencia
     FROM solicitud s
     JOIN estado_solicitud e ON s.id_estado = e.id_estado
     JOIN area ar ON s.id_area = ar.id_area
     LEFT JOIN asignacion a ON a.id_sol = s.id_sol AND a.estado_asignacion = 'activa'
     LEFT JOIN bitacora b ON b.id_sol = s.id_sol
         AND b.id_bit = (SELECT MAX(id_bit) FROM bitacora WHERE id_sol = s.id_sol)
     WHERE s.id_us = ?
     ORDER BY s.fecha_creacion DESC"
);
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$solicitudes = $stmt->get_result();
$stmt->close();

$listaActivas     = [];
$listaCompletadas = [];
while ($s = $solicitudes->fetch_object()) {
    if (strtolower($s->estado) === 'finalizada') $listaCompletadas[] = $s;
    else                                          $listaActivas[]     = $s;
}
$totalSolicitudes = count($listaActivas) + count($listaCompletadas);

if ($totalSolicitudes === 0):
?>
<div class="tarjeta-cuerpo">
    <p style="color:#8f98b2; text-align:center;">No tienes solicitudes registradas.</p>
</div>
<?php else: ?>

<?php if (!empty($listaActivas)): ?>
<div style="padding: 12px 16px 4px;">
    <p style="font-size:11px; font-weight:700; color:#8f98b2; text-transform:uppercase; letter-spacing:1px;">Activas</p>
</div>
<div class="contenedor-tabla">
    <table style="table-layout:fixed; width:100%">
        <colgroup>
            <col style="width:6%"><col style="width:28%"><col style="width:15%"><col style="width:10%">
            <col style="width:10%"><col style="width:10%"><col style="width:12%"><col style="width:9%">
        </colgroup>
        <thead>
            <tr><th>ID</th><th>Título</th><th>Área</th><th>Prioridad</th><th>Fecha</th><th>Fecha límite</th><th>Estado</th><th>Acción</th></tr>
        </thead>
        <tbody>
            <?php foreach ($listaActivas as $s):
                $cp = match(strtolower($s->prioridad)) { 'alta' => 'etiqueta-alta', 'media' => 'etiqueta-media', 'baja' => 'etiqueta-baja', default => '' };
                $ce = match($s->id_estado) { 1 => 'etiqueta-pendiente', 2 => 'etiqueta-proceso', 4 => 'etiqueta-pendiente', 5 => 'etiqueta-cancelada', default => '' };
                $pe = match($s->id_estado) { 1 => 'pendiente', 2 => 'proceso', default => '' };
                $fecha       = date('d/m/Y H:i:s', strtotime($s->fecha_creacion));
                $fechalimite = $s->fecha_fin_asignacion
                    ? date('d/m/Y H:i:s', strtotime($s->fecha_fin_asignacion))
                    : date('d/m/Y H:i:s', strtotime($s->fecha_limite));
                $pdfPath = null;
                if ($s->id_estado == 4 && $s->evidencia) {
                    $carpeta = dirname(explode(',', $s->evidencia)[0]);
                    $pdfPath = $carpeta . '/reporte_' . $s->id_bit . '.pdf';
                } ?>
            <tr>
                <td><span class="texto-apagado texto-xs">#<?= $s->id_sol ?></span></td>
                <td><strong><?= htmlspecialchars($s->encabezado) ?></strong></td>
                <td><?= htmlspecialchars($s->area) ?></td>
                <td><span class="etiqueta <?= $cp ?>"><?= htmlspecialchars($s->prioridad) ?></span></td>
                <td class="texto-apagado"><?= $fecha ?></td>
                <td><?= $fechalimite ?></td>
                <td>
                    <span class="etiqueta <?= $ce ?>">
                        <span class="punto-estado-solicitud <?= $pe ?>"></span>
                        <?= htmlspecialchars($s->estado) ?>
                    </span>
                </td>
                <td>
                    <?php if ($s->id_estado == 4): ?>
                        <div style="display:flex; gap:4px; flex-wrap:wrap; align-items:center;">
                            <?php if ($pdfPath): ?>
                                <a href="<?= htmlspecialchars($pdfPath) ?>" target="_blank" class="btn btn-fantasma btn-pequeno">Ver PDF</a>
                            <?php endif; ?>
                            <form action="php/controlador_solicitud.php" method="POST" style="margin:0;">
                                <input type="hidden" name="accion" value="aprobar">
                                <input type="hidden" name="id_sol" value="<?= $s->id_sol ?>">
                                <button type="submit" class="btn btn-exito btn-pequeno">Aprobar</button>
                            </form>
                            <button class="btn btn-peligro btn-pequeno" onclick="abrirModalRechazo(<?= $s->id_sol ?>)">Rechazar</button>
                        </div>
                    <?php elseif ($s->id_estado == 5): ?>
                        <span class="texto-apagado texto-xs">Esperando reenvío...</span>
                    <?php else: ?>
                        <span class="texto-apagado texto-xs">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($listaCompletadas)): ?>
<div style="padding: 12px 16px 4px; margin-top: 8px;">
    <p style="font-size:11px; font-weight:700; color:#8f98b2; text-transform:uppercase; letter-spacing:1px;">Completadas</p>
</div>
<div class="contenedor-tabla">
    <table style="table-layout:fixed; width:100%">
        <colgroup>
            <col style="width:6%"><col style="width:28%"><col style="width:15%"><col style="width:10%">
            <col style="width:10%"><col style="width:10%"><col style="width:12%"><col style="width:9%">
        </colgroup>
        <thead>
            <tr><th>ID</th><th>Título</th><th>Área</th><th>Prioridad</th><th>Fecha</th><th>Fecha límite</th><th>Estado</th><th>Acción</th></tr>
        </thead>
        <tbody>
            <?php foreach ($listaCompletadas as $s):
                $cp = match(strtolower($s->prioridad)) { 'alta' => 'etiqueta-alta', 'media' => 'etiqueta-media', 'baja' => 'etiqueta-baja', default => '' };
                $fecha       = date('d/m/Y H:i:s', strtotime($s->fecha_creacion));
                $fechalimite = $s->fecha_fin_asignacion
                    ? date('d/m/Y H:i:s', strtotime($s->fecha_fin_asignacion))
                    : date('d/m/Y H:i:s', strtotime($s->fecha_limite)); ?>
            <tr>
                <td><span class="texto-apagado texto-xs">#<?= $s->id_sol ?></span></td>
                <td><strong><?= htmlspecialchars($s->encabezado) ?></strong></td>
                <td><?= htmlspecialchars($s->area) ?></td>
                <td><span class="etiqueta <?= $cp ?>"><?= htmlspecialchars($s->prioridad) ?></span></td>
                <td class="texto-apagado"><?= $fecha ?></td>
                <td><?= $fechalimite ?></td>
                <td>
                    <span class="etiqueta etiqueta-completada">
                        <span class="punto-estado-solicitud completada"></span>
                        Finalizada
                    </span>
                </td>
                <td><span class="texto-apagado texto-xs">—</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; ?>
