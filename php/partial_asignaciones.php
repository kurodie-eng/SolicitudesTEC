<?php
session_start();
if (empty($_SESSION['id']) || !is_numeric($_SESSION['id']) || (int)$_SESSION['id_rol'] !== 2) {
    http_response_code(403);
    exit;
}
require_once 'conexion.php';

$stmt = $conexion->prepare(
    "SELECT a.id_asg, a.id_sol, a.estado_asignacion, a.fecha_inicio, a.fecha_fin,
            s.encabezado, s.prioridad, s.id_estado,
            ar.nombre AS area,
            b.id_bit, b.evidencia, b.razon_rechazo
     FROM asignacion a
     JOIN solicitud s ON a.id_sol = s.id_sol
     JOIN area ar ON s.id_area = ar.id_area
     LEFT JOIN bitacora b ON b.id_sol = a.id_sol
         AND b.id_bit = (SELECT MAX(id_bit) FROM bitacora WHERE id_sol = a.id_sol)
     WHERE a.id_trabajador = ?
       AND a.estado_asignacion != 'cancelada'
     ORDER BY a.estado_asignacion ASC, a.fecha_inicio DESC"
);
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$asignaciones = $stmt->get_result();
$stmt->close();

$listaActivas     = [];
$listaRevision    = [];
$listaRechazadas  = [];
$listaCompletadas = [];
while ($a = $asignaciones->fetch_object()) {
    if ($a->estado_asignacion === 'activa' && $a->id_estado == 4)      $listaRevision[]   = $a;
    elseif ($a->estado_asignacion === 'activa' && $a->id_estado == 5)  $listaRechazadas[] = $a;
    elseif ($a->estado_asignacion === 'activa')                         $listaActivas[]    = $a;
    else                                                                $listaCompletadas[]= $a;
}

$total = count($listaActivas) + count($listaRevision) + count($listaRechazadas) + count($listaCompletadas);

if ($total === 0):
?>
<div class="tarjeta-cuerpo">
    <p style="color:#8f98b2; text-align:center;">No tienes asignaciones activas.</p>
</div>
<?php else: ?>

<?php if (!empty($listaActivas)): ?>
<div style="padding: 12px 16px 4px;">
    <p style="font-size:11px; font-weight:700; color:#8f98b2; text-transform:uppercase; letter-spacing:1px;">Activas</p>
</div>
<div class="contenedor-tabla">
    <table style="table-layout:fixed; width:100%">
        <colgroup>
            <col style="width:30%"><col style="width:15%"><col style="width:12%">
            <col style="width:13%"><col style="width:13%"><col style="width:17%">
        </colgroup>
        <thead>
            <tr><th>Título</th><th>Área</th><th>Prioridad</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($listaActivas as $a):
                $cp = match(strtolower($a->prioridad)) { 'alta' => 'etiqueta-alta', 'media' => 'etiqueta-media', 'baja' => 'etiqueta-baja', default => '' }; ?>
            <tr>
                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                <td><span class="etiqueta <?= $cp ?>"><?= htmlspecialchars($a->prioridad) ?></span></td>
                <td class="texto-apagado"><?= date('d/m/Y H:i:s', strtotime($a->fecha_inicio)) ?></td>
                <td class="texto-apagado"><?= $a->fecha_fin ? date('d/m/Y H:i:s', strtotime($a->fecha_fin)) : '—' ?></td>
                <td><span class="etiqueta etiqueta-proceso">Activa</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($listaRevision)): ?>
<div style="padding: 12px 16px 4px; margin-top: 8px;">
    <p style="font-size:11px; font-weight:700; color:#9a6400; text-transform:uppercase; letter-spacing:1px;">En Revisión</p>
