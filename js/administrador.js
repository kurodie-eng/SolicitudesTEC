var titulosSecciones = {
    gps:               'Ubicaciones',
    bitacora:          'Bitácora',
    'generar-reporte': 'Generar Reporte',
    'admin-usuarios':  'Administrar Usuarios',
    'admin-areas':     'Administrar Áreas'
};

inicializarNavegacion(titulosSecciones);

// Modal que cambia dependiendo de si se está agregando un usuario nuevo o editando a uno existente 
function openModal(action, id, nombre, app, apm, correo, idRol) {
    nombre = nombre || '';
    app    = app    || '';
    apm    = apm    || '';

    var title      = document.getElementById('modal-title');
    var inputId    = document.getElementById('user-id');
    var inputNota  = document.getElementById('password-nota');
    var inputPass  = document.getElementById('user-password');
    var inputPass2 = document.getElementById('user-password2');
    var accion     = document.querySelector('#userForm input[name="accion"]');

    // Agregar un nuevo usuario
    if (action === 'add') {
        title.textContent      = 'Agregar Usuario';
        accion.value           = 'agregar';
        inputId.value          = '';
        inputNota.style.display = 'none';
        inputPass.required     = true;
        inputPass2.required    = true;
        inputPass.minLength    = 8;
        inputPass2.minLength   = 8;

        document.getElementById('user-nombre').value    = '';
        document.getElementById('user-app').value       = '';
        document.getElementById('user-apm').value       = '';
        document.getElementById('user-correo').value    = '';
        inputPass.value                                  = '';
        inputPass2.value                                 = '';
        document.getElementById('user-role').value      = '';

    // Editar un usuario
    } else if (action === 'edit') {
        title.textContent      = 'Editar Usuario';
        accion.value           = 'editar';
        inputId.value          = id;
        inputNota.style.display = 'inline';
        inputPass.required     = false;
        inputPass2.required    = false;
        inputPass.minLength    = 0;
        inputPass2.minLength   = 0;

        document.getElementById('user-nombre').value    = nombre;
        document.getElementById('user-app').value       = app;
        document.getElementById('user-apm').value       = apm;
        document.getElementById('user-correo').value    = correo;
        inputPass.value                                  = '';
        inputPass2.value                                 = '';
        document.getElementById('user-role').value      = idRol;
    }

    document.getElementById('userModal').classList.add('abierto');
}

function closeModal() {
    document.getElementById('userModal').classList.remove('abierto');
}

