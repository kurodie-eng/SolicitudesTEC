use solicitudes;

-- 1. Crear tabla de categorías
CREATE TABLE categoriaArea (
    id_categoriaArea INT AUTO_INCREMENT,
    nombre       VARCHAR(50) NOT NULL UNIQUE,
    PRIMARY KEY (id_categoriaArea)
);

-- 2. Agregar columna a area (nullable primero para poder poblarla)
ALTER TABLE area ADD COLUMN id_categoriaArea INT NULL;
ALTER TABLE area ADD CONSTRAINT fk_area_categoriaArea
    FOREIGN KEY (id_categoriaArea) REFERENCES categoriaArea(id_categoriaArea);

-- 3. Insertar categorías base
INSERT INTO categoriaArea (nombre) VALUES
    ('Dirección'),
    ('Académico'),
    ('Administrativo'),
    ('Jefaturas');

-- 4. Asignar categorías a las áreas existentes
UPDATE area SET id_categoriaArea = (SELECT id_categoriaArea FROM categoriaArea WHERE nombre = 'Dirección')
    WHERE nombre IN ('Dirección General', 'Dirección Académica', 'Dirección de Vinculación');

UPDATE area SET id_categoriaArea = (SELECT id_categoriaArea FROM categoriaArea WHERE nombre = 'Académico')
    WHERE nombre IN ('Docencia', 'Desarrollo Académico', 'Coordinación de Inglés',
                     'Biblioteca', 'Titulación', 'Psicopedagogía', 'Cultura y Deportes');

UPDATE area SET id_categoriaArea = (SELECT id_categoriaArea FROM categoriaArea WHERE nombre = 'Administrativo')
    WHERE nombre IN ('Recursos Materiales', 'Recursos Financieros', 'Caja',
                     'Planeación', 'Calidad', 'Transparencia', 'Centro de Copiado');

UPDATE area SET id_categoriaArea = (SELECT id_categoriaArea FROM categoriaArea WHERE nombre = 'Jefaturas')
    WHERE nombre IN ('Industrial', 'Innovación Agrícola', 'Informática',
                     'Sistemas Computacionales', 'Gestión Empresarial');

-- 5. Hacer la columna obligatoria ahora que todas las filas tienen valor
ALTER TABLE area MODIFY COLUMN id_categoriaArea INT NOT NULL;

-- Verificación
SELECT c.nombre AS categoriaArea, a.nombre AS area
FROM categoriaArea c
LEFT JOIN area a ON a.id_categoriaArea = c.id_categoriaArea
ORDER BY c.nombre, a.nombre;
