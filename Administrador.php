<?php
session_start();
if (empty($_SESSION["id"]) || !is_numeric($_SESSION["id"]) || $_SESSION["id_rol"] != 3) {
    header("Location: index.php");
    exit();
}

// Este tipo de mensajes son los flash. Se envian para su lectura y después de cumplir su función son obliterados
$msgExito = $_SESSION["exito"] ?? null;
$msgError = $_SESSION["error"] ?? null;
$seccionActiva = $_SESSION["seccion_activa"] ?? null;
unset($_SESSION["exito"], $_SESSION["error"], $_SESSION["seccion_activa"]);

require_once "php/conexion.php";

$stmtUsuarios = $conexion->prepare(
    "SELECT u.id_us, u.nombre, u.app, u.apm, u.correo, u.contrasena, u.disponible,
        u.id_rol, r.nombre AS rol
    FROM usuario u
    JOIN rol r ON u.id_rol = r.id_rol
    WHERE u.id_us != ? AND u.id_rol != 3
    ORDER BY u.nombre ASC"
);
$stmtUsuarios->bind_param("i", $_SESSION["id"]);
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->get_result();
$stmtUsuarios->close();

// Bitácora: todas las solicitudes con info de asignación y reporte
$stmtBitacora = $conexion->prepare(
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
$stmtBitacora->execute();
$registrosBitacora = $stmtBitacora->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtBitacora->close();

$stmtAreas   = $conexion->query("SELECT id_area, nombre FROM area ORDER BY nombre ASC");
$areas       = $stmtAreas->fetch_all(MYSQLI_ASSOC);
$stmtEstados = $conexion->query("SELECT id_estado, nombre FROM estado_solicitud ORDER BY id_estado ASC");
$estadosFiltro = $stmtEstados->fetch_all(MYSQLI_ASSOC);

// Áreas agrupadas por categoría
$qAreasCat = $conexion->query(
    "SELECT c.id_categoria, c.nombre AS cat_nombre,
            a.id_area, a.nombre AS area_nombre
     FROM categoriaArea c
     LEFT JOIN area a ON a.id_categoria = c.id_categoria
     ORDER BY c.nombre ASC, a.nombre ASC"
);
$categorias = [];
while ($row = $qAreasCat->fetch_object()) {
    if (!isset($categorias[$row->id_categoria])) {
        $categorias[$row->id_categoria] = ['nombre' => $row->cat_nombre, 'areas' => []];
    }
    if ($row->id_area !== null) {
        $categorias[$row->id_categoria]['areas'][] = [
            'id'     => $row->id_area,
            'nombre' => $row->area_nombre,
        ];
    }
}
$qCatLista      = $conexion->query("SELECT id_categoria, nombre FROM categoriaArea ORDER BY nombre ASC");
$listaCategorias = $qCatLista->fetch_all(MYSQLI_ASSOC);
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Solicitudes - Administrador — ITSRV</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
                <!-- El administrador. En color para que se distinga de entre los demás -->
                <div class="usuario-avatar" style="background-color:#3d6bbf;">AD</div>
                <div>
                    <div class="usuario-nombre">
                        <?php echo $_SESSION["nombre"]. " " .$_SESSION["app"]; ?>
                    </div>
                    <div class="usuario-rol">Control total</div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-etiqueta-seccion">Ubicaciones</div>
            <a href="#" class="nav-link nav-item" data-section="gps">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="nav-texto">GPS</span>
            </a>
            <div class="nav-etiqueta-seccion">Bitácora</div>
            <a href="#" class="nav-link nav-item active" data-section="bitacora">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span class="nav-texto">Bitácora</span>
            </a>
            <a href="#" class="nav-link nav-item" data-section="generar-reporte">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="nav-texto">Generar Reporte</span>
            </a>
            <div class="nav-etiqueta-seccion">Administración</div>
            <a href="#" class="nav-link nav-item" data-section="admin-usuarios">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span class="nav-texto">Administrar Usuarios</span>
            </a>
            <a href="#" class="nav-link nav-item" data-section="admin-areas">
                <svg class="nav-icono" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span class="nav-texto">Administrar Áreas</span>
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
                    <div class="topbar-titulo" id="topbar-titulo">Bitácora</div>
                    <div class="topbar-subtitulo">Instituto Tecnológico Superior de Rioverde</div>
                </div>
            </div>
        </header>

        <div class="cuerpo-pagina">

            <!-- El mensaje flash solo aparece si el controlador dejó un mensaje en sesión -->
            <?php if ($msgExito): ?>
                <div class="alerta alerta-exito"><?= htmlspecialchars($msgExito) ?></div>
            <?php endif; ?>
            <?php if ($msgError): ?>
                <div class="alerta alerta-error"><?= htmlspecialchars($msgError) ?></div>
            <?php endif; ?>

            <!-- GPS / UBICACIONES -->
            <div id="gps" class="section" style="display:none;">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Mapa de ubicaciones</div>
                        <div style="display:flex; gap:16px; align-items:center; font-size:13px; color:#4d5a7a;">
                            <span style="display:flex; align-items:center; gap:6px;">
                                <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:#1a6fa3; border:2px solid #fff; box-shadow:0 1px 3px rgba(0,0,0,.3);"></span>Trabajador
                            </span>
                            <span style="display:flex; align-items:center; gap:6px;">
                                <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:#e25c00; border:2px solid #fff; box-shadow:0 1px 3px rgba(0,0,0,.3);"></span>Solicitante
                            </span>
                        </div>
                    </div>
                    <div id="mapa-gps" class="mapa-gps-contenedor"></div>
                </div>
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Usuarios</div>
                        <span id="gps-ultima-actualizacion" class="texto-apagado" style="font-size:13px;">—</span>
                    </div>
                    <div id="lista-gps-usuarios" class="lista-gps-usuarios">
                        <div class="gps-cargando">Navega a esta sección para cargar.</div>
                    </div>
                </div>
            </div>

            <!-- BITÁCORA -->
            <div id="bitacora" class="section active">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Bitácora de solicitudes</div>
                        <span id="bitacora-contador" class="texto-apagado" style="font-size:13px;"><?= count($registrosBitacora) ?> registro(s)</span>
                    </div>
                    <div class="barra-herramientas">
                        <div class="campo-busqueda">
                            <input class="campo-form" type="text" id="buscar-bitacora"
                                   placeholder="Buscar por solicitud, solicitante o técnico...">
                        </div>
                        <select class="campo-form" id="filtro-estado-bitacora" style="width:auto; min-width:150px;">
                            <option value="">Todos los estados</option>
                            <?php foreach ($estadosFiltro as $est): ?>
                                <option value="<?= htmlspecialchars($est['nombre']) ?>">
                                    <?= htmlspecialchars($est['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="campo-form" id="filtro-area-bitacora" style="width:auto; min-width:150px;">
                            <option value="">Todas las áreas</option>
                            <?php foreach ($areas as $a): ?>
                                <option value="<?= $a['id_area'] ?>">
                                    <?= htmlspecialchars($a['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select class="campo-form" id="filtro-tipo-bitacora" style="width:auto; min-width:170px;">
                            <option value="">Todos los tipos</option>
                            <option value="Correctiva">Correctiva</option>
                            <option value="Preventiva">Preventiva</option>
                            <option value="Soporte Técnico">Soporte Técnico</option>
                        </select>
                    </div>
                    <div class="contenedor-tabla">
                        <table>
                            <thead>
                                <tr>
                                    <th>Solicitud</th>
                                    <th>Solicitante</th>
                                    <th>Técnico</th>
                                    <th>Estado / Prioridad</th>
                                    <th>Tipo de Acción</th>
                                    <th>Fecha</th>
                                    <th>Reporte</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-bitacora">
                            <?php if (empty($registrosBitacora)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; color:#8f98b2;">
                                        No hay solicitudes registradas.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registrosBitacora as $r):
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
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- GENERAR REPORTE -->
            <div id="generar-reporte" class="section" style="display:none;">
                <div class="tarjeta" style="max-width:560px;">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Generar Reporte de Período</div>
                    </div>
                    <div class="tarjeta-cuerpo">
                        <form id="form-reporte-periodo"
                              action="php/generar_reporte_admin.php"
                              method="POST"
                              target="_blank">
                            <div class="fila-form">
                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="rp-fecha-inicio">Inicio del período</label>
                                    <input class="campo-form" type="date" id="rp-fecha-inicio" name="fecha_inicio">
                                </div>
                                <div class="grupo-form">
                                    <label class="etiqueta-form" for="rp-fecha-fin">Fin del período</label>
                                    <input class="campo-form" type="date" id="rp-fecha-fin" name="fecha_fin">
                                </div>
                            </div>

                            <label id="rp-limpiar-label" for="rp-limpiar"
                                   style="display:flex; align-items:flex-start; gap:12px; padding:14px 16px;
                                          border-radius:8px; border:2px solid #e2e8f0; cursor:pointer;
                                          margin-bottom:16px; background:#f8fafc; transition:border-color .2s, background .2s;">
                                <input type="checkbox" id="rp-limpiar" name="limpiar_bd" value="1"
                                       style="margin-top:2px; width:16px; height:16px; flex-shrink:0; accent-color:#c0392b;">
                                <span>
                                    <strong style="font-size:13px;">Limpiar base de datos al generar</strong><br>
                                    <span style="font-size:12px; color:#6b7280; line-height:1.5;">
                                        Elimina todos los registros de solicitudes, asignaciones y bitácora,
                                        así como los archivos de evidencia del servidor.
                                    </span>
                                </span>
                            </label>

                            <div id="rp-aviso-borrado"
                                 style="background:#fdecea; border:1px solid #f5c2c2; border-radius:8px;
                                        padding:12px 16px; margin-bottom:16px; display:none;">
                                <p style="font-size:13px; color:#7a2020; line-height:1.6; margin:0;">
                                    <strong>⚠ Acción irreversible.</strong> Los registros serán eliminados
                                    inmediatamente después de generar el PDF. Actualiza la página tras la descarga.
                                </p>
                            </div>

                            <button type="button" id="btn-generar-reporte"
                                    class="btn btn-primario" disabled
                                    onclick="confirmarGenerarReporte()">
                                Generar Reporte PDF
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ADMINISTRAR ÁREAS -->
            <div id="admin-areas" class="section" style="display:none;">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Administrar Áreas</div>
                        <button class="btn btn-primario btn-pequeno" onclick="openAreaModal('add')">
                            + Agregar Área
                        </button>
                    </div>
                    <div class="contenedor-tabla">
                        <table>
                            <?php foreach ($categorias as $id_cat => $cat): ?>
                                <tbody>
                                    <tr>
                                        <td colspan="2" style="background:#f0f4f8; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#8f98b2; padding:8px 14px; border-bottom:1px solid #dde2ec;">
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </td>
                                    </tr>
                                    <?php if (empty($cat['areas'])): ?>
                                        <tr>
                                            <td colspan="2" style="color:#8f98b2; font-size:13px; padding:10px 14px; font-style:italic;">
                                                Sin áreas registradas en esta categoría.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($cat['areas'] as $a): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($a['nombre']) ?></td>
                                                <td style="width:1px; white-space:nowrap;">
                                                    <div class="acciones-tabla">
                                                        <button class="btn btn-advertencia btn-pequeno"
                                                                onclick="openAreaModal('edit', <?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['nombre'])) ?>, <?= $id_cat ?>)">
                                                            Editar
                                                        </button>
                                                        <button class="btn btn-peligro btn-pequeno"
                                                                onclick="deleteArea(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['nombre'])) ?>)">
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ADMINISTRAR USUARIOS -->
            <div id="admin-usuarios" class="section" style="display:none;">
                <div class="tarjeta">
                    <div class="tarjeta-encabezado">
                        <div class="tarjeta-titulo">Administrar Usuarios</div>
                        <!-- Se abre el modal para  agregar usuarios nuevos -->
                        <button class="btn btn-primario btn-pequeno add-user" onclick="openModal('add')">Agregar Usuario</button>
                    </div>
                    <!-- Busqueda y filtro de roles para que el admin encuentre usuarios específicos -->
                    <div class="barra-herramientas">
                        <div class="campo-busqueda">
                            <input class="campo-form" type="text" id="buscar-usuario" placeholder="Buscar usuario...">
                        </div>
                        <select class="campo-form" id="filtro-rol" style="width:auto; min-width:140px;">
                            <option value="">Todos los roles</option>
                            <option value="trabajador">Trabajador</option>
                            <option value="solicitante">Solicitante</option>
                        </select>
                    </div>
                    <div class="contenedor-tabla">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Disponible</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <!-- Se hace un filtro de filas -->
                            <tbody id="tabla-usuarios">
                            <?php if ($usuarios->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; color:#8f98b2;">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($u = $usuarios->fetch_object()): ?>
                                    <?php
                                        $iniciales = strtoupper(substr($u->nombre, 0, 1) . substr($u->app, 0, 1));
                                        $nombreCompleto = htmlspecialchars($u->nombre . " " . $u->app . ($u->apm ? " " . $u->apm : ""));
                                        $claseRol = $u->rol === "Trabajador" ? "etiqueta-proceso" : "etiqueta-pendiente";
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="avatar-fila-tabla"><?= $iniciales ?></div>
                                                <strong><?= $nombreCompleto ?></strong>
                                            </div>
                                        </td>
                                        <td class="texto-apagado"><?= htmlspecialchars($u->correo) ?></td>
                                        <td><span class="etiqueta <?= $claseRol ?>"><?= htmlspecialchars($u->rol) ?></span></td>
                                        <td>
                                            <?php if ($u->disponible): ?>
                                                <span class="etiqueta etiqueta-completada">Sí</span>
                                            <?php else: ?>
                                                <span class="etiqueta etiqueta-cancelada">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="acciones-tabla">
                                                <!-- Todos los datos del usuario se pasan al JS para pre-rellenar el modal sin necesidad de un fetch -->
                                                <button class="btn btn-advertencia btn-pequeno"
                                                        onclick="openModal('edit', <?= $u->id_us ?>, '<?= htmlspecialchars($u->nombre) ?>', '<?= htmlspecialchars($u->app) ?>', '<?= htmlspecialchars($u->apm ?? "") ?>', '<?= htmlspecialchars($u->correo) ?>', <?= $u->id_rol ?>)">
                                                    Editar
                                                </button>
                                                <button class="btn btn-peligro btn-pequeno"
                                                        onclick="deleteUser(<?= $u->id_us ?>, '<?= $nombreCompleto ?>')">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL: AGREGAR / EDITAR USUARIO  -->
    <div id="userModal" class="fondo-modal">
        <div class="modal">
            <div class="modal-encabezado">
                <!-- JS cambia este texto entre "Agregar Usuario" y "Editar Usuario" -->
                <div class="modal-titulo" id="modal-title">Agregar Usuario</div>
                <button class="modal-cerrar" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-divisor"></div>

            <form id="userForm" method="POST" action="php/controlador_usuario.php">
                <input type="hidden" name="accion" value="agregar">
                <input type="hidden" name="id_us" id="user-id">

                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-nombre">Nombre</label>
                    <input class="campo-form" type="text" id="user-nombre" name="nombre" maxlength="50" required>
                </div>

                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-app">Apellido Paterno</label>
                    <input class="campo-form" type="text" id="user-app" name="app" maxlength="50" required>
                </div>

                <!-- Opcional: Es decir, no incluye el atributo required que obliga a rellenar ese input -->
                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-apm">Apellido Materno</label>
                    <input class="campo-form" type="text" id="user-apm" name="apm" maxlength="50" placeholder="Opcional">
                </div>

                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-correo">Correo electrónico</label>
                    <input class="campo-form" type="email" id="user-correo" name="correo" maxlength="100" required>
                </div>

                <!-- La nota se muestra en modo edición -->
                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-password">Contraseña</label>
                    <small id="password-nota" style="color:#8f98b2; display:none; font-size:10px;">
                        Dejar vacío para no cambiar
                    </small>
                    <input class="campo-form" type="password" id="user-password" name="password" maxlength="255" required>
                </div>

                <!-- Se valida si ambas contraseñas coinciden.  -->
                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-password2">Confirmar Contraseña</label>
                    <input class="campo-form" type="password" id="user-password2" name="password2" maxlength="255" required>
                </div>

                <div class="grupo-form">
                    <label class="etiqueta-form" for="user-role">Rol</label>
                    <select class="campo-form" id="user-role" name="id_rol" required>
                        <option value="" disabled selected>Seleccionar...</option>
                        <option value="1">Solicitante</option>
                        <option value="2">Trabajador</option>
                    </select>
                </div>

                <div class="modal-pie">
                    <button type="submit" class="btn btn-primario">Guardar</button>
                    <button type="button" class="btn btn-fantasma" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="js/comun.js"></script>
    <script src="js/polling.js"></script>
    <script src="js/administrador.js"></script>

    <?php if ($seccionActiva): ?>
    <script>
        navegarSeccion("<?= htmlspecialchars($seccionActiva) ?>", titulosSecciones);
    </script>
    <?php endif; ?>

    <?php
    $bitFpRows = $conexion->query(
        "SELECT GROUP_CONCAT(
             CONCAT(s.id_sol,':',s.id_estado,':',COALESCE(b.id_bit,0),':',COALESCE(b.tipo_accion,''))
             ORDER BY s.id_sol
         ) AS fp
         FROM solicitud s
         LEFT JOIN bitacora b ON b.id_bit = (
             SELECT MAX(id_bit) FROM bitacora WHERE id_sol = s.id_sol
         )"
    )->fetch_object();
    $bitFp = md5($bitFpRows->fp ?? '');
    $notifMaxId = (int)($conexion->query(
        "SELECT COALESCE(MAX(id_not), 0) AS mx FROM notificacion WHERE id_us = " . (int)$_SESSION['id']
    )->fetch_object()->mx ?? 0);
    ?>
    <script>
        iniciarPolling({ rol: 3, notifMaxId: <?= $notifMaxId ?>, bitFp: '<?= $bitFp ?>' });
    </script>

    <!-- MODAL: AGREGAR / EDITAR ÁREA -->
    <div id="areaModal" class="fondo-modal">
        <div class="modal" style="max-width:400px;">
            <div class="modal-encabezado">
                <div class="modal-titulo" id="area-modal-title">Agregar Área</div>
                <button class="modal-cerrar" onclick="closeAreaModal()">✕</button>
            </div>
            <div class="modal-divisor"></div>
            <form id="areaForm" method="POST" action="php/controlador_area.php">
                <input type="hidden" name="accion" value="agregar">
                <input type="hidden" name="id_area" id="area-id">

                <div class="grupo-form">
                    <label class="etiqueta-form" for="area-nombre">Nombre del Área</label>
                    <input class="campo-form" type="text" id="area-nombre" name="nombre" maxlength="50" required>
                </div>

                <div class="grupo-form">
                    <label class="etiqueta-form" for="area-categoria">Categoría</label>
                    <select class="campo-form" id="area-categoria" name="id_categoria" required>
                        <option value="" disabled selected>Seleccionar...</option>
                        <?php foreach ($listaCategorias as $lc): ?>
                            <option value="<?= $lc['id_categoria'] ?>">
                                <?= htmlspecialchars($lc['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-pie">
                    <button type="submit" class="btn btn-primario">Guardar</button>
                    <button type="button" class="btn btn-fantasma" onclick="closeAreaModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: confirmación de generar reporte de período -->
    <div id="modalConfirmarReporte" class="fondo-modal">
        <div class="modal" style="max-width:440px;">
            <div class="modal-encabezado">
                <div class="modal-titulo">Confirmar generación</div>
                <button class="modal-cerrar" onclick="cerrarModalReporte()">✕</button>
            </div>
            <div class="modal-divisor"></div>
            <div style="padding:20px 24px 8px;">
                <p id="modal-reporte-texto" style="color:#4a5568; line-height:1.6; font-size:14px; margin:0;">
                    ¿Deseas generar el reporte PDF del período seleccionado?
                </p>
            </div>
            <div class="modal-pie">
                <button type="button" id="btn-modal-continuar" class="btn btn-primario" onclick="ejecutarGenerarReporte()">Continuar</button>
                <button type="button" class="btn btn-fantasma" onclick="cerrarModalReporte()">Cancelar</button>
            </div>
        </div>
    </div>
</body>
</html>