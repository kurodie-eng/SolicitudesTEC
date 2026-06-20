use solicitudes;

-- Agrega el tipo de acción al reporte (bitácora) y auto-rellena el título.
-- Las filas existentes quedan con tipo_accion NULL (reportes históricos sin tipo asignado).

ALTER TABLE bitacora
    ADD COLUMN tipo_accion ENUM('Correctiva', 'Preventiva', 'Soporte Técnico') NULL
    AFTER descripcion_solucion;

-- Verificación
SELECT id_bit, encabezado, tipo_accion FROM bitacora LIMIT 10;
