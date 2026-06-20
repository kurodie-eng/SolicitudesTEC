-- Notificacion al trabajador cuando se crea una nueva solicitud
delimiter //
create trigger nueva_solicitud_notificacion
after insert on solicitud
for each row
begin

insert into notificacion(id_us, id_sol, mensaje)

select id_us,
       new.id_sol,
       'Nueva solicitud disponible'

from usuario
where disponible = true
and id_rol = 2;

end //
delimiter ;


-- Cambiar estado de un trabajador al tomar una solicitud
delimiter //
create trigger bloquear_trabajador
after insert on asignacion
for each row
begin

update usuario
set disponible=false
where id_us=new.id_trabajador;

end //
delimiter ;


-- Cambiar el estado de un trabajador al finalizar su asignacion
delimiter //
create trigger liberar_trabajador
after update on asignacion
for each row
begin
	if old.estado_asignacion = 'activa'
	   and new.estado_asignacion in ('cancelada','completada') then
        update usuario
        set disponible = true
        where id_us = new.id_trabajador;
    end if;

end //
delimiter ;


-- Cambiar estado de solicitud a 'En proceso'
delimiter //
create trigger solicitud_en_proceso
after insert on asignacion
for each row
begin

update solicitud
set id_estado = 2
where id_sol = new.id_sol;

end //
delimiter ;

-- Trigger para evitar que se asigne una solicitud que ya esta asignada o ya ha sido completada
delimiter //
create trigger evitar_doble_asignacion
before insert on asignacion
for each row
begin
	-- Verificar que el usuario sea tipo trabajador
	if not exists (
		select 1
		from usuario
		where id_us = new.id_trabajador
		and id_rol = 2
	) then
		signal sqlstate '45000'
		set message_text = 'El usuario no es trabajador';
	end if;

    -- Evitar si ya existe asignacion activa
	if exists(
		select 1
		from asignacion
		where id_sol = new.id_sol
		and estado_asignacion = 'activa'
    ) then

        signal sqlstate '45000'
        set message_text='Solicitud ya asignada';

    end if;

    -- Evitar si la solicitud ya está finalizada
    if exists(
        select 1
        from solicitud
        where id_sol = new.id_sol
        and id_estado = 3
    ) then

        signal sqlstate '45000'
        set message_text='Solicitud ya finalizada';

    end if;

end //
delimiter ;


-- Registrar la finalizacion de una solicitud
delimiter //
create trigger finalizar_solicitud
after update on asignacion
for each row
begin

    if old.estado_asignacion = 'activa'
       and new.estado_asignacion = 'completada' then

        update solicitud
        set id_estado = 3   -- 3 = Finalizada
        where id_sol = new.id_sol;

    end if;

end //
delimiter ;