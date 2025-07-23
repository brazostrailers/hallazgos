-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: db
-- Tiempo de generación: 23-07-2025 a las 16:26:04
-- Versión del servidor: 8.0.42
-- Versión de PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `hallazgos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hallazgos`
--

CREATE TABLE `hallazgos` (
  `id` int NOT NULL,
  `id_usuario` int NOT NULL,
  `fecha` date NOT NULL,
  `job_order` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_ensamble` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estacion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_ubicacion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `retrabajo` enum('Si','No') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_parte` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo','cuarentena','scrap') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hallazgos`
--

INSERT INTO `hallazgos` (`id`, `id_usuario`, `fecha`, `job_order`, `no_ensamble`, `estacion`, `area_ubicacion`, `retrabajo`, `modelo`, `no_parte`, `observaciones`, `fecha_creacion`, `fecha_actualizacion`, `estado`) VALUES
(11, 3, '2025-07-21', '33137', '4-200-00110', 'Estación 9', 'soldadura', 'Si', '', '', 'Remate \r\nPieza mal soldada', '2025-07-21 12:50:39', '2025-07-21 15:34:14', 'activo'),
(22, 3, '2025-07-21', '33231', '4-200-00408', 'Estación 13', 'soldadura', 'Si', 'Puerta de la super ', '', '*Soldadura mal aplicada y bajo tamaño \r\n', '2025-07-21 14:56:36', '2025-07-21 15:05:48', 'inactivo'),
(23, 3, '2025-07-21', '689', '4-600-10208', 'Estación 1', 'Beam welder', 'Si', 'ED, WELDING PART PKG', '989', 'Mal', '2025-07-21 09:05:27', '2025-07-23 12:54:30', 'activo'),
(24, 2, '2025-07-21', '505905', '4-100-00322', 'Estación 1', 'Prensas', 'Si', 'ED, 32\' X 48\"SW ROLLED BODY ASSY', '4554', 'nmal', '2025-07-21 09:35:15', '2025-07-23 12:55:36', 'scrap'),
(25, 2, '2025-07-21', '7878', '4-600-10208', 'Estación 10', 'soldadura', 'Si', 'ED, WELDING PART PKG', '', 'Bububugug', '2025-07-21 09:49:11', '2025-07-21 15:49:53', 'cuarentena'),
(26, 3, '2025-07-23', '33363', '4-200-00018', 'Estación 12', 'soldadura', 'Si', '32\' ED, DRAFT ARM FRAME ASSY', '', 'Remate ', '2025-07-23 07:18:21', '2025-07-23 16:24:36', 'inactivo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hallazgos_defectos`
--

CREATE TABLE `hallazgos_defectos` (
  `id` int NOT NULL,
  `hallazgo_id` int NOT NULL,
  `defecto` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hallazgos_defectos`
--

INSERT INTO `hallazgos_defectos` (`id`, `hallazgo_id`, `defecto`, `fecha_creacion`) VALUES
(28, 11, 'Crater', '2025-07-21 12:50:39'),
(39, 22, 'Porosidad', '2025-07-21 14:56:36'),
(40, 23, 'cordón cargado hacia el flange', '2025-07-21 09:05:27'),
(41, 24, 'Daño en pieza al doblar', '2025-07-21 09:35:15'),
(42, 25, 'Otros', '2025-07-21 09:49:11'),
(43, 26, 'Grietas', '2025-07-23 07:18:21'),
(44, 26, 'Porosidad', '2025-07-23 07:18:21'),
(45, 26, 'Otros', '2025-07-23 07:18:21'),
(46, 26, 'Crater', '2025-07-23 07:18:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hallazgos_evidencias`
--

CREATE TABLE `hallazgos_evidencias` (
  `id` int NOT NULL,
  `hallazgo_id` int NOT NULL,
  `archivo_nombre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `archivo_original` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tamaÃ±o_archivo` int DEFAULT NULL,
  `tipo_mime` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hallazgos_evidencias`
--

INSERT INTO `hallazgos_evidencias` (`id`, `hallazgo_id`, `archivo_nombre`, `archivo_original`, `fecha_subida`, `tamaÃ±o_archivo`, `tipo_mime`) VALUES
(25, 11, 'evid_687e379f1d73b_11.jpg', '17531014213951156559263462779957.jpg', '2025-07-21 12:50:39', NULL, NULL),
(26, 11, 'evid_687e379f28ea8_11.jpg', '17531014567083398454962230868143.jpg', '2025-07-21 12:50:39', NULL, NULL),
(41, 22, 'evid_687e5524c6e78_22.jpg', '17531095042448168529971064731719.jpg', '2025-07-21 14:56:36', NULL, NULL),
(42, 22, 'evid_687e5524d5176_22.jpg', '17531095840286294610930394308390.jpg', '2025-07-21 14:56:36', NULL, NULL),
(43, 23, 'evid_687e57378f60a_23.jpg', '1000000357.jpg', '2025-07-21 09:05:27', NULL, NULL),
(44, 23, 'evid_687e5737965c5_23.jpg', '1000000356.jpg', '2025-07-21 09:05:27', NULL, NULL),
(45, 23, 'evid_687e57379c501_23.jpg', '1000000355.jpg', '2025-07-21 09:05:27', NULL, NULL),
(46, 23, 'evid_687e5737a201a_23.jpg', '1000000350.jpg', '2025-07-21 09:05:27', NULL, NULL),
(47, 23, 'evid_687e5737a7ef6_23.jpg', '1000000351.jpg', '2025-07-21 09:05:27', NULL, NULL),
(48, 23, 'evid_687e5737ae21e_23.jpg', '1000000352.jpg', '2025-07-21 09:05:27', NULL, NULL),
(49, 23, 'evid_687e5737b409d_23.jpg', '1000000358.jpg', '2025-07-21 09:05:27', NULL, NULL),
(50, 23, 'evid_687e5737bb738_23.jpg', '1000000353.jpg', '2025-07-21 09:05:27', NULL, NULL),
(51, 23, 'evid_687e5737c2e31_23.jpg', '1000000358.jpg', '2025-07-21 09:05:27', NULL, NULL),
(52, 23, 'evid_687e5737c8e0d_23.jpg', '1000000357.jpg', '2025-07-21 09:05:27', NULL, NULL),
(53, 23, 'evid_687e5737cdb48_23.jpg', '1000000356.jpg', '2025-07-21 09:05:27', NULL, NULL),
(54, 23, 'evid_687e5737d3b9f_23.jpg', '1000000355.jpg', '2025-07-21 09:05:27', NULL, NULL),
(55, 23, 'evid_687e5737d7bfb_23.jpg', '1000000354.jpg', '2025-07-21 09:05:27', NULL, NULL),
(56, 23, 'evid_687e5737de8c5_23.jpg', '1000000353.jpg', '2025-07-21 09:05:27', NULL, NULL),
(57, 23, 'evid_687e5737e51f9_23.jpg', '1000000352.jpg', '2025-07-21 09:05:27', NULL, NULL),
(58, 23, 'evid_687e5737eb338_23.jpg', '1000000350.jpg', '2025-07-21 09:05:27', NULL, NULL),
(59, 23, 'evid_687e5737f081b_23.jpg', '1000000357.jpg', '2025-07-21 09:05:28', NULL, NULL),
(60, 23, 'evid_687e573803336_23.jpg', '1000000356.jpg', '2025-07-21 09:05:28', NULL, NULL),
(61, 24, 'evid_687e5e3379a91_24.jpg', 'Image (40).jpg', '2025-07-21 09:35:15', NULL, NULL),
(62, 24, 'evid_687e5e338efdc_24.jpg', 'Image (50).jpg', '2025-07-21 09:35:15', NULL, NULL),
(63, 24, 'evid_687e5e33913db_24.jpg', 'Image (40).jpg', '2025-07-21 09:35:15', NULL, NULL),
(64, 25, 'evid_687e617795979_25.jpg', '1000000357.jpg', '2025-07-21 09:49:11', NULL, NULL),
(65, 25, 'evid_687e61779b2b3_25.jpg', '1000000357.jpg', '2025-07-21 09:49:11', NULL, NULL),
(66, 26, 'evid_6880e11daa8ac_26.jpg', '17532765072568275276800519886282.jpg', '2025-07-23 07:18:21', NULL, NULL),
(67, 26, 'evid_6880e11db0c18_26.jpg', '17532763041835658084938765741523.jpg', '2025-07-23 07:18:21', NULL, NULL),
(68, 26, 'evid_6880e11db63c2_26.jpg', '17532763389052641722621446301448.jpg', '2025-07-23 07:18:21', NULL, NULL),
(69, 26, 'evid_6880e11dbe1df_26.jpg', '17532763850485654354104301054431.jpg', '2025-07-23 07:18:21', NULL, NULL),
(70, 26, 'evid_6880e11dc8442_26.jpg', '17532765072568275276800519886282.jpg', '2025-07-23 07:18:21', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `scrap_records`
--

CREATE TABLE `scrap_records` (
  `id` int NOT NULL,
  `hallazgo_id` int NOT NULL,
  `modelo` varchar(255) DEFAULT NULL,
  `no_parte` varchar(255) DEFAULT NULL,
  `no_ensamble` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `fecha_scrap` datetime DEFAULT CURRENT_TIMESTAMP,
  `usuario_scrap` int DEFAULT NULL,
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `scrap_records`
--

INSERT INTO `scrap_records` (`id`, `hallazgo_id`, `modelo`, `no_parte`, `no_ensamble`, `precio`, `fecha_scrap`, `usuario_scrap`, `observaciones`) VALUES
(1, 24, 'ED, 32\' X 48\"SW ROLLED BODY ASSY', '4554', '4-100-00322', 4784.00, '2025-07-21 15:43:12', 1, 'gdhhd'),
(2, 23, 'ED, WELDING PART PKG', '989', '4-600-10208', 7848.00, '2025-07-21 15:43:25', 1, 'ghfghfg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL,
  `rol` enum('calidad','encargado') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `contrasena`, `rol`) VALUES
(1, 'Encargado General', 'encargado@empresa.com', '81dc9bdb52d04dc20036dbd8313ed055', 'encargado'),
(2, 'Samuel ', 'calidad@empresa.com', '81dc9bdb52d04dc20036dbd8313ed055', 'calidad'),
(3, 'Samuel', 'samuel.calidad@brazostrailers.com', '81dc9bdb52d04dc20036dbd8313ed055', 'calidad');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `hallazgos`
--
ALTER TABLE `hallazgos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`id_usuario`),
  ADD KEY `idx_fecha` (`fecha`),
  ADD KEY `idx_estacion` (`estacion`),
  ADD KEY `idx_area` (`area_ubicacion`);

--
-- Indices de la tabla `hallazgos_defectos`
--
ALTER TABLE `hallazgos_defectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hallazgo` (`hallazgo_id`),
  ADD KEY `idx_defecto` (`defecto`);

--
-- Indices de la tabla `hallazgos_evidencias`
--
ALTER TABLE `hallazgos_evidencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hallazgo` (`hallazgo_id`);

--
-- Indices de la tabla `scrap_records`
--
ALTER TABLE `scrap_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_scrap` (`usuario_scrap`),
  ADD KEY `idx_hallazgo_id` (`hallazgo_id`),
  ADD KEY `idx_fecha_scrap` (`fecha_scrap`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `hallazgos`
--
ALTER TABLE `hallazgos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `hallazgos_defectos`
--
ALTER TABLE `hallazgos_defectos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `hallazgos_evidencias`
--
ALTER TABLE `hallazgos_evidencias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `scrap_records`
--
ALTER TABLE `scrap_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `hallazgos_defectos`
--
ALTER TABLE `hallazgos_defectos`
  ADD CONSTRAINT `hallazgos_defectos_ibfk_1` FOREIGN KEY (`hallazgo_id`) REFERENCES `hallazgos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `hallazgos_evidencias`
--
ALTER TABLE `hallazgos_evidencias`
  ADD CONSTRAINT `hallazgos_evidencias_ibfk_1` FOREIGN KEY (`hallazgo_id`) REFERENCES `hallazgos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `scrap_records`
--
ALTER TABLE `scrap_records`
  ADD CONSTRAINT `scrap_records_ibfk_1` FOREIGN KEY (`hallazgo_id`) REFERENCES `hallazgos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scrap_records_ibfk_2` FOREIGN KEY (`usuario_scrap`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
