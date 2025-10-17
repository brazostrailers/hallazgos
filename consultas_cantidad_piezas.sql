-- Consultas SQL propuestas para integrar cantidad_piezas en dashboard_data.php

-- 1. ESTADÍSTICAS MEJORADAS CON PIEZAS
-- Total de piezas procesadas
SELECT SUM(cantidad_piezas) as total_piezas FROM hallazgos h $whereClause

-- Total de piezas defectuosas (con hallazgos)
SELECT SUM(h.cantidad_piezas) as total_piezas_defectuosas
FROM hallazgos h 
INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
$whereClause

-- Piezas en cuarentena
SELECT SUM(cantidad_piezas) as piezas_cuarentena 
FROM hallazgos h 
$whereClause AND h.estado = 'cuarentena'

-- 2. GRÁFICOS MEJORADOS CON CANTIDAD DE PIEZAS

-- Áreas: Piezas afectadas por área
SELECT h.area_ubicacion, 
       COUNT(*) as total_hallazgos,
       SUM(h.cantidad_piezas) as total_piezas_afectadas,
       AVG(h.cantidad_piezas) as promedio_piezas_por_hallazgo
FROM hallazgos h 
INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
$whereClause 
GROUP BY h.area_ubicacion 
ORDER BY total_piezas_afectadas DESC
LIMIT 10

-- Modelos: Impacto real por modelo
SELECT h.modelo, 
       COUNT(*) as total_hallazgos,
       SUM(h.cantidad_piezas) as total_piezas_defectuosas,
       AVG(h.cantidad_piezas) as promedio_piezas_por_hallazgo
FROM hallazgos h 
INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
$whereClause 
GROUP BY h.modelo 
ORDER BY total_piezas_defectuosas DESC
LIMIT 10

-- Usuarios: Eficiencia por usuario
SELECT u.nombre, 
       COUNT(*) as total_hallazgos_reportados,
       SUM(h.cantidad_piezas) as total_piezas_identificadas,
       AVG(h.cantidad_piezas) as promedio_piezas_por_reporte
FROM hallazgos h 
INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
INNER JOIN usuarios u ON h.id_usuario = u.id
$whereClause 
GROUP BY h.id_usuario, u.nombre 
ORDER BY total_piezas_identificadas DESC
LIMIT 10

-- No. Parte: Partes más problemáticas por volumen
SELECT h.no_parte, 
       COUNT(*) as frecuencia_problemas,
       SUM(h.cantidad_piezas) as total_piezas_afectadas,
       AVG(h.cantidad_piezas) as promedio_piezas_por_problema
FROM hallazgos h 
INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
$whereClause 
GROUP BY h.no_parte 
ORDER BY total_piezas_afectadas DESC
LIMIT 10

-- 3. NUEVAS GRÁFICAS ESPECÍFICAS PARA CANTIDAD_PIEZAS

-- Distribución de hallazgos por cantidad de piezas
SELECT 
  CASE 
    WHEN cantidad_piezas = 1 THEN '1 pieza'
    WHEN cantidad_piezas BETWEEN 2 AND 5 THEN '2-5 piezas'
    WHEN cantidad_piezas BETWEEN 6 AND 20 THEN '6-20 piezas'
    WHEN cantidad_piezas BETWEEN 21 AND 50 THEN '21-50 piezas'
    ELSE 'Más de 50 piezas'
  END as rango_cantidad,
  COUNT(*) as frecuencia,
  SUM(cantidad_piezas) as total_piezas_rango,
  AVG(cantidad_piezas) as promedio_rango
FROM hallazgos h 
$whereClause
GROUP BY rango_cantidad
ORDER BY MIN(cantidad_piezas)

-- Tendencia temporal de piezas afectadas
SELECT DATE(h.fecha_creacion) as fecha,
       COUNT(*) as total_hallazgos,
       SUM(h.cantidad_piezas) as total_piezas_afectadas,
       AVG(h.cantidad_piezas) as promedio_piezas_diario
FROM hallazgos h 
WHERE h.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(h.fecha_creacion)
ORDER BY fecha ASC

-- Top defectos por impacto en piezas
SELECT d.nombre_defecto,
       COUNT(*) as frecuencia,
       SUM(h.cantidad_piezas) as total_piezas_afectadas,
       AVG(h.cantidad_piezas) as promedio_piezas_por_defecto
FROM hallazgos h 
INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
INNER JOIN defectos d ON hd.defecto_id = d.id
$whereClause 
GROUP BY d.id, d.nombre_defecto 
ORDER BY total_piezas_afectadas DESC
LIMIT 10

-- 4. ANÁLISIS DE EFICIENCIA Y PRODUCTIVIDAD

-- Eficiencia por área (piezas procesadas vs piezas defectuosas)
-- Esta consulta requiere datos adicionales de producción total
-- pero muestra el concepto para análisis futuro
SELECT h.area_ubicacion,
       COUNT(*) as hallazgos_reportados,
       SUM(h.cantidad_piezas) as piezas_defectuosas,
       -- Aquí se podría agregar total_piezas_producidas de otra tabla
       -- ROUND((SUM(h.cantidad_piezas) / total_produccion) * 100, 2) as porcentaje_defectos
FROM hallazgos h 
$whereClause
GROUP BY h.area_ubicacion
ORDER BY piezas_defectuosas DESC

-- 5. INTEGRACIÓN CON SCRAP (MEJORAS PARA scrap_data.php)

-- Costo real por pieza en scrap
SELECT sr.modelo,
       COUNT(*) as registros_scrap,
       SUM(sr.precio) as dinero_perdido_total,
       SUM(h.cantidad_piezas) as total_piezas_scrap,
       ROUND(SUM(sr.precio) / SUM(h.cantidad_piezas), 2) as costo_promedio_por_pieza,
       AVG(h.cantidad_piezas) as promedio_piezas_por_scrap
FROM scrap_records sr
LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
$whereClause
GROUP BY sr.modelo
ORDER BY dinero_perdido_total DESC

-- Análisis temporal de scrap por piezas
SELECT DATE(sr.fecha_scrap) as fecha,
       COUNT(*) as registros_scrap,
       SUM(sr.precio) as dinero_perdido,
       SUM(h.cantidad_piezas) as piezas_enviadas_scrap,
       ROUND(SUM(sr.precio) / SUM(h.cantidad_piezas), 2) as costo_por_pieza_diario
FROM scrap_records sr
LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
WHERE sr.fecha_scrap >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
GROUP BY DATE(sr.fecha_scrap)
ORDER BY fecha ASC
