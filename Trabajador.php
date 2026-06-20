<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 2) {
    header("Location: index.php");
    exit();
}
$msgExito      = $_SESSION["exito"]          ?? null;
$msgError      = $_SESSION["error"]          ?? null;
$seccionActiva = $_SESSION["seccion_activa"] ?? null;
$old           = $_SESSION["old"]            ?? [];
unset($_SESSION["exito"], $_SESSION["error"], $_SESSION["seccion_activa"], $_SESSION["old"]);

require_once "php/conexion.php";

//  Trae solicitudes si la solicitud es id_estado = 1 (pendientes).
$stmtSolicitudes = $conexion->prepare(
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
$stmtSolicitudes->execute();
$solicitudes = $stmtSolicitudes->get_result();
$totalSolicitudes = $solicitudes->num_rows;
$stmtSolicitudes->close();

// Trae las asignaciones activas y completadas del trabajador, con datos de bitácora
$stmtAsignaciones = $conexion->prepare(
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
$stmtAsignaciones->bind_param("i", $_SESSION["id"]);
$stmtAsignaciones->execute();
$asignaciones = $stmtAsignaciones->get_result();
$totalAsignaciones = $asignaciones->num_rows;
$stmtAsignaciones->close();
$listaActivas     = [];
$listaRevision    = [];
$listaRechazadas  = [];
$listaCompletadas = [];
while ($a = $asignaciones->fetch_object()) {
    if ($a->estado_asignacion === 'activa' && $a->id_estado == 4) {
        $listaRevision[] = $a;
    } elseif ($a->estado_asignacion === 'activa' && $a->id_estado == 5) {
        $listaRechazadas[] = $a;
    } elseif ($a->estado_asignacion === 'activa') {
        $listaActivas[] = $a;
    } else {
        $listaCompletadas[] = $a;
    }
}

$stmtNotifs = $conexion->prepare(
    "SELECT id_not, mensaje, fecha_envio FROM notificacion
     WHERE id_us = ? ORDER BY fecha_envio DESC LIMIT 30"
);
$stmtNotifs->bind_param("i", $_SESSION["id"]);
$stmtNotifs->execute();
$notificaciones = $stmtNotifs->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtNotifs->close();
$totalNotifs = count($notificaciones);

// Título auto-generado para el formulario de reporte
$hoy           = date('Y-m-d');
$sigRow        = $conexion->query("SELECT COUNT(*) + 1 AS sig FROM bitacora WHERE DATE(fecha_registro) = '$hoy'")->fetch_object();
$tituloReporte = date('d/m/Y') . ' - ' . str_pad((int)$sigRow->sig, 3, '0', STR_PAD_LEFT);

// Estado inicial para polling
$initSolMaxRow = $conexion->query("SELECT COALESCE(MAX(id_sol), 0) AS mx FROM solicitud WHERE id_estado = 1")->fetch_object();
$initSolMaxId  = (int)$initSolMaxRow->mx;
$initAsgFpRow  = $conexion->query(
    "SELECT GROUP_CONCAT(CONCAT(a.id_asg,':',a.estado_asignacion,':',s.id_estado) ORDER BY a.id_asg) AS fp
     FROM asignacion a JOIN solicitud s ON s.id_sol = a.id_sol
     WHERE a.id_trabajador = {$_SESSION['id']} AND a.estado_asignacion != 'cancelada'"
)->fetch_object();
$initAsgFp      = md5($initAsgFpRow->fp ?? '');
$initNotifMaxId = !empty($notificaciones) ? (int)max(array_column($notificaciones, 'id_not')) : 0;
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Solicitudes — Trabajador | ITSRV</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="layout-app">

    <aside class="sidebar">
        <div class="sidebar-marca">
            <div class="marca-fila">
                <div class="marca-emblema"><img src="img/logo_tec_.png" alt="logo del Instituto Tecnológico Superior de Rioverde" width="30"></div>
                <div>
                    <div class="marca-nombre">ITSRV</div>
                    <div class="marca-subtitulo">SOPORTEC</div>
                </div>
            </div>
            <div class="usuario-pastilla">
                <!-- Iniciales calculadas desde la sesión ej. Juan Perez= JP -->
                <div class="usuario-avatar">
                    <?php echo strtoupper(substr($_SESSION["nombre"], 0, 1) . substr($_SESSION["app"], 0, 1)); ?>
                </div>
                <div>
                    <div class="usuario-nombre">
                        <?php echo htmlspecialchars($_SESSION["nombre"] . " " . $_SESSION["app"]); ?>
                    </div>
                    <div class="usuario-rol">Trabajador</div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-etiqueta-seccion">Principal</div>
            <a href="#" class="nav-link nav-item active" data-section="solicitudes">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                <span class="nav-texto">Solicitudes</span>
                <?php if ($totalSolicitudes > 0): ?>
                    <span class="nav-contador"><?= $totalSolicitudes ?></span>
                <?php endif; ?>
            </a>
            <a href="#" class="nav-link nav-item" data-section="mis-asignaciones">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="nav-texto">Solicitudes Aceptadas</span>
                <?php $totalActivas = count($listaActivas) + count($listaRevision) + count($listaRechazadas); ?>
                <?php if ($totalActivas > 0): ?>
                    <span class="nav-contador"><?= $totalActivas ?></span>
                <?php endif; ?>
            </a>
            <a href="#" class="nav-link nav-item" data-section="reporte">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span class="nav-texto">Reporte de Solicitud</span>
            </a>
        </nav>

        <div class="sidebar-pie">
            <a href="php/controlador_cerrar.php" class="btn-cerrar-sesion">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span class="nav-texto">Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <div class="contenido-principal">

        <header class="topbar">
            <div style="display:flex; align-items:center; gap:12px;">
                <button class="btn-hamburguesa" onclick="toggleSidebar()" title="Menú">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div>
                    <div class="topbar-titulo" id="topbar-titulo">Solicitudes</div>
                    <div class="topbar-subtitulo">Instituto Tecnológico Superior de Rioverde</div>
                </div>
            </div>
            <div class="notif-contenedor">
                <button class="notif-boton" id="notif-boton" onclick="toggleNotificaciones()" title="Notificaciones">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($totalNotifs > 0): ?>
                        <span class="notif-badge"><?= $totalNotifs ?></span>
                    <?php endif; ?>
                </button>
                <div class="notif-panel" id="notif-panel">
                    <div class="notif-panel-encabezado">Notificaciones</div>
                    <div class="notif-lista">
                        <?php if (empty($notificaciones)): ?>
                            <div class="notif-vacio">Sin notificaciones nuevas</div>
                        <?php else: ?>
                            <?php foreach ($notificaciones as $n): ?>
                                <div class="notif-item" id="notif-<?= $n['id_not'] ?>">
                                    <div class="notif-mensaje"><?= htmlspecialchars($n['mensaje']) ?></div>
                                    <div class="notif-meta">
                                        <span class="notif-fecha"><?= date('d/m/Y H:i', strtotime($n['fecha_envio'])) ?></span>
                                        <button class="notif-eliminar" onclick="eliminarNotificacion(<?= $n['id_not'] ?>)" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="cuerpo-pagina">
            <?php if ($msgExito): ?>
                <div class="alerta alerta-exito"><?= htmlspecialchars($msgExito) ?></div>
            <?php endif; ?>
            <?php if ($msgError): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($msgError) ?></div>
            <?php endif; ?>

            <!-- SOLICITUDES -->
            <div id="solicitudes" class="section active">
                <!-- Un grid con dos columnas. La lista y el Panel lateral -->
                <div class="columnas-dashboard">

                    <div class="tarjeta">
                        <div class="tarjeta-encabezado">
                            <div class="tarjeta-titulo">Solicitudes Disponibles</div>
                        </div>
                        <div id="tablon-contenido" style="padding: 12px;">
                            <?php if ($totalSolicitudes === 0): ?>
                                <p style="color:#8f98b2; text-align:center; padding:16px;">
                                    No hay solicitudes pendientes.
                                </p>
                            <?php else: ?>
                                <?php while ($s = $solicitudes->fetch_object()): ?>
                                    <?php
                                        $prioridad   = strtolower($s->prioridad);
                                        $solicitante = htmlspecialchars($s->solicitante_nombre . " " . $s->solicitante_app);
                                        $fecha       = date("d/m/Y H:i:s", strtotime($s->fecha_creacion));
                                        $fechalimite = date("d/m/Y H:i:s", strtotime($s->fecha_limite));
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
                                        <!-- Botones principales que se ocultan al aceptar -->
                                        <div class="solicitud-acciones buttons">
                                            <button class="btn btn-exito btn-mediano" onclick="aceptarSolicitud(this, <?= $s->id_sol ?>)">Aceptar</button>
                                        </div>
                                        <!-- Botones post-decisión que están ocultos hasta que se acepte o rechace -->
                                        <div class="cancel-btn" style="display:none; gap:6px;">
                                            <!-- crearReporte() solo funciona si la solicitud fue aceptada -->
                                            <button class="btn btn-primario btn-pequeno create-report" onclick="crearReporte(<?= $s->id_sol ?>)">Crear Reporte</button>
                                            <button class="btn btn-fantasma btn-pequeno" onclick="cancelarSolicitud(this, <?= $s->id_sol ?>)">Cancelar</button>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- REPORTE  -->
            <!-- El reporte se oculta al cargar pero crearReporte() lo activa y pre-rellena los campos -->
            <div id="reporte" class="section" style="display:none;">
                <div class="tarjeta" style="max-width:680px;">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Reporte de Solicitud</div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <div id="report-form-container">
                            <p class="msj-instruccion">Selecciona una solicitud aceptada para generar el reporte.</p>
                        </div>
                        <div id="report-form">
                            <form id="form-reporte" action="php/controlador_trabajador.php" method="POST"
                                enctype="multipart/form-data">
                                <input type="hidden" name="accion" value="reporte">
                                
                                <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap;">
                                    <h3 style="font-size:13px; margin:0; color:black;">Generar Reporte para:</h3>
                                    <select class="campo-form" id="select-solicitud-reporte" name="id_sol"
                                            style="width:auto; min-width:200px;">
                                        <option value="" disabled selected>— Seleccionar Solicitud —</option>
                                        <?php if (!empty($listaActivas) && !empty($listaRechazadas)): ?>
                                            <optgroup label="En proceso">
                                            <?php foreach ($listaActivas as $a): ?>
                                                <option value="<?= $a->id_sol ?>"><?= htmlspecialchars($a->encabezado) ?></option>
                                            <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Reenviar reporte">
                                            <?php foreach ($listaRechazadas as $a): ?>
                                                <option value="<?= $a->id_sol ?>"><?= htmlspecialchars($a->encabezado) ?></option>
                                            <?php endforeach; ?>
                                            </optgroup>
                                        <?php else: ?>
                                            <?php foreach ($listaActivas as $a): ?>
                                                <option value="<?= $a->id_sol ?>"><?= htmlspecialchars($a->encabezado) ?></option>
                                            <?php endforeach; ?>
                                            <?php foreach ($listaRechazadas as $a): ?>
                                                <option value="<?= $a->id_sol ?>"><?= htmlspecialchars($a->encabezado) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="titulo-reporte">Título del reporte</label>
                                    <input class="campo-form" type="text" id="titulo-reporte"
                                        name="encabezado" readonly tabindex="-1"
                                        value="<?= htmlspecialchars($tituloReporte) ?>"
                                        style="background:#f4f6fa; color:#6b7590; cursor:default; user-select:none; pointer-events:none;">
                                </div>

                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="tipo-accion">Tipo de acción</label>
                                    <select class="campo-form" id="tipo-accion" name="tipo_accion" required>
                                        <option value="" disabled <?= empty($old['tipo_accion']) ? 'selected' : '' ?>>— Seleccionar —</option>
                                        <option value="Correctiva"      <?= ($old['tipo_accion'] ?? '') === 'Correctiva'      ? 'selected' : '' ?>>Correctiva</option>
                                        <option value="Preventiva"      <?= ($old['tipo_accion'] ?? '') === 'Preventiva'      ? 'selected' : '' ?>>Preventiva</option>
                                        <option value="Soporte Técnico" <?= ($old['tipo_accion'] ?? '') === 'Soporte Técnico' ? 'selected' : '' ?>>Soporte Técnico</option>
                                    </select>
                                </div>

                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="desc-problema">Descripción del problema <small class="contador-chars"></small></label>
                                    <textarea class="campo-form" id="desc-problema" name="descripcion_problema"
                                            rows="4" placeholder="Describe el problema detalladamente"
                                            maxlength="120" required><?= htmlspecialchars($old['descripcion_problema'] ?? '') ?></textarea>
                                </div>

                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="desc-solucion">Solución <small class="contador-chars"></small></label>
                                    <textarea class="campo-form" id="desc-solucion" name="descripcion_solucion"
                                            rows="4" placeholder="Describe la solución detalladamente"
                                            maxlength="120" required><?= htmlspecialchars($old['descripcion_solucion'] ?? '') ?></textarea>
                                </div>

                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="fotos">Fotografías de Evidencia   (Mín. 1, Máx. 3) [jpg, png, webp]</label>
                                    <input class="campo-form" type="file" id="fotos" name="fotos[]"
                                        multiple accept="image/jpeg,image/png,image/webp" required>
                                </div>

                                <button type="submit" class="btn btn-primario">Guardar Reporte</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SOLICITUDES ACEPTADAS -->
            <div id="mis-asignaciones" class="section" style="display:none;">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Solicitudes Aceptadas</div>
                    </div>

                    <div id="asignaciones-contenido">
                    <?php if ($totalAsignaciones === 0): ?>
                        <div class="tarjeta-cuerpo">
                            <p style="color:#8f98b2; text-align:center;">No tienes asignaciones activas.</p>
                        </div>
                    <?php else: ?>

                        <!-- ACTIVAS -->
                        <?php if (!empty($listaActivas)): ?>
                            <div style="padding: 12px 16px 4px;">
                                <p style="font-size:11px; font-weight:700; color:#8f98b2; text-transform:uppercase; letter-spacing:1px;">
                                    Activas
                                </p>
                            </div>
                            <div class="contenedor-tabla">
                                <table style="table-layout:fixed; width:100%">
                                        <colgroup>
                                            <col style="width:30%">
                                            <col style="width:15%">
                                            <col style="width:12%">
                                            <col style="width:13%">
                                            <col style="width:13%">
                                            <col style="width:17%">
                                        </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Área</th>
                                            <th>Prioridad</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listaActivas as $a): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                                                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                                                <td>
                                                    <?php
                                                        $clasePrioridad = match(strtolower($a->prioridad)) {
                                                            'alta'  => 'etiqueta-alta',
                                                            'media' => 'etiqueta-media',
                                                            'baja'  => 'etiqueta-baja',
                                                            default => ''
                                                        };
                                                    ?>
                                                    <span class="etiqueta <?= $clasePrioridad ?>"><?= htmlspecialchars($a->prioridad) ?></span>
                                                </td>
                                                <td class="texto-apagado"><?= date("d/m/Y H:i:s", strtotime($a->fecha_inicio)) ?></td>
                                                <td class="texto-apagado">
                                                    <?= $a->fecha_fin ? date("d/m/Y H:i:s", strtotime($a->fecha_fin)) : '—' ?>
                                                </td>
                                                <td><span class="etiqueta etiqueta-proceso">Activa</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                                        
                        <!-- EN REVISIÓN -->
                        <?php if (!empty($listaRevision)): ?>
                            <div style="padding: 12px 16px 4px; margin-top: 8px;">
                                <p style="font-size:11px; font-weight:700; color:#9a6400; text-transform:uppercase; letter-spacing:1px;">
                                    En Revisión
                                </p>
                            </div>
                            <div class="contenedor-tabla">
                                <table style="table-layout:fixed; width:100%">
                                    <colgroup>
                                        <col style="width:25%">
                                        <col style="width:13%">
                                        <col style="width:11%">
                                        <col style="width:12%">
                                        <col style="width:12%">
                                        <col style="width:14%">
                                        <col style="width:13%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Área</th>
                                            <th>Prioridad</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Estado</th>
                                            <th>Reporte</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listaRevision as $a): ?>
                                            <?php
                                                $clasePrioridad = match(strtolower($a->prioridad)) {
                                                    'alta'  => 'etiqueta-alta',
                                                    'media' => 'etiqueta-media',
                                                    'baja'  => 'etiqueta-baja',
                                                    default => ''
                                                };
                                                $pdfPath = null;
                                                if ($a->evidencia) {
                                                    $carpeta = dirname(explode(',', $a->evidencia)[0]);
                                                    $pdfPath = $carpeta . '/reporte_' . $a->id_bit . '.pdf';
                                                }
                                            ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                                                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                                                <td><span class="etiqueta <?= $clasePrioridad ?>"><?= htmlspecialchars($a->prioridad) ?></span></td>
                                                <td class="texto-apagado"><?= date("d/m/Y H:i:s", strtotime($a->fecha_inicio)) ?></td>
                                                <td class="texto-apagado"><?= $a->fecha_fin ? date("d/m/Y H:i:s", strtotime($a->fecha_fin)) : '—' ?></td>
                                                <td><span class="etiqueta etiqueta-pendiente">En Revisión</span></td>
                                                <td>
                                                    <?php if ($pdfPath): ?>
                                                        <a href="<?= htmlspecialchars($pdfPath) ?>" target="_blank"
                                                           class="btn btn-fantasma btn-pequeno">Ver PDF</a>
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

                        <!-- REPORTE RECHAZADO -->
                        <?php if (!empty($listaRechazadas)): ?>
                            <div style="padding: 12px 16px 4px; margin-top: 8px;">
                                <p style="font-size:11px; font-weight:700; color:#c0392b; text-transform:uppercase; letter-spacing:1px;">
                                    Reporte Rechazado — Reenviar
                                </p>
                            </div>
                            <div class="contenedor-tabla">
                                <table style="table-layout:fixed; width:100%">
                                    <colgroup>
                                        <col style="width:22%">
                                        <col style="width:12%">
                                        <col style="width:10%">
                                        <col style="width:12%">
                                        <col style="width:12%">
                                        <col style="width:32%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Área</th>
                                            <th>Prioridad</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Motivo del Rechazo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listaRechazadas as $a): ?>
                                            <?php
                                                $clasePrioridad = match(strtolower($a->prioridad)) {
                                                    'alta'  => 'etiqueta-alta',
                                                    'media' => 'etiqueta-media',
                                                    'baja'  => 'etiqueta-baja',
                                                    default => ''
                                                };
                                            ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                                                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                                                <td><span class="etiqueta <?= $clasePrioridad ?>"><?= htmlspecialchars($a->prioridad) ?></span></td>
                                                <td class="texto-apagado"><?= date("d/m/Y H:i:s", strtotime($a->fecha_inicio)) ?></td>
                                                <td class="texto-apagado"><?= $a->fecha_fin ? date("d/m/Y H:i:s", strtotime($a->fecha_fin)) : '—' ?></td>
                                                <td>
                                                    <p style="font-size:12px; color:#c0392b; margin:0 0 6px;">
                                                        <?= htmlspecialchars($a->razon_rechazo ?? '') ?>
                                                    </p>
                                                    <button class="btn btn-advertencia btn-pequeno"
                                                            onclick="irAReporte(<?= $a->id_sol ?>)">
                                                        Reenviar Reporte
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- COMPLETADAS -->
                        <?php if (!empty($listaCompletadas)): ?>
                            <div style="padding: 12px 16px 4px; margin-top: 8px;">
                                <p style="font-size:11px; font-weight:700; color:#8f98b2; text-transform:uppercase; letter-spacing:1px;">
                                    Completadas
                                </p>
                            </div>
                            <div class="contenedor-tabla">
                                <table style="table-layout:fixed; width:100%">
                                        <colgroup>
                                            <col style="width:30%">
                                            <col style="width:15%">
                                            <col style="width:12%">
                                            <col style="width:13%">
                                            <col style="width:13%">
                                            <col style="width:17%">
                                        </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Título</th>
                                            <th>Área</th>
                                            <th>Prioridad</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listaCompletadas as $a): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($a->encabezado) ?></strong></td>
                                                <td class="texto-apagado"><?= htmlspecialchars($a->area) ?></td>
                                                <td>
                                                    <?php
                                                        $clasePrioridad = match(strtolower($a->prioridad)) {
                                                            'alta'  => 'etiqueta-alta',
                                                            'media' => 'etiqueta-media',
                                                            'baja'  => 'etiqueta-baja',
                                                            default => ''
                                                        };
                                                    ?>
                                                    <span class="etiqueta <?= $clasePrioridad ?>"><?= htmlspecialchars($a->prioridad) ?></span>
                                                </td>
                                                <td class="texto-apagado"><?= date("d/m/Y H:i:s", strtotime($a->fecha_inicio)) ?></td>
                                                <td class="texto-apagado">
                                                    <?= $a->fecha_fin ? date("d/m/Y H:i:s", strtotime($a->fecha_fin)) : '—' ?>
                                                </td>
                                                <td><span class="etiqueta etiqueta-completada">Completada</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    </div><!-- /asignaciones-contenido -->
                </div>
            </div>
        </div>
    </div>

    <script src="js/comun.js"></script>
    <script src="js/trabajador.js"></script>
    <script src="js/polling.js"></script>
    <script>
        inicializarContadores();
        iniciarPolling({
            rol:        2,
            notifMaxId: <?= $initNotifMaxId ?>,
            solMaxId:   <?= $initSolMaxId ?>,
            solCount:   <?= $totalSolicitudes ?>,
            asgFp:      '<?= $initAsgFp ?>'
        });
        <?php if (!empty($old['id_sol'])): ?>
        (function () {
            var sel = document.getElementById('select-solicitud-reporte');
            var hid = document.getElementById('input-id-sol-reporte');
            if (sel) { sel.value = '<?= (int)$old['id_sol'] ?>'; }
            if (hid) { hid.value = '<?= (int)$old['id_sol'] ?>'; }
        })();
        <?php endif; ?>
    </script>

    <?php if ($seccionActiva): ?>
    <script>
        navegarSeccion("<?= htmlspecialchars($seccionActiva) ?>", titulosPagina);
    </script>
    <?php endif; ?>

    <!-- Modal de prioridad al aceptar solicitud -->
    <div id="modalPrioridad" class="fondo-modal">
        <div class="modal" style="max-width:360px;">
            <div class="modal-encabezado">
                <div class="modal-titulo">Asignar Prioridad</div>
                <button class="modal-cerrar" onclick="cerrarModalPrioridad()">✕</button>
            </div>
            <div class="modal-divisor"></div>
            <p style="font-size:13px; color:#4d5a7a; margin-bottom:16px;">
                Selecciona la prioridad para esta solicitud antes de aceptarla.
            </p>
            <form id="formAceptar" method="POST" action="php/controlador_trabajador.php">
                <input type="hidden" name="accion" value="aceptar">
                <input type="hidden" name="id_sol" id="modal-id-sol">
                <div class="grupo-form">
                    <label class="etiqueta-form" for="modal-prioridad">Prioridad</label>
                    <select class="campo-form" id="modal-prioridad" name="prioridad" required>
                        <option value="" disabled selected>Seleccionar...</option>
                        <option value="Alta">Alta</option>
                        <option value="Media">Media</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div class="modal-pie">
                    <button type="submit" class="btn btn-primario">Confirmar</button>
                    <button type="button" class="btn btn-fantasma" onclick="cerrarModalPrioridad()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>