 -- Administrador: Tiene control total en la base de datos
create user 'administrador'@'localhost' identified by 'admin123';
grant all privileges on solicitudes.* to 'administrador'@'localhost';


-- Trabajador: Puede consultar toda la base de datos pero solo ejecutar los procedimientos para manejar las solicitudes
create user 'trabajador'@'localhost' identified by 'trabajador123';
grant select on solicitudes.* to 'trabajador'@'localhost';
grant execute on procedure solicitudes.aceptar_solicitud to 'trabajador'@'localhost';
grant execute on procedure solicitudes.finalizar_asignacion to 'trabajador'@'localhost';
grant execute on procedure solicitudes.insertar_bitacora to 'trabajador'@'localhost';


-- Solicitante: Puede consultar la tabla solicitud y ejecutar el procedimiento para generar una solicitud
create user 'solicitante'@'localhost' identified by 'solicitante123';
grant select on solicitudes.solicitud to 'solicitante'@'localhost'; -- Puede consultar datos en la tabla solicitud
grant execute on procedure solicitudes.insertar_solicitud to 'solicitante'@'localhost'; -- Puede usar el procedimiento ingresar_solicitud


flush privileges;

-- show grants FOR 'administrador'@'localhost';
-- show grants FOR 'trabajador'@'localhost';
-- show grants FOR 'solicitante'@'localhost';
-- select user, host FROM mysql.user;