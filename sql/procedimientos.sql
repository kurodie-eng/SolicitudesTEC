-- Los procedimientos en esta base de datos tienen la finalidad de facilitar la insercion de datos en las tablas

-- Insercion en tabla 'rol'
delimiter //
create procedure insertar_rol(in nom varchar(50))
begin
    insert into rol(nombre)
    values (nom);
end //
delimiter ;


-- Insercion en tabla 'area'
delimiter //
create procedure insertar_area(in nom varchar(50))
begin
    insert into area(nombre)
    values (nom);
end //
delimiter ;


-- Insercion en tabla 'usuario'
delimiter //
create procedure insertar_usuario(
    in p_nombre varchar(50),
    in p_app varchar(50),
    in p_apm varchar(50),
    in p_username varchar(50),
    in p_contrasena varchar(255),
    in p_id_rol int,
    in p_id_area int
)
begin
    insert into usuario(nombre, app, apm, username,contrasena, id_rol, id_area)
    values(p_nombre, p_app, p_apm, p_username, p_contrasena, p_id_rol, p_id_area);
end //
delimiter ;


-- Insercion en tabla 'estado_solicitud'
delimiter //
create procedure insertar_estado(in p_nombre varchar(20))
begin
    insert into estado_solicitud(nombre)
    values(p_nombre);
end //
delimiter ;


-- Insercion en tabla 'solicitud'
delimiter //
create procedure insertar_solicitud(
    in p_id_us int,
    in p_encabezado varchar(255),
    in p_descripcion text,
    in p_prioridad enum('Baja','Media','Alta')
)
begin
    insert into solicitud(id_us, encabezado, descripcion, prioridad)
    values(p_id_us, p_encabezado, p_descripcion, p_prioridad);

end //
delimiter ;


-- Insercion en tabla 'asignacion'. La fecha de inicio y fin se insertan por separado en dos procedimientos.
-- 1. Aceptar solicitud: Aqui se insertan los datos de solicitud, trabajador y fecha de inicio
delimiter //
create procedure aceptar_solicitud(in p_id_sol int, in p_id_trabajador int)

begin
	-- Validar si el usuario es tipo trabajador
	if not exists (
		select 1
		from usuario
		where id_us = p_id_trabajador
		and id_rol = 2
	) then
		signal sqlstate '45000'
		set message_text = 'El usuario no es trabajador';
	end if;
    
	-- Validar solicitud activa mediante la funcion correspondiente
    if not solicitud_activa(p_id_sol) then
        signal sqlstate '45000'
        set message_text = 'Solicitud no activa';
    end if;

    -- Validar trabajador disponible mediante la funcion correspondiente
    if not trabajador_disponible(p_id_trabajador) then
        signal sqlstate '45000'
        set message_text = 'Trabajador no disponible';
    end if;
    
	insert into asignacion(id_sol, id_trabajador)
	values(p_id_sol, p_id_trabajador);
end //
delimiter ;


-- 2. Finalizar solicitud. Aqui se inserta la fecha de cierre en la tabla 'asignación'
delimiter //
create procedure finalizar_asignacion(in p_id_sol int)
begin

    -- Verificar que exista asignacion activa
    if not exists (
        select 1
        from asignacion
        where id_sol = p_id_sol
        and estado_asignacion = 'activa'
    ) then
        signal sqlstate '45000'
        set message_text = 'No existe asignacion activa';
    end if;

    -- Marcar como completada
    update asignacion
    set 
        estado_asignacion = 'completada',
        fecha_fin = now()
    where id_sol = p_id_sol
    and estado_asignacion = 'activa';

end //
delimiter ;

-- 3. Si el trabajador cancela su asignación, esta se cierra y se reestablece el estado de la solicitud y la asignacion
delimiter //
create procedure cancelar_asignacion(in p_id_sol int)
begin

    -- Verificar que exista asignación activa
    if not exists (
        select 1
        from asignacion
        where id_sol = p_id_sol
        and estado_asignacion = 'activa'
    ) then
        signal sqlstate '45000'
        set message_text = 'No existe asignacion activa';
    end if;

    -- Cancelar asignacion
    update asignacion
    set 
        estado_asignacion = 'cancelada',
        fecha_fin = now()
    where id_sol = p_id_sol
    and estado_asignacion = 'activa';

    -- Regresar solicitud a Pendiente
    update solicitud
    set id_estado = 1
    where id_sol = p_id_sol;

end //
delimiter ;


-- Insercion en tabla 'notificacion'
delimiter //
create procedure insertar_notificacion(
    in p_id_us int,
    in p_id_sol int,
    in p_mensaje text
)
begin
    insert into notificacion(id_us, id_sol, mensaje)
    values(p_id_us, p_id_sol, p_mensaje);
end //
delimiter ;


-- Insercion en tabla 'bitacora'
delimiter //
create procedure insertar_bitacora(
    in p_id_sol int,
    in p_id_us int,
    in p_clasificacion enum('Soporte tecnico', 'Mantenimiento correctivo', 'Mantenimiento preventivo'),
    in p_encabezado varchar(50),
    in p_descripcion text,
    in p_evidencia varchar(255)
)
begin
    insert into bitacora(id_sol, id_us, clasificacion, encabezado, descripcion, evidencia)
    values(p_id_sol, p_id_us, p_clasificacion, p_encabezado, p_descripcion, p_evidencia);
end //
delimiter ;