-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for hallazgos
CREATE DATABASE IF NOT EXISTS `hallazgos` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `hallazgos`;

-- Dumping structure for table hallazgos.hallazgos
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table hallazgos.hallazgos: ~6 rows (approximately)
INSERT INTO `hallazgos` (`id`, `id_usuario`, `fecha`, `job_order`, `no_ensamble`, `estacion`, `area_ubicacion`, `retrabajo`, `modelo`, `no_parte`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`, `estado`) VALUES
	(1, 2, '2025-07-16', '6-700-0787', '4-200-00410', 'Estación 3', 'Prensas', 'Si', 'ED/SS, SUSPENSION, CENTERPOINT SPRING', '343434', 'Todo mal', '2025-07-16 16:43:05', '2025-07-17 21:27:28', 'scrap'),
	(2, 2, '2025-07-16', '45454-544554', '4-100-00322', 'Estación 3', 'Prensas', 'Si', 'ED, 32&#039; X 48&quot;SW ROLLED BODY ASSY', '4334', 'mal', '2025-07-16 16:45:36', '2025-07-17 21:24:20', 'scrap'),
	(3, 2, '2025-07-16', '89898989', '4-600-10208', 'Estación 15', 'soldadura', 'Si', 'ED, WELDING PART PKG', '89898989', 'Todo Mal', '2025-07-16 16:53:46', '2025-07-16 16:53:46', 'activo'),
	(4, 2, '2025-07-16', '34334', '4-200-00416', 'Estación 2', 'Beam welder', 'Si', 'LDDT, FRAME', '34334', 'Todo mal', '2025-07-16 17:22:29', '2025-07-16 17:22:29', 'activo'),
	(5, 2, '2025-07-16', '7887878', '4-100-00322', 'Estación 3', 'Prensas', 'Si', 'ED, 32&#039; X 48&quot;SW ROLLED BODY ASSY', '78778', 'mal', '2025-07-16 17:40:29', '2025-07-17 18:32:02', 'activo'),
	(6, 2, '2025-07-16', '1232', '4-200-00008', 'Estación 2', 'Beam welder', 'Si', 'ED/SS 5TH WHEEL ASSY', '322323', 'Mal', '2025-07-16 18:07:31', '2025-07-17 21:24:13', 'scrap'),
	(7, 1, '2025-07-17', '89', '89', '89', '89', 'No', '89', '89', 'msal', '2025-07-17 19:33:25', '2025-07-17 18:06:05', 'activo'),
	(9, 1, '2025-07-17', '90934', '4-100-00322', 'Estación 3', 'Prensas', 'Si', 'ED, 32\' X 48"SW ROLLED BODY ASSY', '9043', 'Todo mal', '2025-07-17 19:38:09', '2025-07-17 19:38:09', 'inactivo'),
	(10, 1, '2025-07-17', 'M895-9849', '4-100-00322', 'Estación 3', 'Plasma', 'Si', 'ED, 32\' X 48"SW ROLLED BODY ASSY', '57843', 'Todo Mal', '2025-07-17 19:43:56', '2025-07-17 18:40:05', 'inactivo'),
	(11, 1, '2025-07-17', '894343-9834', '4-100-00322', 'Estación 2', 'Beam welder', 'Si', 'ED, 32\' X 48"SW ROLLED BODY ASSY', '433434', 'Mal', '2025-07-17 19:55:12', '2025-07-17 19:55:12', 'inactivo'),
	(12, 1, '2025-07-17', '8989', '4-100-00322', 'Estación 2', 'Prensas', 'Si', 'ED, 32\' X 48"SW ROLLED BODY ASSY', '8989', 'mal', '2025-07-17 23:17:54', '2025-07-17 23:17:54', 'inactivo'),
	(13, 1, '2025-07-17', '4-678-900', '4-600-10208', 'Estación 1', 'Fresadora', 'Si', 'ED, WELDING PART PKG', '5674', 'Todo mal', '2025-07-18 00:33:10', '2025-07-17 21:27:12', 'scrap');

-- Dumping structure for table hallazgos.hallazgos_defectos
CREATE TABLE IF NOT EXISTS `hallazgos_defectos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hallazgo_id` int NOT NULL,
  `defecto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hallazgo` (`hallazgo_id`),
  KEY `idx_defecto` (`defecto`),
  CONSTRAINT `hallazgos_defectos_ibfk_1` FOREIGN KEY (`hallazgo_id`) REFERENCES `hallazgos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table hallazgos.hallazgos_defectos: ~15 rows (approximately)
INSERT INTO `hallazgos_defectos` (`id`, `hallazgo_id`, `defecto`, `fecha_creacion`) VALUES
	(1, 1, 'Pieza con grados de mas (se doblo de más)', '2025-07-16 16:43:05'),
	(2, 1, 'Pieza muy abierta', '2025-07-16 16:43:05'),
	(3, 1, 'Pieza cerrada', '2025-07-16 16:43:05'),
	(4, 2, 'Pieza pandeada', '2025-07-16 16:45:36'),
	(5, 2, 'Daño en pieza al doblar', '2025-07-16 16:45:36'),
	(6, 2, 'Pieza cerrada', '2025-07-16 16:45:36'),
	(7, 3, 'Grietas', '2025-07-16 16:53:46'),
	(8, 3, 'puntas sobrantes de soldadura', '2025-07-16 16:53:46'),
	(9, 3, 'chisporroteo', '2025-07-16 16:53:46'),
	(10, 4, 'cordón cargado hacia el flange', '2025-07-16 17:22:29'),
	(11, 4, 'altura de webbing fuera de tolerancia', '2025-07-16 17:22:29'),
	(12, 4, 'unión de flange mal soldada', '2025-07-16 17:22:29'),
	(13, 5, 'Pieza pandeada', '2025-07-16 17:40:29'),
	(14, 5, 'Daño en pieza al doblar', '2025-07-16 17:40:29'),
	(15, 5, 'Pieza con grados de mas (se doblo de más)', '2025-07-16 17:40:29'),
	(16, 6, 'cordón cargado hacia el flange', '2025-07-16 18:07:31'),
	(17, 6, 'altura de webbing fuera de tolerancia', '2025-07-16 18:07:31'),
	(18, 7, 'maal', '2025-07-17 19:33:25'),
	(19, 7, 'mal', '2025-07-17 19:33:25'),
	(20, 7, 'mal', '2025-07-17 19:33:25'),
	(23, 9, 'Pieza muy abierta', '2025-07-17 19:38:09'),
	(24, 9, 'Pieza con grados de mas (se doblo de más)', '2025-07-17 19:38:09'),
	(25, 9, 'Pieza con grado de menos (falto doblez)', '2025-07-17 19:38:09'),
	(26, 10, 'Error de placa', '2025-07-17 19:43:56'),
	(27, 10, 'Error de programa', '2025-07-17 19:43:56'),
	(28, 11, 'cordón cargado hacia el flange', '2025-07-17 19:55:12'),
	(29, 11, 'altura de webbing fuera de tolerancia', '2025-07-17 19:55:12'),
	(30, 12, 'Pieza muy abierta', '2025-07-17 23:17:54'),
	(31, 12, 'Pieza con grado de menos (falto doblez)', '2025-07-17 23:17:54'),
	(32, 13, 'Perforación de tubo fuera de especificación', '2025-07-18 00:33:10');

-- Dumping structure for table hallazgos.hallazgos_evidencias
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table hallazgos.hallazgos_evidencias: ~17 rows (approximately)
INSERT INTO `hallazgos_evidencias` (`id`, `hallazgo_id`, `archivo_nombre`, `archivo_original`, `fecha_subida`, `tamaño_archivo`, `tipo_mime`) VALUES
	(1, 1, 'evid_6877d699ae263_1752684185_0.jpg', 'Image (48).jpg', '2025-07-16 16:43:05', NULL, NULL),
	(2, 1, 'evid_6877d699aea05_1752684185_1.png', '352681111_2892524807544414_8486043528924434117_n-removebg-preview (1).png', '2025-07-16 16:43:05', NULL, NULL),
	(3, 1, 'evid_6877d699af099_1752684185_2.png', '352681111_2892524807544414_8486043528924434117_n-removebg-preview.png', '2025-07-16 16:43:05', NULL, NULL),
	(4, 2, 'evid_6877d7302672f_1752684336_0.jpg', 'Junior-jpg-768x698.jpg', '2025-07-16 16:45:36', NULL, NULL),
	(5, 2, 'evid_6877d73027558_1752684336_1.jpg', 'Image (51).jpg', '2025-07-16 16:45:36', NULL, NULL),
	(6, 2, 'evid_6877d730282d8_1752684336_2.png', '352681111_2892524807544414_8486043528924434117_n-removebg-preview (1).png', '2025-07-16 16:45:36', NULL, NULL),
	(7, 3, 'evid_6877d91aac854_1752684826_0.jpg', 'Image (48).jpg', '2025-07-16 16:53:46', NULL, NULL),
	(8, 3, 'evid_6877d91aad41f_1752684826_1.png', '352681111_2892524807544414_8486043528924434117_n-removebg-preview (1).png', '2025-07-16 16:53:46', NULL, NULL),
	(9, 3, 'evid_6877d91aad96d_1752684826_2.png', '352681111_2892524807544414_8486043528924434117_n-removebg-preview.png', '2025-07-16 16:53:46', NULL, NULL),
	(10, 4, 'evid_6877dfd52b93a_1752686549_0.png', '352681111_2892524807544414_8486043528924434117_n-removebg-preview (1).png', '2025-07-16 17:22:29', NULL, NULL),
	(11, 4, 'evid_6877dfd52c120_1752686549_1.png', '352681111_2892524807544414_8486043528924434117_n-removebg-preview.png', '2025-07-16 17:22:29', NULL, NULL),
	(12, 5, 'evid_6877e40d70bd8_1752687629_0.jpg', '27ce68ce9aeb60b434a7b6c194a19fd0.jpg', '2025-07-16 17:40:29', NULL, NULL),
	(13, 5, 'evid_6877e40d7153b_1752687629_1.png', 'Captura de pantalla 2025-07-15 152201.png', '2025-07-16 17:40:29', NULL, NULL),
	(14, 5, 'evid_6877e40d71f59_1752687629_2.jpg', 'Junior-jpg-768x698.jpg', '2025-07-16 17:40:29', NULL, NULL),
	(15, 6, 'evid_6877ea638b3e9_1752689251_0.png', 'Captura de pantalla 2025-07-15 152201.png', '2025-07-16 18:07:31', NULL, NULL),
	(16, 6, 'evid_6877ea638bb5f_1752689251_1.jpg', 'Junior-jpg-768x698.jpg', '2025-07-16 18:07:31', NULL, NULL),
	(17, 6, 'evid_6877ea638c1e9_1752689251_2.jpg', 'Image (50).jpg', '2025-07-16 18:07:31', NULL, NULL),
	(18, 7, 'evid_6878fba5ceac6_7.png', 'Captura de pantalla 2025-07-15 152201.png', '2025-07-17 19:33:25', 166022, 'image/png'),
	(19, 9, 'evid_6878fcc14b087_9.jpg', 'Junior-jpg-768x698.jpg', '2025-07-17 19:38:09', 95474, 'image/jpeg'),
	(20, 9, 'evid_6878fcc14b325_9.jpg', 'Image (51).jpg', '2025-07-17 19:38:09', 531824, 'image/jpeg'),
	(21, 9, 'evid_6878fcc14b5f6_9.jpg', 'Image (49).jpg', '2025-07-17 19:38:09', 590675, 'image/jpeg'),
	(22, 10, 'evid_6878fe1c44eff_10.jpg', 'Junior-jpg-768x698.jpg', '2025-07-17 19:43:56', 95474, 'image/jpeg'),
	(23, 10, 'evid_6878fe1c4545b_10.jpg', 'Image (51).jpg', '2025-07-17 19:43:56', 531824, 'image/jpeg'),
	(24, 10, 'evid_6878fe1c4595a_10.jpg', 'Image (50).jpg', '2025-07-17 19:43:56', 52514, 'image/jpeg'),
	(25, 11, 'evid_687900c0bac36_11.png', 'Captura de pantalla 2025-07-15 152201.png', '2025-07-17 19:55:12', 166022, 'image/png'),
	(26, 11, 'evid_687900c0bb093_11.jpg', 'Image (51).jpg', '2025-07-17 19:55:12', 531824, 'image/jpeg'),
	(27, 11, 'evid_687900c0bb5bc_11.jpg', 'Image (50).jpg', '2025-07-17 19:55:12', 52514, 'image/jpeg'),
	(28, 12, 'evid_6879304263aa9_12.png', 'Captura de pantalla 2025-07-15 152201.png', '2025-07-17 23:17:54', 166022, 'image/png'),
	(29, 13, 'evid_687941e6b9f49_13.jpg', 'Image (50).jpg', '2025-07-18 00:33:10', 52514, 'image/jpeg');

-- Dumping structure for table hallazgos.scrap_records
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hallazgos.scrap_records: ~0 rows (approximately)
INSERT INTO `scrap_records` (`id`, `hallazgo_id`, `modelo`, `no_parte`, `no_ensamble`, `precio`, `fecha_scrap`, `usuario_scrap`, `observaciones`) VALUES
	(3, 6, 'ED/SS 5TH WHEEL ASSY', '322323', '4-200-00008', 9000.00, '2025-07-17 15:14:44', 1, 'mal'),
	(4, 6, 'ED/SS 5TH WHEEL ASSY', '322323', '4-200-00008', 9799.00, '2025-07-17 15:24:13', 1, 'mal'),
	(5, 2, 'ED, 32&#039; X 48&quot;SW ROLLED BODY ASSY', '4334', '4-100-00322', 6888.00, '2025-07-17 15:24:20', 1, 'mal'),
	(6, 13, 'ED, WELDING PART PKG', '5674', '4-600-10208', 55455.00, '2025-07-17 15:27:12', 1, ',lmknk'),
	(7, 1, 'ED/SS, SUSPENSION, CENTERPOINT SPRING', '343434', '4-200-00410', 488.00, '2025-07-17 15:27:28', 1, '88');

-- Dumping structure for table hallazgos.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `rol` enum('calidad','encargado') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hallazgos.usuarios: ~2 rows (approximately)
INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `contrasena`, `rol`) VALUES
	(1, 'Encargado General', 'encargado@empresa.com', '81dc9bdb52d04dc20036dbd8313ed055', 'encargado'),
	(2, 'Empleado Calidad', 'calidad@empresa.com', '81dc9bdb52d04dc20036dbd8313ed055', 'calidad');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
