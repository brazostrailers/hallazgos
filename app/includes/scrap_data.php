<?php
require_once 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener parámetros de filtro
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $area = $_GET['area'] ?? '';
    $tipo_grafica = $_GET['tipo'] ?? 'mensual'; // mensual, diario, semanal
    
    // Construir WHERE clause base
    $whereConditions = [];
    $params = [];
    $types = '';
    
    // Filtro por fechas
    if (!empty($fecha_inicio)) {
        $whereConditions[] = "DATE(sr.fecha_scrap) >= ?";
        $params[] = $fecha_inicio;
        $types .= 's';
    }
    
    if (!empty($fecha_fin)) {
        $whereConditions[] = "DATE(sr.fecha_scrap) <= ?";
        $params[] = $fecha_fin;
        $types .= 's';
    }
    
    // Filtro por área
    if (!empty($area)) {
        $whereConditions[] = "h.area_ubicacion LIKE ?";
        $params[] = "%$area%";
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Consulta base para datos de scrap
    $baseSql = "SELECT sr.*, h.area_ubicacion, h.modelo as hallazgo_modelo, h.no_parte as hallazgo_no_parte,
                       u.nombre as usuario_nombre
                FROM scrap_records sr
                LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
                LEFT JOIN usuarios u ON sr.usuario_scrap = u.id
                $whereClause";
    
    // 1. Total general de dinero perdido
    $totalSql = "SELECT 
                    COUNT(*) as total_registros,
                    SUM(sr.precio) as total_perdido,
                    AVG(sr.precio) as promedio_perdido,
                    MIN(sr.precio) as min_perdido,
                    MAX(sr.precio) as max_perdido
                 FROM scrap_records sr
                 LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
                 $whereClause";
    
    $stmt = $mysqli->prepare($totalSql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalData = $stmt->get_result()->fetch_assoc();
    
    // 2. Datos para gráfica temporal (cumpliendo ONLY_FULL_GROUP_BY)
    $groupExpr = '';
    $labelExpr = '';
    
    switch ($tipo_grafica) {
        case 'diario':
            $groupExpr = "DATE(sr.fecha_scrap)";
            $labelExpr = "DATE_FORMAT(sr.fecha_scrap, '%Y-%m-%d')";
            break;
        case 'semanal':
            // Clave de agrupación por año-semana y etiqueta amigable
            $groupExpr = "YEARWEEK(sr.fecha_scrap, 1)";
            $labelExpr = "CONCAT('Semana ', WEEK(sr.fecha_scrap, 1), ' - ', YEAR(sr.fecha_scrap))";
            break;
        case 'mensual':
        default:
            $groupExpr = "DATE_FORMAT(sr.fecha_scrap, '%Y-%m')";
            $labelExpr = "DATE_FORMAT(sr.fecha_scrap, '%Y-%m')";
            break;
    }
    
    $temporalSql = "SELECT 
                        $groupExpr as periodo_key,
                        $labelExpr as periodo,
                        COUNT(*) as cantidad_registros,
                        SUM(sr.precio) as total_periodo,
                        AVG(sr.precio) as promedio_periodo
                    FROM scrap_records sr
                    LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
                    $whereClause
                    GROUP BY periodo_key, periodo
                    ORDER BY periodo_key ASC";
    
    $stmt = $mysqli->prepare($temporalSql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $temporalResult = $stmt->get_result();
    
    $temporalData = [];
    while ($row = $temporalResult->fetch_assoc()) {
        $temporalData[] = $row;
    }
    
    // 3. Datos por área
    $areaSql = "SELECT 
                    COALESCE(h.area_ubicacion, 'Sin área') as area,
                    COUNT(*) as cantidad_registros,
                    SUM(sr.precio) as total_area,
                    AVG(sr.precio) as promedio_area
                FROM scrap_records sr
                LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
                $whereClause
                GROUP BY h.area_ubicacion
                ORDER BY total_area DESC";
    
    $stmt = $mysqli->prepare($areaSql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $areaResult = $stmt->get_result();
    
    $areaData = [];
    while ($row = $areaResult->fetch_assoc()) {
        $areaData[] = $row;
    }
    
    // 4. Datos por modelo
    $modeloSql = "SELECT 
                    COALESCE(sr.modelo, 'Sin modelo') as modelo,
                    COUNT(*) as cantidad_registros,
                    SUM(sr.precio) as total_modelo,
                    AVG(sr.precio) as promedio_modelo
                  FROM scrap_records sr
                  LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
                  $whereClause
                  GROUP BY sr.modelo
                  ORDER BY total_modelo DESC
                  LIMIT 10";
    
    $stmt = $mysqli->prepare($modeloSql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $modeloResult = $stmt->get_result();
    
    $modeloData = [];
    while ($row = $modeloResult->fetch_assoc()) {
        $modeloData[] = $row;
    }
    
    // 5. Registros recientes
    $recentesSql = "SELECT sr.*, h.area_ubicacion,
                           u.nombre as usuario_nombre,
                           DATE_FORMAT(sr.fecha_scrap, '%d/%m/%Y %H:%i') as fecha_formateada
                    FROM scrap_records sr
                    LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
                    LEFT JOIN usuarios u ON sr.usuario_scrap = u.id
                    $whereClause
                    ORDER BY sr.fecha_scrap DESC
                    LIMIT 10";
    
    $stmt = $mysqli->prepare($recentesSql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $recentesResult = $stmt->get_result();
    
    $recentesData = [];
    while ($row = $recentesResult->fetch_assoc()) {
        $recentesData[] = $row;
    }
    
    // Respuesta final
    $response = [
        'success' => true,
        'resumen' => [
            'total_registros' => (int)($totalData['total_registros'] ?? 0),
            'total_perdido' => (float)($totalData['total_perdido'] ?? 0),
            'promedio_perdido' => (float)($totalData['promedio_perdido'] ?? 0),
            'min_perdido' => (float)($totalData['min_perdido'] ?? 0),
            'max_perdido' => (float)($totalData['max_perdido'] ?? 0)
        ],
        'temporal' => $temporalData,
        'por_area' => $areaData,
        'por_modelo' => $modeloData,
        'recientes' => $recentesData,
        'filtros' => [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'area' => $area,
            'tipo_grafica' => $tipo_grafica
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}

$mysqli->close();
?>
