use solicitudes;

INSERT INTO rol (nombre)
    VALUES ("Solicitante");
    
INSERT INTO rol (nombre)
    VALUES ("Trabajador");
    
INSERT INTO rol (nombre)
    VALUES ("Administrador");
    
INSERT INTO categoriaArea (nombre) VALUES ("Académico");
INSERT INTO categoriaArea (nombre) VALUES ("Administrativo");
INSERT INTO categoriaArea (nombre) VALUES ("Dirección");
INSERT INTO categoriaArea (nombre) VALUES ("Jefaturas");

-- Dirección
INSERT INTO area (nombre, id_categoria) SELECT "Dirección General",       id_categoria FROM categoriaArea WHERE nombre = "Dirección";
INSERT INTO area (nombre, id_categoria) SELECT "Dirección Académica",     id_categoria FROM categoriaArea WHERE nombre = "Dirección";
INSERT INTO area (nombre, id_categoria) SELECT "Dirección de Vinculación",id_categoria FROM categoriaArea WHERE nombre = "Dirección";

-- Académico
INSERT INTO area (nombre, id_categoria) SELECT "Docencia",                id_categoria FROM categoriaArea WHERE nombre = "Académico";
INSERT INTO area (nombre, id_categoria) SELECT "Desarrollo Académico",    id_categoria FROM categoriaArea WHERE nombre = "Académico";
INSERT INTO area (nombre, id_categoria) SELECT "Coordinación de Inglés",  id_categoria FROM categoriaArea WHERE nombre = "Académico";
INSERT INTO area (nombre, id_categoria) SELECT "Biblioteca",              id_categoria FROM categoriaArea WHERE nombre = "Académico";
INSERT INTO area (nombre, id_categoria) SELECT "Titulación",              id_categoria FROM categoriaArea WHERE nombre = "Académico";
INSERT INTO area (nombre, id_categoria) SELECT "Psicopedagogía",          id_categoria FROM categoriaArea WHERE nombre = "Académico";
INSERT INTO area (nombre, id_categoria) SELECT "Cultura y Deportes",      id_categoria FROM categoriaArea WHERE nombre = "Académico";

-- Administrativo
INSERT INTO area (nombre, id_categoria) SELECT "Recursos Materiales",     id_categoria FROM categoriaArea WHERE nombre = "Administrativo";
INSERT INTO area (nombre, id_categoria) SELECT "Recursos Financieros",    id_categoria FROM categoriaArea WHERE nombre = "Administrativo";
INSERT INTO area (nombre, id_categoria) SELECT "Caja",                    id_categoria FROM categoriaArea WHERE nombre = "Administrativo";
INSERT INTO area (nombre, id_categoria) SELECT "Planeación",              id_categoria FROM categoriaArea WHERE nombre = "Administrativo";
INSERT INTO area (nombre, id_categoria) SELECT "Calidad",                 id_categoria FROM categoriaArea WHERE nombre = "Administrativo";
INSERT INTO area (nombre, id_categoria) SELECT "Transparencia",           id_categoria FROM categoriaArea WHERE nombre = "Administrativo";
INSERT INTO area (nombre, id_categoria) SELECT "Centro de Copiado",       id_categoria FROM categoriaArea WHERE nombre = "Administrativo";

-- Jefaturas
INSERT INTO area (nombre, id_categoria) SELECT "Industrial",              id_categoria FROM categoriaArea WHERE nombre = "Jefaturas";
INSERT INTO area (nombre, id_categoria) SELECT "Innovación Agrícola",     id_categoria FROM categoriaArea WHERE nombre = "Jefaturas";
INSERT INTO area (nombre, id_categoria) SELECT "Informática",             id_categoria FROM categoriaArea WHERE nombre = "Jefaturas";
INSERT INTO area (nombre, id_categoria) SELECT "Sistemas Computacionales",id_categoria FROM categoriaArea WHERE nombre = "Jefaturas";
INSERT INTO area (nombre, id_categoria) SELECT "Gestión Empresarial",     id_categoria FROM categoriaArea WHERE nombre = "Jefaturas";
    
INSERT INTO estado_solicitud (nombre)
    VALUES ("Pendiente");
    
INSERT INTO estado_solicitud (nombre)
    VALUES ("En Proceso");
    
INSERT INTO estado_solicitud (nombre)
    VALUES ("Finalizada");
    
INSERT INTO estado_solicitud (nombre) 
	VALUES ('En Revisión');

INSERT INTO estado_solicitud (nombre)
    VALUES ('Reporte Rechazado');

SELECT * from estado_solicitud;
SELECT * from rol;
SELECT * from area;