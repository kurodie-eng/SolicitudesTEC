(function () {
    var INTERVALO = 20000;

    window.iniciarPolling = function (config) {
        var estado = {
            notifMaxId: config.notifMaxId || 0,
            solMaxId:   config.solMaxId   || 0,
            solCount:   config.solCount   || 0,
            solFp:      config.solFp      || '',
            asgFp:      config.asgFp      || '',
            bitFp:      config.bitFp      || ''
        };

        function escHtml(s) {
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function parpadear(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.style.transition = 'background-color 0.4s ease';
            el.style.backgroundColor = 'rgba(27,85,45,0.07)';
            setTimeout(function () { el.style.backgroundColor = ''; }, 1200);
        }

        function actualizarContadorNav(dataSection, count) {
            var link = document.querySelector('.nav-link[data-section="' + dataSection + '"]');
            if (!link) return;
            var ctr = link.querySelector('.nav-contador');
            if (count <= 0) { if (ctr) ctr.remove(); return; }
            if (!ctr) {
                ctr = document.createElement('span');
                ctr.className = 'nav-contador';
                link.appendChild(ctr);
            }
            ctr.textContent = count;
        }

        function actualizarBadgeNotif(count) {
            var boton = document.getElementById('notif-boton');
            if (!boton) return;
            var badge = boton.querySelector('.notif-badge');
            if (count <= 0) { if (badge) badge.remove(); return; }
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notif-badge';
                boton.appendChild(badge);
            }
            badge.textContent = count;
        }

        function procesarNotifsNuevas(data) {
            if (!data.notificaciones_nuevas || !data.notificaciones_nuevas.length) return;
            var lista = document.querySelector('.notif-lista');
            if (!lista) return;
            var vacio = lista.querySelector('.notif-vacio');
            if (vacio) vacio.remove();
            data.notificaciones_nuevas.forEach(function (n) {
                if (document.getElementById('notif-' + n.id_not)) return;
                var div = document.createElement('div');
                div.className = 'notif-item';
                div.id = 'notif-' + n.id_not;
                div.innerHTML =
                    '<div class="notif-mensaje">' + escHtml(n.mensaje) + '</div>' +
                    '<div class="notif-meta">' +
                        '<span class="notif-fecha">' + escHtml(n.fecha_fmt) + '</span>' +
                        '<button class="notif-eliminar" onclick="eliminarNotificacion(' + n.id_not + ')" title="Eliminar">' +
                            '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                            '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>' +
                            '<path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>' +
                        '</button>' +
                    '</div>';
                lista.insertBefore(div, lista.firstChild);
            });
            actualizarBadgeNotif(data.notif_count);
            estado.notifMaxId = data.notif_max_id;
        }

        function refrescarSeccion(id, url, callback) {
            fetch(url)
                .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
                .then(function (html) {
                    var el = document.getElementById(id);
                    if (el) { el.innerHTML = html; parpadear(id); }
                    if (callback) callback();
                })
                .catch(function () {});
        }

        setInterval(function () {
            fetch('php/api_polling.php?desde_notif=' + estado.notifMaxId)
                .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                .then(function (data) {

                    if (data.notif_max_id > estado.notifMaxId) {
                        procesarNotifsNuevas(data);
                    }

                    if (config.rol === 2) {
                        var solCambio = data.sol_count !== estado.solCount || data.sol_max_id !== estado.solMaxId;
                        if (solCambio) {
                            estado.solCount = data.sol_count;
                            estado.solMaxId = data.sol_max_id;
                            actualizarContadorNav('solicitudes', data.sol_count);
                            refrescarSeccion('tablon-contenido', 'php/partial_tablon.php');
                        }
                        if (data.asg_fingerprint !== estado.asgFp) {
                            estado.asgFp = data.asg_fingerprint;
                            actualizarContadorNav('mis-asignaciones', data.asg_activas);
                            refrescarSeccion('asignaciones-contenido', 'php/partial_asignaciones.php');
                        }
                    }

                    if (config.rol === 1) {
                        if (data.sol_fingerprint !== estado.solFp) {
                            estado.solFp    = data.sol_fingerprint;
                            estado.solCount = data.sol_count;
                            actualizarContadorNav('creadas', data.sol_activas);
                            refrescarSeccion('mis-sol-contenido', 'php/partial_mis_solicitudes.php');
                        }
                    }

                    if (config.rol === 3) {
                        if (data.bit_fingerprint !== estado.bitFp) {
                            estado.bitFp = data.bit_fingerprint;
                            fetch('php/partial_bitacora.php')
                                .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
                                .then(function (html) {
                                    var tbody = document.getElementById('tabla-bitacora');
                                    if (!tbody) return;
                                    // Preserve active filters by re-applying after refresh
                                    tbody.innerHTML = html;
                                    parpadear('tabla-bitacora');
                                    // Update record count badge
                                    var contadorEl = document.getElementById('bitacora-contador');
                                    if (contadorEl) {
                                        var filas = tbody.querySelectorAll('tr:not([id])');
                                        var hayVacia = tbody.querySelector('td[colspan]');
                                        contadorEl.textContent = hayVacia ? '0 registro(s)' : filas.length + ' registro(s)';
                                    }
                                    // Re-trigger filters if any are active
                                    var evt = new Event('change');
                                    var filtros = ['filtro-estado-bitacora', 'filtro-area-bitacora', 'filtro-tipo-bitacora'];
                                    filtros.forEach(function (id) {
                                        var el = document.getElementById(id);
                                        if (el && el.value) el.dispatchEvent(evt);
                                    });
                                    var buscar = document.getElementById('buscar-bitacora');
                                    if (buscar && buscar.value.trim()) buscar.dispatchEvent(new Event('input'));
                                })
                                .catch(function () {});
                        }
                    }
                })
                .catch(function () {});
        }, INTERVALO);
    };
})();
