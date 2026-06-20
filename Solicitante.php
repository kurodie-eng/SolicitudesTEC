<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 1) {
    header("Location: index.php");
    exit();
}

// Mensajes flash
$msgExito      = $_SESSION["exito"]          ?? null;
$msgError      = $_SESSION["error"]          ?? null;
$seccionActiva = $_SESSION["seccion_activa"] ?? null;
$old           = $_SESSION["old"]            ?? [];
unset($_SESSION["exito"], $_SESSION["error"], $_SESSION["seccion_activa"], $_SESSION["old"]);

require_once "php/conexion.php";

$stmtSolicitudes = $conexion->prepare(
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
$stmtSolicitudes->bind_param("i", $_SESSION["id"]);
$stmtSolicitudes->execute();
$solicitudes = $stmtSolicitudes->get_result();
$totalSolicitudes = $solicitudes->num_rows;
$stmtSolicitudes->close();
$listaActivas     = [];
$listaCompletadas = [];
while ($s = $solicitudes->fetch_object()) {
    if (strtolower($s->estado) === 'finalizada') {
        $listaCompletadas[] = $s;
    } else {
        $listaActivas[] = $s;
    }
}

// Trae las asignaciones activas y completadas del trabajador
$stmtAsignaciones = $conexion->prepare(
    "SELECT a.id_asg, a.estado_asignacion, a.fecha_inicio, a.fecha_fin,
            s.encabezado, s.prioridad,
            ar.nombre AS area
     FROM asignacion a
     JOIN solicitud s ON a.id_sol = s.id_sol
     JOIN area ar ON s.id_area = ar.id_area
     WHERE a.id_trabajador = ?
       AND a.estado_asignacion != 'cancelada'
     ORDER BY a.estado_asignacion ASC, a.fecha_inicio DESC"
);
$stmtAsignaciones->bind_param("i", $_SESSION["id"]);
$stmtAsignaciones->execute();
$asignaciones = $stmtAsignaciones->get_result();
$totalAsignaciones = $asignaciones->num_rows;
$stmtAsignaciones->close();

$stmtNotifs = $conexion->prepare(
    "SELECT id_not, mensaje, fecha_envio FROM notificacion
     WHERE id_us = ? ORDER BY fecha_envio DESC LIMIT 30"
);
$stmtNotifs->bind_param("i", $_SESSION["id"]);
$stmtNotifs->execute();
$notificaciones = $stmtNotifs->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtNotifs->close();
$totalNotifs = count($notificaciones);

// Estado inicial para polling
$initFpRow      = $conexion->query(
    "SELECT GROUP_CONCAT(CONCAT(id_sol,':',id_estado) ORDER BY id_sol) AS fp
     FROM solicitud WHERE id_us = {$_SESSION['id']}"
)->fetch_object();
$initSolFp      = md5($initFpRow->fp ?? '');
$initNotifMaxId = !empty($notificaciones) ? (int)max(array_column($notificaciones, 'id_not')) : 0;
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Solicitudes — Usuario | ITSRV</title>
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
                    <div class="usuario-rol">Solicitante</div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-etiqueta-seccion">Solicitudes</div>
            <a href="#" class="nav-link nav-item active" data-section="crear">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="nav-texto">Nueva Solicitud</span>
            </a>
            <a href="#" class="nav-link nav-item" data-section="creadas">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <span class="nav-texto">Mis Solicitudes</span>
                <?php if (count($listaActivas) > 0): ?>
                    <span class="nav-contador"><?= count($listaActivas) ?></span>
                <?php endif; ?>
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
                    <div class="topbar-titulo" id="topbar-titulo">Nueva Solicitud</div>
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

            <!--  NUEVA SOLICITUD  -->
            <div id="crear" class="section active">
                <div class="tarjeta" style="max-width: 680px;">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Nueva solicitud de soporte</div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <!-- Utiliza el método POST para enviar a controlador_solicitud.php por POST -->
                        <form action="php/controlador_solicitud.php" method="POST">

                            <div class="grupo-form">
                                <label class="etiqueta-form" for="titulo">Título de la solicitud <small class="contador-chars"></small></label>
                                <input class="campo-form" type="text" id="titulo" name="titulo"
                                    placeholder="Ej: Equipo sin acceso a red" maxlength="50" required
                                    value="<?= htmlspecialchars($old['titulo'] ?? '') ?>">
                            </div>

                            <!--  red (grid) con dos columnas. Una  para Área y otra para Prioridad -->
                            <div class="fila-form">
                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="area">Área</label>
                                    <select class="campo-form" id="area" name="id_area" required>
                                        <option value="">— Seleccionar área —</option>
                                        <optgroup label="Dirección">
                                            <option value="1">Dirección General</option>
                                            <option value="2">Dirección Académica</option>
                                            <option value="3">Dirección de Vinculación</option>
                                        </optgroup>
                                        <optgroup label="Académico">
                                            <option value="4">Docencia</option>
                                            <option value="5">Desarrollo Académico</option>
                                            <option value="6">Coordinación de Inglés</option>
                                            <option value="7">Biblioteca</option>
                                            <option value="8">Titulación</option>
                                            <option value="9">Psicopedagogía</option>
                                            <option value="10">Cultura y Deportes</option>
                                        </optgroup>
                                        <optgroup label="Administrativo">
                                            <option value="11">Recursos Materiales</option>
                                            <option value="12">Recursos Financieros</option>
                                            <option value="13">Caja</option>
                                            <option value="14">Planeación</option>
                                            <option value="15">Calidad</option>
                                            <option value="16">Transparencia</option>
                                            <option value="17">Centro de Copiado</option>
                                        </optgroup>
                                        <optgroup label="Jefaturas">
                                            <option value="18">Industrial</option>
                                            <option value="19">Innovación Agrícola</option>
                                            <option value="20">Informática</option>
                                            <option value="21">Sistemas Computacionales</option>
                                            <option value="22">Gestión Empresarial</option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>

                            <div class="grupo-form">
                                <label class="etiqueta-form" for="descripcion">Descripción detallada <small class="contador-chars"></small></label>
                                <textarea class="campo-form" id="descripcion" name="descripcion"
                                        rows="5" placeholder="Describe el problema con el mayor detalle posible"
                                        maxlength="120" required><?= htmlspecialchars($old['descripcion'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primario w-full"
                                    style="justify-content:center; padding:10px;">
                                Enviar Solicitud
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MIS SOLICITUDES -->
            <div id="creadas" class="section" style="display:none;">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <?php if ($totalSolicitudes > 0): ?>
                            <div class="tarjeta-titulo">
                                Mis Solicitudes
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-primario btn-pequeno" onclick="navTo('crear')">Nueva solicitud</button>
                    </div>

                    <div id="mis-sol-contenido">
                    <?php if ($totalSolicitudes === 0): ?>
                        <div class="tarjeta-cuerpo">
                            <p style="color:#8f98b2; text-align:center;">No tienes solicitudes registradas.</p>
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
                                            <col style="width:6%">
                                            <col style="width:28%">
                                            <col style="width:15%">
                                            <col style="width:10%">
                                            <col style="width:10%">
                                            <col style="width:10%">
                                            <col style="width:12%">
                                            <col style="width:9%">
                                        </colgroup>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Área</th>
                                            <th>Prioridad</th>
                                            <th>Fecha</th>
                                            <th>Fecha límite</th>
                                            <th>Estado</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listaActivas as $s): ?>
                                            <?php
                                                $clasePrioridad = match(strtolower($s->prioridad)) {
                                                    'alta'  => 'etiqueta-alta',
                                                    'media' => 'etiqueta-media',
                                                    'baja'  => 'etiqueta-baja',
                                                    default => ''
                                                };
                                                $claseEstado = match($s->id_estado) {
                                                    1 => 'etiqueta-pendiente',
                                                    2 => 'etiqueta-proceso',
                                                    4 => 'etiqueta-pendiente',
                                                    5 => 'etiqueta-cancelada',
                                                    default => ''
                                                };
                                                $puntoEstado = match($s->id_estado) {
                                                    1 => 'pendiente',
                                                    2 => 'proceso',
                                                    default => ''
                                                };
                                                $fecha = date("d/m/Y H:i:s", strtotime($s->fecha_creacion));
                                                $fechalimite = $s->fecha_fin_asignacion
                                                    ? date("d/m/Y H:i:s", strtotime($s->fecha_fin_asignacion))
                                                    : date("d/m/Y H:i:s", strtotime($s->fecha_limite));
                                                $pdfPath = null;
                                                if ($s->id_estado == 4 && $s->evidencia) {
                                                    $carpeta = dirname(explode(',', $s->evidencia)[0]);
                                                    $pdfPath = $carpeta . '/reporte_' . $s->id_bit . '.pdf';
                                                }
                                            ?>
                                            <tr>
                                                <td><span class="texto-apagado texto-xs">#<?= $s->id_sol ?></span></td>
                                                <td><strong><?= htmlspecialchars($s->encabezado) ?></strong></td>
                                                <td><?= htmlspecialchars($s->area) ?></td>
                                                <td><span class="etiqueta <?= $clasePrioridad ?>"><?= htmlspecialchars($s->prioridad) ?></span></td>
                                                <td class="texto-apagado"><?= $fecha ?></td>
                                                <td><?= $fechalimite ?></td>
                                                <td>
                                                    <span class="etiqueta <?= $claseEstado ?>">
                                                        <span class="punto-estado-solicitud <?= $puntoEstado ?>"></span>
                                                        <?= htmlspecialchars($s->estado) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($s->id_estado == 4): ?>
                                                        <div style="display:flex; gap:4px; flex-wrap:wrap; align-items:center;">
                                                            <?php if ($pdfPath): ?>
                                                                <a href="<?= htmlspecialchars($pdfPath) ?>" target="_blank"
                                                                   class="btn btn-fantasma btn-pequeno">Ver PDF</a>
                                                            <?php endif; ?>
                                                            <form action="php/controlador_solicitud.php" method="POST" style="margin:0;">
                                                                <input type="hidden" name="accion" value="aprobar">
                                                                <input type="hidden" name="id_sol" value="<?= $s->id_sol ?>">
                                                                <button type="submit" class="btn btn-exito btn-pequeno">Aprobar</button>
                                                            </form>
                                                            <button class="btn btn-peligro btn-pequeno"
                                                                    onclick="abrirModalRechazo(<?= $s->id_sol ?>)">Rechazar</button>
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
                                            <col style="width:6%">
                                            <col style="width:28%">
                                            <col style="width:15%">
                                            <col style="width:10%">
                                            <col style="width:10%">
                                            <col style="width:10%">
                                            <col style="width:12%">
                                            <col style="width:9%">
                                        </colgroup>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Área</th>
                                            <th>Prioridad</th>
                                            <th>Fecha</th>
                                            <th>Fecha límite</th>
                                            <th>Estado</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($listaCompletadas as $s): ?>
                                            <?php
                                                $clasePrioridad = match(strtolower($s->prioridad)) {
                                                    'alta'  => 'etiqueta-alta',
                                                    'media' => 'etiqueta-media',
                                                    'baja'  => 'etiqueta-baja',
                                                    default => ''
                                                };
                                                $fecha = date("d/m/Y H:i:s", strtotime($s->fecha_creacion));
                                                $fechalimite = $s->fecha_fin_asignacion
                                                    ? date("d/m/Y H:i:s", strtotime($s->fecha_fin_asignacion))
                                                    : date("d/m/Y H:i:s", strtotime($s->fecha_limite));
                                            ?>
                                            <tr>
                                                <td><span class="texto-apagado texto-xs">#<?= $s->id_sol ?></span></td>
                                                <td><strong><?= htmlspecialchars($s->encabezado) ?></strong></td>
                                                <td><?= htmlspecialchars($s->area) ?></td>
                                                <td><span class="etiqueta <?= $clasePrioridad ?>"><?= htmlspecialchars($s->prioridad) ?></span></td>
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
                    </div><!-- /mis-sol-contenido -->
                </div>
            </div>



        </div>
    </div>

    <!-- MODAL: RECHAZAR REPORTE -->
    <div id="modalRechazo" class="fondo-modal">
        <div class="modal" style="max-width:440px;">
            <div class="modal-encabezado">
                <div class="modal-titulo">Rechazar Reporte</div>
                <button class="modal-cerrar" onclick="cerrarModalRechazo()">✕</button>
            </div>
            <div class="modal-divisor"></div>
            <p style="font-size:13px; color:#4d5a7a; margin-bottom:16px;">
                Indica el motivo del rechazo. El trabajador recibirá esta razón y deberá enviar un nuevo reporte.
            </p>
            <form id="formRechazo" method="POST" action="php/controlador_solicitud.php">
                <input type="hidden" name="accion" value="rechazar">
                <input type="hidden" name="id_sol" id="rechazo-id-sol">
                <div class="grupo-form">
                    <label class="etiqueta-form" for="razon-rechazo">Motivo del rechazo</label>
                    <textarea class="campo-form" id="razon-rechazo" name="razon" rows="4"
                        placeholder="Describe por qué rechazas este reporte..." required></textarea>
                </div>
                <div class="modal-pie">
                    <button type="submit" class="btn btn-peligro">Confirmar Rechazo</button>
                    <button type="button" class="btn btn-fantasma" onclick="cerrarModalRechazo()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/comun.js"></script>
    <script src="js/usuarios.js"></script>
    <script src="js/polling.js"></script>
    <script>
        inicializarContadores();
        iniciarPolling({
            rol:        1,
            notifMaxId: <?= $initNotifMaxId ?>,
            solFp:      '<?= $initSolFp ?>',
            solCount:   <?= $totalSolicitudes ?>
        });
        <?php if (!empty($old['id_area'])): ?>
        document.getElementById('area').value = '<?= (int)$old['id_area'] ?>';
        <?php endif; ?>
        <?php if ($seccionActiva): ?>
        navegarSeccion('<?= htmlspecialchars($seccionActiva) ?>', titulosPagina);
        <?php endif; ?>
    </script>
    <script>
        function abrirModalRechazo(idSol) {
            document.getElementById('rechazo-id-sol').value = idSol;
            document.getElementById('modalRechazo').classList.add('abierto');
        }
        function cerrarModalRechazo() {
            document.getElementById('modalRechazo').classList.remove('abierto');
            document.getElementById('razon-rechazo').value = '';
        }
        document.getElementById('modalRechazo').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalRechazo();
        });
    </script>
</body>
</html>
