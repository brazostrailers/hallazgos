-- Estructura de base de datos para Hallazgos USA
-- Tabla principal para hallazgos especiales de USA

USE hallazgos;

-- Tabla principal de hallazgos USA
CREATE TABLE IF NOT EXISTS `hallazgos_usa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fecha` date NOT NULL,
  `job_order` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `warehouse` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `noparte` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `defecto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo','resuelto','pendiente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_job_order` (`job_order`),
  KEY `idx_warehouse` (`warehouse`),
  KEY `idx_defecto` (`defecto`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `hallazgos_usa_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para múltiples evidencias fotográficas de hallazgos USA
CREATE TABLE IF NOT EXISTS `hallazgos_usa_evidencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hallazgo_usa_id` int NOT NULL,
  `archivo_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archivo_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tamaño_archivo` int DEFAULT NULL,
  `tipo_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hallazgo_usa` (`hallazgo_usa_id`),
  KEY `idx_fecha_subida` (`fecha_subida`),
  CONSTRAINT `hallazgos_usa_evidencias_ibfk_1` FOREIGN KEY (`hallazgo_usa_id`) REFERENCES `hallazgos_usa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para seguimiento de acciones/actualizaciones en hallazgos USA
CREATE TABLE IF NOT EXISTS `hallazgos_usa_seguimiento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hallazgo_usa_id` int NOT NULL,
  `id_usuario` int NOT NULL,
  `accion` enum('creado','actualizado','estado_cambiado','evidencia_agregada','comentario_agregado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_anterior` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_nuevo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_accion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hallazgo_usa` (`hallazgo_usa_id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_fecha_accion` (`fecha_accion`),
  CONSTRAINT `hallazgos_usa_seguimiento_ibfk_1` FOREIGN KEY (`hallazgo_usa_id`) REFERENCES `hallazgos_usa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hallazgos_usa_seguimiento_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para estadísticas de defectos USA (opcional, para reportes)
CREATE TABLE IF NOT EXISTS `hallazgos_usa_estadisticas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `defecto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `warehouse` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mes` int NOT NULL,
  `año` int NOT NULL,
  `total_ocurrencias` int NOT NULL DEFAULT 1,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_stat` (`defecto`,`warehouse`,`mes`,`año`),
  KEY `idx_periodo` (`año`,`mes`),
  KEY `idx_defecto_stat` (`defecto`),
  KEY `idx_warehouse_stat` (`warehouse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices adicionales para optimizar consultas comunes
CREATE INDEX IF NOT EXISTS `idx_hallazgos_usa_fecha_estado` ON `hallazgos_usa` (`fecha`, `estado`);
CREATE INDEX IF NOT EXISTS `idx_hallazgos_usa_warehouse_defecto` ON `hallazgos_usa` (`warehouse`, `defecto`);
CREATE INDEX IF NOT EXISTS `idx_hallazgos_usa_job_order_fecha` ON `hallazgos_usa` (`job_order`, `fecha`);

-- Trigger para mantener estadísticas actualizadas automáticamente
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `tr_hallazgos_usa_insert_stats` 
AFTER INSERT ON `hallazgos_usa`
FOR EACH ROW
BEGIN
    INSERT INTO `hallazgos_usa_estadisticas` 
    (`defecto`, `warehouse`, `mes`, `año`, `total_ocurrencias`)
    VALUES 
    (NEW.defecto, NEW.warehouse, MONTH(NEW.fecha), YEAR(NEW.fecha), 1)
    ON DUPLICATE KEY UPDATE 
    `total_ocurrencias` = `total_ocurrencias` + 1,
    `fecha_actualizacion` = CURRENT_TIMESTAMP;
END$$

CREATE TRIGGER IF NOT EXISTS `tr_hallazgos_usa_insert_seguimiento`
AFTER INSERT ON `hallazgos_usa`
FOR EACH ROW
BEGIN
    INSERT INTO `hallazgos_usa_seguimiento` 
    (`hallazgo_usa_id`, `id_usuario`, `accion`, `estado_nuevo`, `comentario`)
    VALUES 
    (NEW.id, NEW.id_usuario, 'creado', NEW.estado, CONCAT('Hallazgo creado para Job Order: ', NEW.job_order));
END$$

CREATE TRIGGER IF NOT EXISTS `tr_hallazgos_usa_update_seguimiento`
AFTER UPDATE ON `hallazgos_usa`
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO `hallazgos_usa_seguimiento` 
        (`hallazgo_usa_id`, `id_usuario`, `accion`, `estado_anterior`, `estado_nuevo`)
        VALUES 
        (NEW.id, NEW.id_usuario, 'estado_cambiado', OLD.estado, NEW.estado);
    END IF;
END$$

DELIMITER ;

-- Comentarios de documentación
ALTER TABLE `hallazgos_usa` COMMENT = 'Tabla principal para hallazgos especiales registrados desde el formulario USA';
ALTER TABLE `hallazgos_usa_evidencias` COMMENT = 'Tabla para almacenar múltiples evidencias fotográficas por hallazgo USA';
ALTER TABLE `hallazgos_usa_seguimiento` COMMENT = 'Tabla de auditoría para rastrear cambios y acciones en hallazgos USA';
ALTER TABLE `hallazgos_usa_estadisticas` COMMENT = 'Tabla para mantener estadísticas agregadas de defectos por almacén y período';
