<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    // Obtener filtros de la URL
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $area = $_GET['area'] ?? '';
    
    // Construir la cláusula WHERE base
    $whereConditions = [];
    $params = [];
    $types = '';
    
    // Filtro por fechas
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $whereConditions[] = "DATE(h.fecha_creacion) BETWEEN ? AND ?";
        $params[] = $fecha_inicio;
        $params[] = $fecha_fin;
        $types .= 'ss';
    } elseif (!empty($fecha_inicio)) {
        $whereConditions[] = "DATE(h.fecha_creacion) >= ?";
        $params[] = $fecha_inicio;
        $types .= 's';
    } elseif (!empty($fecha_fin)) {
        $whereConditions[] = "DATE(h.fecha_creacion) <= ?";
        $params[] = $fecha_fin;
        $types .= 's';
    }
    
    // Filtro por área
    if (!empty($area)) {
        $whereConditions[] = "h.area_ubicacion = ?";
        $params[] = $area;
        $types .= 's';
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Inicializar estadísticas
    $stats = [
        'total' => 0,
        'con_hallazgos' => 0,
        'retrabajo' => 0,
        'cuarentena' => 0,
        'total_piezas' => 0,
        'piezas_defectuosas' => 0,
        'piezas_retrabajo' => 0,
        'piezas_cuarentena' => 0
    ];
    
    // 1. Total de registros y piezas
    $sqlTotal = "SELECT COUNT(*) as total, SUM(cantidad_piezas) as total_piezas FROM hallazgos h $whereClause";
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlTotal);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total'] = $row['total'];
        $stats['total_piezas'] = $row['total_piezas'] ?? 0;
    } else {
        $result = $mysqli->query($sqlTotal);
        $row = $result->fetch_assoc();
        $stats['total'] = $row['total'];
        $stats['total_piezas'] = $row['total_piezas'] ?? 0;
    }
    
    // 2. Registros con hallazgos (que tengan defectos) y piezas defectuosas
    $sqlConHallazgos = "
        SELECT COUNT(DISTINCT h.id) as total, SUM(h.cantidad_piezas) as piezas_defectuosas
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause";
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlConHallazgos);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['con_hallazgos'] = $row['total'];
        $stats['piezas_defectuosas'] = $row['piezas_defectuosas'] ?? 0;
    } else {
        $result = $mysqli->query($sqlConHallazgos);
        $row = $result->fetch_assoc();
        $stats['con_hallazgos'] = $row['total'];
        $stats['piezas_defectuosas'] = $row['piezas_defectuosas'] ?? 0;
    }
    
    // 3. Registros con retrabajo y piezas en retrabajo
    $whereClauseRetrabajo = $whereClause;
    $paramsRetrabajo = $params;
    $typesRetrabajo = $types;
    
    if (!empty($whereConditions)) {
        $whereClauseRetrabajo .= " AND h.retrabajo = 'Si'";
    } else {
        $whereClauseRetrabajo = "WHERE h.retrabajo = 'Si'";
    }
    
    $sqlRetrabajo = "SELECT COUNT(*) as total, SUM(cantidad_piezas) as piezas_retrabajo FROM hallazgos h $whereClauseRetrabajo";
    if (!empty($paramsRetrabajo)) {
        $stmt = $mysqli->prepare($sqlRetrabajo);
        $stmt->bind_param($typesRetrabajo, ...$paramsRetrabajo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['retrabajo'] = $row['total'];
        $stats['piezas_retrabajo'] = $row['piezas_retrabajo'] ?? 0;
    } else {
        $result = $mysqli->query($sqlRetrabajo);
        $row = $result->fetch_assoc();
        $stats['retrabajo'] = $row['total'];
        $stats['piezas_retrabajo'] = $row['piezas_retrabajo'] ?? 0;
    }
    
    // 4. Registros en cuarentena
    $whereClauseCuarentena = $whereClause;
    $paramsCuarentena = $params;
    $typesCuarentena = $types;
    
    if (!empty($whereConditions)) {
        $whereClauseCuarentena .= " AND h.estado = 'cuarentena'";
    } else {
        $whereClauseCuarentena = "WHERE h.estado = 'cuarentena'";
    }
    
    $sqlCuarentena = "SELECT COUNT(*) as total, SUM(cantidad_piezas) as piezas_cuarentena FROM hallazgos h $whereClauseCuarentena";
    if (!empty($paramsCuarentena)) {
        $stmt = $mysqli->prepare($sqlCuarentena);
        $stmt->bind_param($typesCuarentena, ...$paramsCuarentena);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['cuarentena'] = $row['total'];
        $stats['piezas_cuarentena'] = $row['piezas_cuarentena'] ?? 0;
    } else {
        $result = $mysqli->query($sqlCuarentena);
        $row = $result->fetch_assoc();
        $stats['cuarentena'] = $row['total'];
        $stats['piezas_cuarentena'] = $row['piezas_cuarentena'] ?? 0;
    }
    
    // Datos para gráficos por área
    $sqlAreaData = "
        SELECT h.area_ubicacion, 
               COUNT(*) as total_hallazgos,
               SUM(h.cantidad_piezas) as total_piezas_afectadas,
               AVG(h.cantidad_piezas) as promedio_piezas
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause 
        GROUP BY h.area_ubicacion 
        ORDER BY total_piezas_afectadas DESC
        LIMIT 10
    ";
    
    $areaData = ['labels' => [], 'data' => [], 'piezas' => [], 'promedio' => []];
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlAreaData);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query($sqlAreaData);
    }
    
    while ($row = $result->fetch_assoc()) {
        $areaData['labels'][] = $row['area_ubicacion'] ?? 'Sin área';
        $areaData['data'][] = (int)$row['total_hallazgos'];
        $areaData['piezas'][] = (int)$row['total_piezas_afectadas'];
        $areaData['promedio'][] = round((float)$row['promedio_piezas'], 2);
    }
    
    // Datos para gráficos por modelo
    $sqlModeloData = "
        SELECT h.modelo, 
               COUNT(*) as total_hallazgos,
               SUM(h.cantidad_piezas) as total_piezas_afectadas,
               AVG(h.cantidad_piezas) as promedio_piezas
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause 
        GROUP BY h.modelo 
        ORDER BY total_piezas_afectadas DESC
        LIMIT 10
    ";
    
    $modeloData = ['labels' => [], 'data' => [], 'piezas' => [], 'promedio' => []];
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlModeloData);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query($sqlModeloData);
    }
    
    while ($row = $result->fetch_assoc()) {
        $modeloData['labels'][] = $row['modelo'] ?? 'Sin modelo';
        $modeloData['data'][] = (int)$row['total_hallazgos'];
        $modeloData['piezas'][] = (int)$row['total_piezas_afectadas'];
        $modeloData['promedio'][] = round((float)$row['promedio_piezas'], 2);
    }
    
    // Datos para gráficos por usuario
    $sqlUsuarioData = "
        SELECT u.nombre, 
               COUNT(*) as total_hallazgos,
               SUM(h.cantidad_piezas) as total_piezas_identificadas,
               AVG(h.cantidad_piezas) as promedio_piezas
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        INNER JOIN usuarios u ON h.id_usuario = u.id
        $whereClause 
        GROUP BY h.id_usuario, u.nombre 
        ORDER BY total_piezas_identificadas DESC
        LIMIT 10
    ";
    
    $usuarioData = ['labels' => [], 'data' => [], 'piezas' => [], 'promedio' => []];
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlUsuarioData);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query($sqlUsuarioData);
    }
    
    while ($row = $result->fetch_assoc()) {
        $usuarioData['labels'][] = $row['nombre'] ?? 'Sin usuario';
        $usuarioData['data'][] = (int)$row['total_hallazgos'];
        $usuarioData['piezas'][] = (int)$row['total_piezas_identificadas'];
        $usuarioData['promedio'][] = round((float)$row['promedio_piezas'], 2);
    }
    
    // Datos para gráficos por no_parte
    $sqlNoParteData = "
        SELECT h.no_parte, 
               COUNT(*) as total_hallazgos,
               SUM(h.cantidad_piezas) as total_piezas_afectadas,
               AVG(h.cantidad_piezas) as promedio_piezas
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause 
        GROUP BY h.no_parte 
        ORDER BY total_piezas_afectadas DESC
        LIMIT 10
    ";
    
    $noParteData = ['labels' => [], 'data' => [], 'piezas' => [], 'promedio' => []];
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlNoParteData);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query($sqlNoParteData);
    }
    
    while ($row = $result->fetch_assoc()) {
        $noParteData['labels'][] = $row['no_parte'] ?? 'Sin no. parte';
        $noParteData['data'][] = (int)$row['total_hallazgos'];
        $noParteData['piezas'][] = (int)$row['total_piezas_afectadas'];
        $noParteData['promedio'][] = round((float)$row['promedio_piezas'], 2);
    }
    
    // Datos para gráficos por defectos más reportados
    $sqlDefectosData = "
        SELECT hd.defecto, 
               COUNT(*) as total_hallazgos,
               SUM(h.cantidad_piezas) as total_piezas_afectadas,
               AVG(h.cantidad_piezas) as promedio_piezas
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause 
        GROUP BY hd.defecto 
        ORDER BY total_piezas_afectadas DESC
        LIMIT 10
    ";
    
    $defectosData = ['labels' => [], 'data' => [], 'piezas' => [], 'promedio' => []];
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlDefectosData);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query($sqlDefectosData);
    }
    
    while ($row = $result->fetch_assoc()) {
        $defectosData['labels'][] = $row['defecto'] ?? 'Sin defecto';
        $defectosData['data'][] = (int)$row['total_hallazgos'];
        $defectosData['piezas'][] = (int)$row['total_piezas_afectadas'];
        $defectosData['promedio'][] = round((float)$row['promedio_piezas'], 2);
    }
    
    // Tendencia semanal (últimos 7 días)
    $sqlTendencia = "
        SELECT DATE(h.fecha_creacion) as fecha, 
               COUNT(*) as total_hallazgos,
               SUM(h.cantidad_piezas) as total_piezas_afectadas
        FROM hallazgos h 
        WHERE h.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(h.fecha_creacion)
        ORDER BY fecha ASC
    ";
    
    $tendenciaData = ['labels' => [], 'data' => [], 'piezas' => []];
    $result = $mysqli->query($sqlTendencia);
    
    while ($row = $result->fetch_assoc()) {
        $tendenciaData['labels'][] = date('d/m', strtotime($row['fecha']));
        $tendenciaData['data'][] = (int)$row['total_hallazgos'];
        $tendenciaData['piezas'][] = (int)$row['total_piezas_afectadas'];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'charts' => [
            'areas' => $areaData,
            'modelos' => $modeloData,
            'usuarios' => $usuarioData,
            'no_parte' => $noParteData,
            'defectos' => $defectosData,
            'tendencia' => $tendenciaData
        ],
        'debug' => [
            'whereClause' => $whereClause,
            'params' => $params,
            'filters' => [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'area' => $area
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener datos: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
