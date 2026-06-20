// Sidebar colapsable / overlay móvil

function cerrarSidebarMobile() {
    document.body.classList.remove('sidebar-movil-abierta');
    var ov = document.getElementById('sidebar-overlay');
    if (ov) ov.classList.remove('visible');
}

function toggleSidebar() {
    if (window.innerWidth <= 768) {
        var abierta = document.body.classList.toggle('sidebar-movil-abierta');
        if (abierta) document.body.classList.remove('sidebar-colapsada');
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