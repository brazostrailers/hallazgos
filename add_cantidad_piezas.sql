-- Agregar campo cantidad_piezas a la tabla hallazgos
-- Fecha: 2025-08-14
-- Propósito: Permitir especificar la cantidad de piezas afectadas en cada hallazgo

ALTER TABLE `hallazgos` 
ADD COLUMN `cantidad_piezas` INT NOT NULL DEFAULT 1 AFTER `no_parte`;

-- Agregar comentario para documentar el campo
ALTER TABLE `hallazgos` 
MODIFY COLUMN `cantidad_piezas` INT NOT NULL DEFAULT 1 COMMENT 'Cantidad de piezas afectadas en el hallazgo (mínimo 1)';

-- Verificar que la columna se agregó correctamente
DESCRIBE `hallazgos`;
