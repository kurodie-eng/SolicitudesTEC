// Secciones de la página
var titulosPagina = {
    solicitudes:    'Solicitudes',
    'mis-asignaciones': 'Solicitudes Aceptadas',
    reporte:        'Reporte de Solicitud',
    notificaciones: 'Notificaciones'
};

var acceptedRequests = {};

inicializarNavegacion(titulosPagina);

// Funciones para mostrar los distintos estados de las solicitudes en la interfaz de los trabajadores
function aceptarSolicitud(button, id) {
    document.getElementById('modal-id-sol').value = id;
    document.getElementById('modalPrioridad').classList.add('abierto');
}

function cerrarModalPrioridad() {
    document.getElementById('modalPrioridad').classList.remove('abierto');
    document.getElementById('modal-prioridad').value = '';
}

document.getElementById('modalPrioridad').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalPrioridad();
});

function rechazarSolicitud(button) {
    var item = button.closest('.solicitud-item');
    item.classList.add('rejected');
    item.classList.remove('accepted');
    item.querySelector('.status').innerHTML = '<strong>Estado:</strong> Rechazada';
    item.querySelector('.buttons').style.display = 'none';
    item.querySelector('.cancel-btn').style.display = 'flex';
    item.querySelector('.create-report').style.display = 'none';
}

function cancelarSolicitud(button, id) {
    var item = button.closest('.solicitud-item');
    item.classList.remove('accepted', 'rejected');
    item.querySelector('.status').innerHTML = '<strong>Estado:</strong> Pendiente';
    item.querySelector('.buttons').style.display = 'flex';
    item.querySelector('.cancel-btn').style.display = 'none';
    item.querySelector('.create-report').style.display = '';

    delete acceptedRequests[id];
}

// Función para el botón de Crear Reporte, temporal
function crearReporte(id) {
    if (acceptedRequests[id]) {
        var datos = acceptedRequests[id];
        navegarSeccion('reporte', titulosPagina);

        // Pre-rellena título
        document.getElementById('titulo-reporte').value = 'Reporte de ' + datos.title;
        document.getElementById('desc-problema').value  = '';
        document.getElementById('desc-solucion').value  = '';

        // Sincroniza el select y el hidden con el id de la solicitud
        var select = document.getElementById('select-solicitud-reporte');
        var hidden = document.getElementById('input-id-sol-reporte');

        // Agrega la opción si no existe ya
        var existe = false;
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value == id) { existe = true; break; }
        }
        if (!existe) {
            var opcion = document.createElement('option');
            opcion.value       = id;
            opcion.textContent = datos.title;
            select.appendChild(opcion);
        }
        select.value = id;
        hidden.value = id;

    } else {
        alert('Esta solicitud no está aceptada.');
    }
}

// Sincronizar hidden al cambiar el select manualmente
document.getElementById('select-solicitud-reporte').addEventListener('change', function() {
    document.getElementById('input-id-sol-reporte').value = this.value;
});

// Sincronizar el select de reporte con el input hidden
var selectReporte = document.getElementById('select-solicitud-reporte');
if (selectReporte) {
    selectReporte.addEventListener('change', function() {
        document.getElementById('input-id-sol-reporte').value = this.value;
    });
}

// Si ya hay una opción seleccionada al cargar, sincronizarla
    if (selectReporte && selectReporte.value) {
        document.getElementById('input-id-sol-reporte').value = selectReporte.value;
    }

// Navega a la sección de reporte y pre-selecciona la solicitud rechazada
function irAReporte(id) {
    navegarSeccion('reporte', titulosPagina);
    var select = document.getElementById('select-solicitud-reporte');
    if (select) select.value = id;
}