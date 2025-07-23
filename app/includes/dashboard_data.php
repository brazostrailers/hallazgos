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
        'cuarentena' => 0
    ];
    
    // 1. Total de registros
    $sqlTotal = "SELECT COUNT(*) as total FROM hallazgos h $whereClause";
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlTotal);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['total'] = $result->fetch_assoc()['total'];
    } else {
        $result = $mysqli->query($sqlTotal);
        $stats['total'] = $result->fetch_assoc()['total'];
    }
    
    // 2. Registros con hallazgos (que tengan defectos)
    $sqlConHallazgos = "
        SELECT COUNT(DISTINCT h.id) as total 
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause";
    if (!empty($params)) {
        $stmt = $mysqli->prepare($sqlConHallazgos);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['con_hallazgos'] = $result->fetch_assoc()['total'];
    } else {
        $result = $mysqli->query($sqlConHallazgos);
        $stats['con_hallazgos'] = $result->fetch_assoc()['total'];
    }
    
    // 3. Registros con retrabajo
    $whereClauseRetrabajo = $whereClause;
    $paramsRetrabajo = $params;
    $typesRetrabajo = $types;
    
    if (!empty($whereConditions)) {
        $whereClauseRetrabajo .= " AND h.retrabajo = 'Si'";
    } else {
        $whereClauseRetrabajo = "WHERE h.retrabajo = 'Si'";
    }
    
    $sqlRetrabajo = "SELECT COUNT(*) as total FROM hallazgos h $whereClauseRetrabajo";
    if (!empty($paramsRetrabajo)) {
        $stmt = $mysqli->prepare($sqlRetrabajo);
        $stmt->bind_param($typesRetrabajo, ...$paramsRetrabajo);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['retrabajo'] = $result->fetch_assoc()['total'];
    } else {
        $result = $mysqli->query($sqlRetrabajo);
        $stats['retrabajo'] = $result->fetch_assoc()['total'];
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
    
    $sqlCuarentena = "SELECT COUNT(*) as total FROM hallazgos h $whereClauseCuarentena";
    if (!empty($paramsCuarentena)) {
        $stmt = $mysqli->prepare($sqlCuarentena);
        $stmt->bind_param($typesCuarentena, ...$paramsCuarentena);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['cuarentena'] = $result->fetch_assoc()['total'];
    } else {
        $result = $mysqli->query($sqlCuarentena);
        $stats['cuarentena'] = $result->fetch_assoc()['total'];
    }
    
    // Datos para gráficos por área
    $sqlAreaData = "
        SELECT h.area_ubicacion, COUNT(*) as total 
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause 
        GROUP BY h.area_ubicacion 
        ORDER BY total DESC
        LIMIT 10
    ";
    
    $areaData = ['labels' => [], 'data' => []];
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
        $areaData['data'][] = (int)$row['total'];
    }
    
    // Datos para gráficos por modelo
    $sqlModeloData = "
        SELECT h.modelo, COUNT(*) as total 
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause 
        GROUP BY h.modelo 
        ORDER BY total DESC
        LIMIT 10
    ";
    
    $modeloData = ['labels' => [], 'data' => []];
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
        $modeloData['data'][] = (int)$row['total'];
    }
    
    // Datos para gráficos por usuario
    $sqlUsuarioData = "
        SELECT u.nombre, COUNT(*) as total 
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        INNER JOIN usuarios u ON h.id_usuario = u.id
        $whereClause 
        GROUP BY h.id_usuario, u.nombre 
        ORDER BY total DESC
        LIMIT 10
    ";
    
    $usuarioData = ['labels' => [], 'data' => []];
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
        $usuarioData['data'][] = (int)$row['total'];
    }
    
    // Datos para gráficos por no_parte
    $sqlNoParteData = "
        SELECT h.no_parte, COUNT(*) as total 
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause 
        GROUP BY h.no_parte 
        ORDER BY total DESC
        LIMIT 10
    ";
    
    $noParteData = ['labels' => [], 'data' => []];
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
        $noParteData['data'][] = (int)$row['total'];
    }
    
    // Datos para gráficos por defectos más reportados
    $sqlDefectosData = "
        SELECT hd.defecto, COUNT(*) as total 
        FROM hallazgos h 
        INNER JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id 
        $whereClause 
        GROUP BY hd.defecto 
        ORDER BY total DESC
        LIMIT 10
    ";
    
    $defectosData = ['labels' => [], 'data' => []];
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
        $defectosData['data'][] = (int)$row['total'];
    }
    
    // Tendencia semanal (últimos 7 días)
    $sqlTendencia = "
        SELECT DATE(h.fecha_creacion) as fecha, COUNT(*) as total 
        FROM hallazgos h 
        WHERE h.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(h.fecha_creacion)
        ORDER BY fecha ASC
    ";
    
    $tendenciaData = ['labels' => [], 'data' => []];
    $result = $mysqli->query($sqlTendencia);
    
    while ($row = $result->fetch_assoc()) {
        $tendenciaData['labels'][] = date('d/m', strtotime($row['fecha']));
        $tendenciaData['data'][] = (int)$row['total'];
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
