-- Verificar si una solicitud esta activa
delimiter //
create function solicitud_activa(p_id_sol int)

returns boolean
deterministic

begin
declare v_estado int;
select id_estado
into v_estado
from solicitud
where id_sol=p_id_sol;
return v_estado in (1,2);

end //
delimiter ;


-- Verificar si un trabajador esta disponible
delimiter //

create function trabajador_disponible(p_id_us int)
returns boolean
deterministic
begin

    declare v_disp boolean;
    declare v_rol int;

    select disponible, id_rol
    into v_disp, v_rol
    from usuario
    where id_us = p_id_us;

    return v_disp = true and v_rol = 2;

end //

delimiter ;