// Sidebar colapsable / overlay móvil

function cerrarSidebarMobile() {
    var sidebar = document.querySelector('.sidebar');
    var ov = document.getElementById('sidebar-overlay');
    if (sidebar) sidebar.classList.remove('sidebar-abierta');
    if (ov) ov.classList.remove('visible');
}

function toggleSidebar() {
    if (window.innerWidth <= 768) {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        var abierta = sidebar.classList.toggle('sidebar-abierta');
        var ov = document.getElementById('sidebar-overlay');
        if (ov) ov.classList.toggle('visible', abierta);
    } else {
        var colapsada = document.body.classList.toggle('sidebar-colapsada');
        try { localStorage.setItem('sidebarColapsada', colapsada ? '1' : '0'); } catch (e) {}
    }
}

(function () {
    try {
        if (window.innerWidth > 768 && localStorage.getItem('sidebarColapsada') === '1') {
            document.body.classList.add('sidebar-colapsada');
        }
    } catch (e) {}

    var ov = document.createElement('div');
    ov.id        = 'sidebar-overlay';
    ov.className = 'sidebar-overlay';
    ov.onclick   = cerrarSidebarMobile;
    document.body.appendChild(ov);

    try {
        window.matchMedia('(max-width: 768px)').addEventListener('change', function (e) {
            if (e.matches) {
                document.body.classList.remove('sidebar-colapsada');
                cerrarSidebarMobile();
            } else {
                cerrarSidebarMobile();
                try {
                    if (localStorage.getItem('sidebarColapsada') === '1') {
                        document.body.classList.add('sidebar-colapsada');
                    }
                } catch (e2) {}
            }
        });
    } catch (e) {}
})();

// Navegación de secciones en las páginas.
function navegarSeccion(idSeccion, titulosPagina) {
    document.querySelectorAll('.fondo-modal').forEach(function(m) {
        m.classList.remove('abierto');
    });

    document.querySelectorAll('.section').forEach(function(seccion) {
        seccion.style.display = 'none';
    });

    document.getElementById(idSeccion).style.display = '';

    document.querySelectorAll('.nav-link').forEach(function(nav) {
        nav.classList.remove('active');
    });

    var navActivo = document.querySelector('.nav-link[data-section="' + idSeccion + '"]');
    if (navActivo) navActivo.classList.add('active');

    var topbarTitulo = document.getElementById('topbar-titulo');
    if (topbarTitulo && titulosPagina) {
        topbarTitulo.textContent = titulosPagina[idSeccion] || '';
    }

    cerrarSidebarMobile();
}

// Evita envíos duplicados: desactiva el botón submit tras el primer click
document.addEventListener('submit', function (e) {
    var btn = e.target.querySelector('button[type="submit"]');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    btn.textContent = 'Enviando…';
}, true);

function inicializarContadores() {
    document.querySelectorAll('input[maxlength], textarea[maxlength]').forEach(function(el) {
        var grupo = el.closest('.grupo-form');
        var contador = grupo ? grupo.querySelector('.contador-chars') : null;
        if (!contador) return;
        var max = parseInt(el.getAttribute('maxlength'));

        function actualizar() {
            var restantes = max - el.value.length;
            contador.textContent = '(' + restantes + ' caracteres restantes)';
            contador.style.color = restantes <= 10 ? '#e74c3c' : '#8f98b2';
        }

        el.addEventListener('input', actualizar);
        actualizar();
    });
}

function toggleNotificaciones() {
    var panel = document.getElementById('notif-panel');
    if (panel) panel.classList.toggle('abierto');
}

document.addEventListener('click', function(e) {
    var panel = document.getElementById('notif-panel');
    var boton = document.getElementById('notif-boton');
    if (!panel || !boton) return;
    if (!panel.contains(e.target) && !boton.contains(e.target)) {
        panel.classList.remove('abierto');
    }
});

function eliminarNotificacion(id) {
    var formData = new FormData();
    formData.append('id_not', id);

    fetch('php/controlador_notificacion.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.ok) return;

            var el = document.getElementById('notif-' + id);
            if (el) el.remove();

            var badge = document.querySelector('.notif-badge');
            if (badge) {
                var n = parseInt(badge.textContent) - 1;
                if (n <= 0) badge.remove();
                else badge.textContent = n;
            }

            var lista = document.querySelector('.notif-lista');
            if (lista && !lista.querySelector('.notif-item')) {
                lista.innerHTML = '<div class="notif-vacio">Sin notificaciones nuevas</div>';
            }
        })
        .catch(function() {});
}

function inicializarNavegacion(titulosPagina) {
    document.querySelectorAll('.nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            navegarSeccion(this.getAttribute('data-section'), titulosPagina);
        });
    });
}

// ── Geolocalización ───────────────────────────────────────────────────────────
(function () {
    var watchId = null;

    var modalGeo = document.createElement('div');
    modalGeo.id        = 'modal-geo-bloqueado';
    modalGeo.className = 'fondo-modal fondo-modal-geo';
    modalGeo.innerHTML =
        '<div class="modal" style="max-width:420px; text-align:center;">' +
            '<div class="modal-encabezado" style="justify-content:center;">' +
                '<div class="modal-titulo">Permiso de ubicación requerido</div>' +
            '</div>' +
            '<div class="modal-divisor"></div>' +
            '<div style="padding:16px 0 8px;">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="none" viewBox="0 0 24 24" stroke="#e25c00" stroke-width="1.5">' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>' +
                    '<path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>' +
                '</svg>' +
            '</div>' +
            '<p style="font-size:14px; color:#4d5a7a; margin-bottom:10px;">El sistema requiere acceso a tu ubicación. Este permiso es obligatorio.</p>' +
            '<p style="font-size:12px; color:#8f98b2; margin-bottom:20px; line-height:1.6;">Si bloqueaste el permiso, ve a <strong>Configuración del navegador → Permisos del sitio → Ubicación</strong>, permite el acceso a esta página y presiona <em>Reintentar</em>.</p>' +
            '<div class="modal-pie" style="justify-content:center;">' +
                '<button class="btn btn-primario" id="btn-reintentar-geo">Reintentar</button>' +
            '</div>' +
        '</div>';
    document.body.appendChild(modalGeo);

    function enviarUbicacion(lat, lng) {
        var fd = new FormData();
        fd.append('accion', 'ubicacion');
        fd.append('lat', lat);
        fd.append('lng', lng);
        fetch('php/api_ubicacion.php', { method: 'POST', body: fd }).catch(function () {});
    }

    function pedirUbicacion() {
        if (!navigator.geolocation) return;
        if (watchId !== null) { navigator.geolocation.clearWatch(watchId); }
        watchId = navigator.geolocation.watchPosition(
            function (pos) {
                modalGeo.classList.remove('abierto');
                enviarUbicacion(pos.coords.latitude, pos.coords.longitude);
            },
            function (err) {
                if (err.code === 1) modalGeo.classList.add('abierto');
            },
            { enableHighAccuracy: true, maximumAge: 30000, timeout: 30000 }
        );
    }

    document.getElementById('btn-reintentar-geo').addEventListener('click', function () {
        modalGeo.classList.remove('abierto');
        setTimeout(pedirUbicacion, 300);
    });

    window.iniciarConexion = function () {
        var fd = new FormData();
        fd.append('accion', 'conexion');
        fetch('php/api_ubicacion.php', { method: 'POST', body: fd }).catch(function () {});
        pedirUbicacion();
    };
})();