document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Borrar un usuario
function deleteUser(id, nombre) {
    if (confirm('¿Eliminar a ' + nombre + '? Esta acción no se puede deshacer.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'php/controlador_usuario.php';

        var campoAccion = document.createElement('input');
        campoAccion.type  = 'hidden';
        campoAccion.name  = 'accion';
        campoAccion.value = 'eliminar';

        var campoId = document.createElement('input');
        campoId.type  = 'hidden';
        campoId.name  = 'id_us';
        campoId.value = id;

        form.appendChild(campoAccion);
        form.appendChild(campoId);
        document.body.appendChild(form);
        form.submit();
    }
}

// Filtrado de la bitácora
(function () {
    var inputBuscar  = document.getElementById("buscar-bitacora");
    var selectEstado = document.getElementById("filtro-estado-bitacora");
    var selectArea   = document.getElementById("filtro-area-bitacora");
    var selectTipo   = document.getElementById("filtro-tipo-bitacora");

    if (!inputBuscar || !selectEstado || !selectArea || !selectTipo) return;

    function filtrar() {
        var texto  = inputBuscar.value.toLowerCase().trim();
        var estado = selectEstado.value;
        var area   = selectArea.value;
        var tipo   = selectTipo.value;
        var hayResultados = false;
        var hayFilasDatos = false;

        document.querySelectorAll("#tabla-bitacora tr").forEach(function (fila) {
            if (fila.querySelector("td[colspan]")) return;

            hayFilasDatos = true;
            var textoDato  = (fila.dataset.texto  || "");
            var estadoDato = (fila.dataset.estado || "");
            var areaDato   = (fila.dataset.area   || "");
            var tipoDato   = (fila.dataset.tipo   || "");

            var ok = (!texto  || textoDato.includes(texto))
                  && (!estado || estadoDato === estado)
                  && (!area   || areaDato   === area)
                  && (!tipo   || tipoDato   === tipo);

            fila.style.display = ok ? "" : "none";
            if (ok) hayResultados = true;
        });

        var sinResultados = document.getElementById("bitacora-sin-resultados");
        if (hayFilasDatos) {
            if (!sinResultados) {
                sinResultados = document.createElement("tr");
                sinResultados.id = "bitacora-sin-resultados";
                sinResultados.innerHTML = '<td colspan="7" style="text-align:center;color:#8f98b2;">Sin resultados para los filtros aplicados.</td>';
                document.getElementById("tabla-bitacora").appendChild(sinResultados);
            }
            sinResultados.style.display = hayResultados ? "none" : "";
        } else if (sinResultados) {
            sinResultados.style.display = "none";
        }
    }

    inputBuscar.addEventListener("input", filtrar);
    selectEstado.addEventListener("change", filtrar);
    selectArea.addEventListener("change", filtrar);
    selectTipo.addEventListener("change", filtrar);
})();

// Filtrado de tabla de usuarios para la barra de búsqueda, después se pasará a comun.js
(function () {
    var inputBuscar = document.getElementById("buscar-usuario");
    var selectRol   = document.getElementById("filtro-rol");

    if (!inputBuscar || !selectRol) return;

    function filtrar() {
        var texto = inputBuscar.value.toLowerCase().trim();
        var rol   = selectRol.value.toLowerCase();

        document.querySelectorAll("#tabla-usuarios tr").forEach(function (fila) {
            if (fila.querySelector("td[colspan]")) {
                fila.style.display = "";
                return;
            }

            var nombre   = (fila.cells[0]?.textContent || "").toLowerCase();
            var correo   = (fila.cells[1]?.textContent || "").toLowerCase();
            var rolFila  = (fila.cells[2]?.textContent || "").toLowerCase();

            var coincideTexto = nombre.includes(texto) || correo.includes(texto);
            var coincideRol   = rol === "" || rolFila.includes(rol);

            fila.style.display = coincideTexto && coincideRol ? "" : "none";
        });
    }
    inputBuscar.addEventListener("input", filtrar);
    selectRol.addEventListener("change", filtrar);
})();

// ── Generar Reporte de Período ────────────────────────────────────────────────
(function () {
    var inicio        = document.getElementById('rp-fecha-inicio');
    var fin           = document.getElementById('rp-fecha-fin');
    var btn           = document.getElementById('btn-generar-reporte');
    var chkLimpiar    = document.getElementById('rp-limpiar');
    var labelLimpiar  = document.getElementById('rp-limpiar-label');
    var avisoBorrado  = document.getElementById('rp-aviso-borrado');

    if (!inicio || !fin || !btn || !chkLimpiar) return;

    function actualizar() {
        var fechasOk = inicio.value.trim() !== '' && fin.value.trim() !== '';
        btn.disabled = !fechasOk;

        var limpiar = chkLimpiar.checked;
        avisoBorrado.style.display  = limpiar ? '' : 'none';
        labelLimpiar.style.borderColor  = limpiar ? '#e53e3e' : '#e2e8f0';
        labelLimpiar.style.background   = limpiar ? '#fff5f5' : '#f8fafc';
    }

    inicio.addEventListener('change', actualizar);
    fin.addEventListener('change', actualizar);
    chkLimpiar.addEventListener('change', actualizar);
})();

function confirmarGenerarReporte() {
    var limpiar = document.getElementById('rp-limpiar').checked;
    var texto   = document.getElementById('modal-reporte-texto');
    var btnCont = document.getElementById('btn-modal-continuar');
    if (texto) {
        texto.innerHTML = limpiar
            ? 'Al generar el reporte, <strong>todos los registros de la base de datos serán eliminados</strong> y los archivos de evidencia borrados del servidor. Esta acción no se puede deshacer.<br><br>¿Desea continuar?'
            : '¿Desea generar el reporte PDF del período seleccionado?';
    }
    if (btnCont) {
        btnCont.className = limpiar ? 'btn btn-peligro' : 'btn btn-primario';
    }
    document.getElementById('modalConfirmarReporte').classList.add('abierto');
}

function cerrarModalReporte() {
    document.getElementById('modalConfirmarReporte').classList.remove('abierto');
}

function ejecutarGenerarReporte() {
    cerrarModalReporte();
    document.getElementById('form-reporte-periodo').submit();
}

// ── Modal de Áreas ────────────────────────────────────────────────────────────
function openAreaModal(action, id, nombre, idCategoria) {
    var title   = document.getElementById('area-modal-title');
    var accion  = document.querySelector('#areaForm input[name="accion"]');
    var inputId = document.getElementById('area-id');

    if (action === 'add') {
        title.textContent = 'Agregar Área';
        accion.value      = 'agregar';
        inputId.value     = '';
        document.getElementById('area-nombre').value    = '';
        document.getElementById('area-categoria').value = '';
    } else {
        title.textContent = 'Editar Área';
        accion.value      = 'editar';
        inputId.value     = id;
        document.getElementById('area-nombre').value    = nombre    || '';
        document.getElementById('area-categoria').value = idCategoria || '';
    }

    document.getElementById('areaModal').classList.add('abierto');
}

function closeAreaModal() {
    document.getElementById('areaModal').classList.remove('abierto');
}

(function () {
    var el = document.getElementById('areaModal');
    if (el) el.addEventListener('click', function (e) { if (e.target === this) closeAreaModal(); });
})();

// ── GPS / Ubicaciones ─────────────────────────────────────────────────────────
(function () {
    var mapa    = null;
    var markers = {};

    function escH(s) {
        var d = document.createElement('div');
        d.textContent = String(s || '');
        return d.innerHTML;
    }

    function timeAgo(epochSec) {
        if (!epochSec) return 'Nunca';
        var diff = Math.floor(Date.now() / 1000 - Number(epochSec));
        if (diff < 5)     return 'ahora';
        if (diff < 60)    return 'hace ' + diff + 's';
        if (diff < 3600)  return 'hace ' + Math.floor(diff / 60) + ' min';
        if (diff < 86400) return 'hace ' + Math.floor(diff / 3600) + ' h';
        return 'hace ' + Math.floor(diff / 86400) + ' día(s)';
    }

    function makeIcon(idRol) {
        var color = (idRol == 2) ? '#1a6fa3' : '#e25c00';
        return L.divIcon({
            className: '',
            html: '<div style="width:14px;height:14px;border-radius:50%;background:' + color + ';border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);"></div>',
            iconSize: [14, 14],
            iconAnchor: [7, 7],
            popupAnchor: [0, -12]
        });
    }

    function cargarUbicaciones() {
        fetch('php/api_ubicacion_admin.php')
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (usuarios) {
                actualizarMarcadores(usuarios);
                actualizarLista(usuarios);
                var el = document.getElementById('gps-ultima-actualizacion');
                if (el) el.textContent = 'Actualizado: ' + new Date().toLocaleTimeString('es-MX');
            })
            .catch(function () {});
    }

    function actualizarMarcadores(usuarios) {
        if (!mapa) return;
        var vistos = {};
        usuarios.forEach(function (u) {
            var lat = parseFloat(u.ultima_lat);
            var lng = parseFloat(u.ultima_lng);
            if (!u.ultima_lat || !u.ultima_lng || isNaN(lat) || isNaN(lng)) return;
            vistos[u.id_us] = true;

            var popup = '<strong>' + escH(u.nombre_completo) + '</strong><br>' +
                        '<span style="font-size:12px;color:#666;">' + escH(u.rol) + '</span><br>' +
                        '<span style="font-size:11px;color:#888;">' + timeAgo(u.ts_ubicacion) + '</span>';

            if (markers[u.id_us]) {
                markers[u.id_us].setLatLng([lat, lng]);
                markers[u.id_us].setPopupContent(popup);
            } else {
                var m = L.marker([lat, lng], { icon: makeIcon(u.id_rol) }).addTo(mapa);
                m.bindPopup(popup);
                markers[u.id_us] = m;
            }
        });
        Object.keys(markers).forEach(function (id) {
            if (!vistos[id]) {
                mapa.removeLayer(markers[id]);
                delete markers[id];
            }
        });
    }

    function actualizarLista(usuarios) {
        var lista = document.getElementById('lista-gps-usuarios');
        if (!lista) return;
        lista.innerHTML = '';
        if (!usuarios.length) {
            lista.innerHTML = '<div class="gps-cargando">Sin usuarios registrados.</div>';
            return;
        }
        usuarios.forEach(function (u) {
            var tieneUb    = u.ultima_lat && u.ultima_lng;
            var avatarBg   = (u.id_rol == 2) ? '#1a6fa3' : '#4d7a3d';
            var rolStyle   = (u.id_rol == 2)
                ? 'background:#e8f4fd;color:#1a6fa3;border-color:#b3d7f0;'
                : 'background:#e8f7e8;color:#2d6a2d;border-color:#9fd49f;';
            var iniciales  = u.nombre_completo.trim().split(/\s+/).slice(0, 2)
                               .map(function (w) { return w[0] || ''; }).join('').toUpperCase();
            var lat = parseFloat(u.ultima_lat);
            var lng = parseFloat(u.ultima_lng);

            var div = document.createElement('div');
            div.className = 'fila-gps-usuario' + (tieneUb ? ' con-ubicacion' : '');
            if (tieneUb && !isNaN(lat) && !isNaN(lng)) {
                (function (id, la, lo) {
                    div.onclick = function () {
                        mapa.setView([la, lo], 17, { animate: true });
                        if (markers[id]) markers[id].openPopup();
                    };
                })(u.id_us, lat, lng);
            }

            div.innerHTML =
                '<div class="gps-avatar" style="background:' + avatarBg + ';">' + escH(iniciales) + '</div>' +
                '<div class="gps-info">' +
                    '<div class="gps-nombre">' + escH(u.nombre_completo) + '</div>' +
                    '<div class="gps-meta">' +
                        '<span class="etiqueta" style="font-size:11px;' + rolStyle + '">' + escH(u.rol) + '</span>' +
                        '<span class="texto-apagado" style="font-size:12px;">Última conexión: ' + timeAgo(u.ts_conexion) + '</span>' +
                        (tieneUb
                            ? '<span class="texto-apagado" style="font-size:12px;">  Ubicación actualizada: ' + timeAgo(u.ts_ubicacion) + '</span>'
                            : '<span class="texto-apagado" style="font-size:12px;">  Sin ubicación</span>') +
                    '</div>' +
                '</div>' +
                (tieneUb
                    ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#8f98b2" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>'
                    : '');
            lista.appendChild(div);
        });
    }

    function initMap() {
        if (typeof L === 'undefined') return;
        var contenedor = document.getElementById('mapa-gps');
        if (!contenedor) return;
        if (mapa) { mapa.invalidateSize(); return; }
        mapa = L.map('mapa-gps').setView([21.9374, -100.0024], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(mapa);
        cargarUbicaciones();
        setInterval(cargarUbicaciones, 30000);
    }

    // Observar cuándo la sección GPS se vuelve visible (display != 'none')
    var secGPS = document.getElementById('gps');
    if (secGPS && window.MutationObserver) {
        var obs = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.attributeName === 'style' && secGPS.style.display !== 'none') {
                    requestAnimationFrame(initMap);
                }
            });
        });
        obs.observe(secGPS, { attributes: true, attributeFilter: ['style'] });
    }

    // Fallback: click directo en el nav link
    var navGPS = document.querySelector('.nav-link[data-section="gps"]');
    if (navGPS) {
        navGPS.addEventListener('click', function () { setTimeout(initMap, 150); });
    }
})();

function deleteArea(id, nombre) {
    if (!confirm('¿Eliminar el área "' + nombre + '"? Esta acción no se puede deshacer.')) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'php/controlador_area.php';

    var fAccion = document.createElement('input');
    fAccion.type = 'hidden'; fAccion.name = 'accion'; fAccion.value = 'eliminar';

    var fId = document.createElement('input');
    fId.type = 'hidden'; fId.name = 'id_area'; fId.value = id;

    form.appendChild(fAccion);
    form.appendChild(fId);
    document.body.appendChild(form);
    form.submit();
}