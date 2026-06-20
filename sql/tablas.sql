create database solicitudes;
use solicitudes;

-- Tabla para definir y almacenar los roles de los usuarios en el sistema (Solicitante, Trabajador y Administrador)
create table rol(
    id_rol int auto_increment,
    nombre varchar(50) not null,
    primary key(id_rol)
);

-- Tabla para agrupar las áreas por categoría
-- ¡¡¡¡¡¡¡¡¡¡ NUEVO !!!!!!!!!!
create table categoriaArea(
    id_categoria int auto_increment,
    nombre varchar(50) not null unique,
    primary key(id_categoria)
);
-- ¡¡¡¡¡¡¡¡¡¡ NUEVO !!!!!!!!!!

-- Tabla para definir y almacenar las areas de la institucion
create table area(
    id_area      int auto_increment,
    nombre       varchar(50) not null unique,
-- ¡¡¡¡¡¡¡¡¡¡ NUEVO !!!!!!!!!!
    id_categoria int not null,
-- ¡¡¡¡¡¡¡¡¡¡ NUEVO !!!!!!!!!!
    primary key(id_area),
    foreign key(id_categoria) references categoriaArea(id_categoria)
);

-- Tabla que almacena los datos del usuario y, si corresponde, su rol y estado como trabajador.
create table usuario(
    id_us int auto_increment,
    nombre varchar(50) not null, # INDICE ORDINARIO (nombre, app, apm)
    app varchar(50) not null,
    apm varchar(50),
    correo varchar(100) not null unique, -- El usuario ahora es un correo [MODIFICACIÓN]
    contrasena varchar(255) not null,
    id_rol int not null, -- YA NO TIENE id_area [REMOVIDO]
    disponible boolean default true, # INDICE ORDINARIO
    primary key(id_us),
    foreign key(id_rol) references rol(id_rol) on update cascade,
);

-- Tabla para definir los estados en los que se puede encontrar una solicitud (Pendiente, En Proceso, Finalizada)
create table estado_solicitud(
    id_estado int auto_increment,
    nombre varchar(20) not null,
    primary key(id_estado)
);

-- Tabla que almacena los detalles de una solicitud enviada por un solicitante
create table solicitud(
    id_sol int auto_increment,
    id_us int not null,
    id_estado int not null default 1,
    id_area int not null,
    encabezado varchar(255) not null,
    descripcion text not null,	
    prioridad enum('Sin Asignar','Baja','Media','Alta') not null, # INDICE ORDINARIO
    fecha_creacion datetime default current_timestamp, # INDICE ORDINARIO
    fecha_limite DATETIME DEFAULT (CURRENT_TIMESTAMP + INTERVAL 2 HOUR), -- CAMBIADO DE 1 WEEK A 2 HOUR
    primary key(id_sol),
    foreign key(id_us) references usuario(id_us)
        on delete cascade,
    foreign key(id_estado) references estado_solicitud(id_estado),
    foreign key(id_area) references area(id_area)
);

-- Tabla que relaciona una solicitud con el trabajador a la que fue asignada
create table asignacion(
    id_asg int auto_increment,
    id_sol int not null,
    id_trabajador int not null,
    estado_asignacion ENUM('activa','cancelada','completada') default 'activa', # INDICE ORDINARIO
    fecha_inicio datetime default current_timestamp, # INDICE ORDINARIO (inicio-fin)
    fecha_fin datetime,
    primary key(id_asg),
    foreign key(id_sol) references solicitud(id_sol) on delete cascade,
    foreign key(id_trabajador) references usuario(id_us)
);

-- Tabla que almacena las notificaciones de los usuarios
create table notificacion(
    id_not int auto_increment,
    id_us int not null,
    id_sol int,
    mensaje text not null, # INDICE TEXTO COMPLETO
    fecha_envio datetime default current_timestamp,
    primary key(id_not),
    foreign key(id_us) references usuario(id_us)
        on delete cascade,
    foreign key(id_sol) references solicitud(id_sol)
        on delete cascade
);

-- Tabla que almacena los detalles y evidencias de una solicitud tras ser finalizada
create table bitacora(
    id_bit int auto_increment,
    id_sol int not null,
    id_us int not null,
    encabezado varchar(50) not null,
    descripcion_problema text not null,
    descripcion_solucion text not null,
-- ¡¡¡¡¡¡¡¡¡¡ NUEVO !!!!!!!!!!
    tipo_accion enum('Correctiva','Preventiva','Soporte Técnico') not null,
-- ¡¡¡¡¡¡¡¡¡¡ NUEVO !!!!!!!!!!
    evidencia text,
    fecha_registro datetime default current_timestamp, # INDICE ORDINARIO
    aprobado boolean default false,
    razon_rechazo text null,
    primary key(id_bit),
    foreign key(id_sol) references solicitud(id_sol)
        on delete cascade,
    foreign key(id_us) references usuario(id_us)
);

-- Adicion de los checks
alter table notificacion
add constraint chk_mensaje
check (length(mensaje) > 0);

alter table bitacora
add constraint chk_bit_desc
check (length(descripcion) > 10);

alter table solicitud
add constraint chk_sol_desc
check (length(descripcion) > 10);

alter table usuario
add constraint len_contrasena
check (length(contrasena) >= 8);

-- drop database solicitudes;