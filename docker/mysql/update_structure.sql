-- Script para actualizar la base de datos Docker con la estructura correcta del bd.sql

USE hallazgos;

-- Verificar si las columnas existen y agregarlas si no están
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'hallazgos' 
     AND TABLE_NAME = 'hallazgos_evidencias' 
     AND COLUMN_NAME = 'tamaño_archivo') > 0,
    'SELECT "Column tamaño_archivo already exists" as message',
    'ALTER TABLE hallazgos_evidencias ADD COLUMN tamaño_archivo int DEFAULT NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'hallazgos' 
     AND TABLE_NAME = 'hallazgos_evidencias' 
     AND COLUMN_NAME = 'tipo_mime') > 0,
    'SELECT "Column tipo_mime already exists" as message',
    'ALTER TABLE hallazgos_evidencias ADD COLUMN tipo_mime varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar estructura final
DESCRIBE hallazgos_evidencias;
