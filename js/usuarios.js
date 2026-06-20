// Secciones de la página
var titulosPagina = {
    crear:          'Nueva Solicitud',
    creadas:        'Mis Solicitudes',
    notificaciones: 'Notificaciones'
};

function navTo(id) {
    navegarSeccion(id, titulosPagina);
}

inicializarNavegacion(titulosPagina);

// Filtrado de tabla de solicitudes
(function () {
    var inputBuscar   = document.getElementById("buscar-solicitud");
    var selectEstado  = document.getElementById("filtro-estado");

    if (!inputBuscar || !selectEstado) return;

    function filtrar() {
        var texto  = inputBuscar.value.toLowerCase().trim();
        var estado = selectEstado.value.toLowerCase();

        document.querySelectorAll("#tabla-solicitudes tr").forEach(function (fila) {
            if (fila.querySelector("td[colspan]")) {
                fila.style.display = "";
                return;
            }

            var titulo    = (fila.cells[1]?.textContent || "").toLowerCase();
            var estadoFila = (fila.cells[6]?.textContent || "").toLowerCase();

            var coincideTexto  = titulo.includes(texto);
            var coincideEstado = estado === "" || estadoFila.includes(estado);

            fila.style.display = coincideTexto && coincideEstado ? "" : "none";
        });
    }

    inputBuscar.addEventListener("input", filtrar);
    selectEstado.addEventListener("change", filtrar);
})();