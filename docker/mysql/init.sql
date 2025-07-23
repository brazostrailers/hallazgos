
-- Crear base de datos y usar
CREATE DATABASE IF NOT EXISTS hallazgos;
USE hallazgos;

-- Tabla usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `rol` enum('calidad','encargado') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla hallazgos
CREATE TABLE IF NOT EXISTS `hallazgos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `fecha` date NOT NULL,
  `job_order` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_ensamble` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estacion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_ubicacion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `retrabajo` enum('Si','No') COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_parte` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo','cuarentena','scrap') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_estacion` (`estacion`),
  KEY `idx_area` (`area_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla hallazgos_defectos
CREATE TABLE IF NOT EXISTS `hallazgos_defectos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hallazgo_id` int NOT NULL,
  `defecto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hallazgo` (`hallazgo_id`),
  KEY `idx_defecto` (`defecto`),
  CONSTRAINT `hallazgos_defectos_ibfk_1` FOREIGN KEY (`hallazgo_id`) REFERENCES `hallazgos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla hallazgos_evidencias
CREATE TABLE IF NOT EXISTS `hallazgos_evidencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hallazgo_id` int NOT NULL,
  `archivo_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archivo_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tamaño_archivo` int DEFAULT NULL,
  `tipo_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hallazgo` (`hallazgo_id`),
  CONSTRAINT `hallazgos_evidencias_ibfk_1` FOREIGN KEY (`hallazgo_id`) REFERENCES `hallazgos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla scrap_records (Nueva tabla para funcionalidad de scrap)
CREATE TABLE IF NOT EXISTS `scrap_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hallazgo_id` int NOT NULL,
  `modelo` varchar(255) DEFAULT NULL,
  `no_parte` varchar(255) DEFAULT NULL,
  `no_ensamble` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `fecha_scrap` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario_scrap` int DEFAULT NULL,
  `observaciones` text,
  PRIMARY KEY (`id`),
  KEY `usuario_scrap` (`usuario_scrap`),
  KEY `idx_hallazgo_id` (`hallazgo_id`),
  KEY `idx_fecha_scrap` (`fecha_scrap`),
  CONSTRAINT `scrap_records_ibfk_1` FOREIGN KEY (`hallazgo_id`) REFERENCES `hallazgos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scrap_records_ibfk_2` FOREIGN KEY (`usuario_scrap`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insertar usuarios por defecto
INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES
('Encargado General', 'encargado@empresa.com', '81dc9bdb52d04dc20036dbd8313ed055', 'encargado'),
('Empleado Calidad', 'calidad@empresa.com', '81dc9bdb52d04dc20036dbd8313ed055', 'calidad')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