</div>
<div class="contenedor-tabla">
    <table style="table-layout:fixed; width:100%">
        <colgroup>
            <col style="width:25%"><col style="width:13%"><col style="width:11%">
            <col style="width:12%"><col style="width:12%"><col style="width:14%"><col style="width:13%">
        </colgroup>
        <thead>
            <tr><th>Título</th><th>Área</th><th>Prioridad</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Estado</th><th>Reporte</th></tr>
        </thead>
        <tbody>
            <?php foreach ($listaRevision as $a):
                $cp = match(strtolower($a->prioridad)) { 'alta' => 'etiqueta-alta', 'media' => 'etiqueta-media', 'baja' => 'etiqueta-baja', default => '' };
                $pdfPath = null;
                if ($a->evidencia) {
                    $carpeta = dirname(explode(',', $a->evidencia)[0]);
                    $pdfPath = $carpeta . '/reporte_' . $a->id_bit . '.pdf';
                } ?>
            <tr>
                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                <td><span class="etiqueta <?= $cp ?>"><?= htmlspecialchars($a->prioridad) ?></span></td>
                <td class="texto-apagado"><?= date('d/m/Y H:i:s', strtotime($a->fecha_inicio)) ?></td>
                <td class="texto-apagado"><?= $a->fecha_fin ? date('d/m/Y H:i:s', strtotime($a->fecha_fin)) : '—' ?></td>
                <td><span class="etiqueta etiqueta-pendiente">En Revisión</span></td>
                <td>
                    <?php if ($pdfPath): ?>
                        <a href="<?= htmlspecialchars($pdfPath) ?>" target="_blank" class="btn btn-fantasma btn-pequeno">Ver PDF</a>
                    <?php else: ?>
                        <span class="texto-apagado">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($listaRechazadas)): ?>
<div style="padding: 12px 16px 4px; margin-top: 8px;">
    <p style="font-size:11px; font-weight:700; color:#c0392b; text-transform:uppercase; letter-spacing:1px;">Reporte Rechazado — Reenviar</p>
</div>
<div class="contenedor-tabla">
    <table style="table-layout:fixed; width:100%">
        <colgroup>
            <col style="width:22%"><col style="width:12%"><col style="width:10%">
            <col style="width:12%"><col style="width:12%"><col style="width:32%">
        </colgroup>
        <thead>
            <tr><th>Título</th><th>Área</th><th>Prioridad</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Motivo del Rechazo</th></tr>
        </thead>
        <tbody>
            <?php foreach ($listaRechazadas as $a):
                $cp = match(strtolower($a->prioridad)) { 'alta' => 'etiqueta-alta', 'media' => 'etiqueta-media', 'baja' => 'etiqueta-baja', default => '' }; ?>
            <tr>
                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                <td><span class="etiqueta <?= $cp ?>"><?= htmlspecialchars($a->prioridad) ?></span></td>
                <td class="texto-apagado"><?= date('d/m/Y H:i:s', strtotime($a->fecha_inicio)) ?></td>
                <td class="texto-apagado"><?= $a->fecha_fin ? date('d/m/Y H:i:s', strtotime($a->fecha_fin)) : '—' ?></td>
                <td>
                    <p style="font-size:12px; color:#c0392b; margin:0 0 6px;"><?= htmlspecialchars($a->razon_rechazo ?? '') ?></p>
                    <button class="btn btn-advertencia btn-pequeno" onclick="irAReporte(<?= $a->id_sol ?>)">Reenviar Reporte</button>
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
            <col style="width:30%"><col style="width:15%"><col style="width:12%">
            <col style="width:13%"><col style="width:13%"><col style="width:17%">
        </colgroup>
        <thead>
            <tr><th>Título</th><th>Área</th><th>Prioridad</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($listaCompletadas as $a):
                $cp = match(strtolower($a->prioridad)) { 'alta' => 'etiqueta-alta', 'media' => 'etiqueta-media', 'baja' => 'etiqueta-baja', default => '' }; ?>
            <tr>
                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                <td><span class="etiqueta <?= $cp ?>"><?= htmlspecialchars($a->prioridad) ?></span></td>
                <td class="texto-apagado"><?= date('d/m/Y H:i:s', strtotime($a->fecha_inicio)) ?></td>
                <td class="texto-apagado"><?= $a->fecha_fin ? date('d/m/Y H:i:s', strtotime($a->fecha_fin)) : '—' ?></td>
                <td><span class="etiqueta etiqueta-completada">Completada</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; ?>
