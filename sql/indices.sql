use solicitudes;

CREATE INDEX idx_datos_usuario
ON usuario(nombre, app, apm);

CREATE INDEX idx_disponibilidad
ON usuario(disponible);

CREATE INDEX idx_prioridad
ON solicitud(prioridad);

CREATE INDEX idx_solicitud_fecha
ON solicitud(fecha_creacion);

CREATE INDEX idx_estado
ON asignacion(estado_asignacion);

CREATE INDEX idx_fecha_asignacion
ON asignacion(fecha_inicio, fecha_fin);

CREATE FULLTEXT INDEX idx_mensaje
ON notificacion(mensaje);

CREATE INDEX idx_bitacora_clasif
ON bitacora(clasificacion);

CREATE FULLTEXT INDEX idx_bitacora_desc
ON bitacora(descripcion);

CREATE INDEX idx_bitacora_fecha
ON bitacora(fecha_registro);