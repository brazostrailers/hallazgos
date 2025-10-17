<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

try {
    // Obtener parámetros de filtro opcionales
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $area = $_GET['area'] ?? null;

    // Configurar charset para caracteres especiales
    $mysqli->set_charset("utf8mb4");

    // Construir la consulta SQL
    $sql = "
        SELECT 
            h.estacion,
            h.area_ubicacion as area,
            SUM(sr.precio) as total_perdido,
            COUNT(sr.id) as total_registros
        FROM scrap_records sr
        INNER JOIN hallazgos h ON sr.hallazgo_id = h.id
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    // Agregar filtros de fecha si se proporcionan (usar fecha de scrap; si falta, caer a fecha del hallazgo)
    if ($fechaInicio) {
        $sql .= " AND DATE(COALESCE(sr.fecha_scrap, h.fecha)) >= ?";
        $params[] = $fechaInicio;
        $types .= 's';
    }

    if ($fechaFin) {
        $sql .= " AND DATE(COALESCE(sr.fecha_scrap, h.fecha)) <= ?";
        $params[] = $fechaFin;
        $types .= 's';
    }

    // Agregar filtro de área si se proporciona
    if ($area) {
        $sql .= " AND h.area_ubicacion = ?";
        $params[] = $area;
        $types .= 's';
    }

    $sql .= "
        GROUP BY h.estacion, h.area_ubicacion
        ORDER BY total_perdido DESC
        LIMIT 15
    ";

    $stmt = $mysqli->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $estaciones_data = [];
    $areas_colores = [
        'Plasma' => '#FF6384',
        'Prensas' => '#36A2EB', 
        'Beam welder' => '#FFCE56',
        'Roladora' => '#4BC0C0',
        'Sierras' => '#9966FF',
        'Fresadora' => '#FF9F40',
        'Vulcanizadora' => '#FF6384',
        'soldadura' => '#C9CBCF',
        'ejes' => '#4BC0C0',
        'Diseño' => '#8BC34A'
    ];

    while ($row = $result->fetch_assoc()) {
        $estaciones_data[] = [
            'estacion' => $row['estacion'],
            'area' => $row['area'],
            'total_perdido' => floatval($row['total_perdido']),
            'total_registros' => intval($row['total_registros']),
            'color' => $areas_colores[$row['area']] ?? '#999999'
        ];
    }

    // Preparar datos para la gráfica
    $labels = [];
    $data = [];
    $backgroundColor = [];
    $borderColor = [];

    foreach ($estaciones_data as $item) {
        $labels[] = $item['estacion'] . ' (' . $item['area'] . ')';
        $data[] = $item['total_perdido'];
        $backgroundColor[] = $item['color'] . '80'; // 50% transparencia
        $borderColor[] = $item['color'];
    }

    // Calcular total general del mes/intervalo SIN limitar a Top 15 (coincidir con card)
    // Nota: para coincidir con la card (que suma scrap_records sin join),
    // incluimos también scraps sin hallazgo cuando NO hay filtro de área.
    $paramsTotal = [];
    $typesTotal = '';
    if ($area) {
        // Con filtro de área: unir con hallazgos para aplicar el filtro
        $totalSql = "SELECT COALESCE(SUM(sr.precio),0) AS total_general
                     FROM scrap_records sr
                     INNER JOIN hallazgos h ON sr.hallazgo_id = h.id
                     WHERE 1=1";
        if ($fechaInicio) { $totalSql .= " AND DATE(COALESCE(sr.fecha_scrap, h.fecha)) >= ?"; $paramsTotal[] = $fechaInicio; $typesTotal .= 's'; }
        if ($fechaFin)    { $totalSql .= " AND DATE(COALESCE(sr.fecha_scrap, h.fecha)) <= ?"; $paramsTotal[] = $fechaFin;  $typesTotal .= 's'; }
        $totalSql .= " AND h.area_ubicacion = ?"; $paramsTotal[] = $area; $typesTotal .= 's';
    } else {
        // Sin filtro de área: sumar directamente de scrap_records (incluye huérfanos)
        $totalSql = "SELECT COALESCE(SUM(sr.precio),0) AS total_general
                     FROM scrap_records sr
                     LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
                     WHERE 1=1";
        if ($fechaInicio) { $totalSql .= " AND DATE(COALESCE(sr.fecha_scrap, h.fecha)) >= ?"; $paramsTotal[] = $fechaInicio; $typesTotal .= 's'; }
        if ($fechaFin)    { $totalSql .= " AND DATE(COALESCE(sr.fecha_scrap, h.fecha)) <= ?"; $paramsTotal[] = $fechaFin;  $typesTotal .= 's'; }
    }

    $stmtTotal = $mysqli->prepare($totalSql);
    if (!empty($paramsTotal)) { $stmtTotal->bind_param($typesTotal, ...$paramsTotal); }
    $stmtTotal->execute();
    $rowTotal = $stmtTotal->get_result()->fetch_assoc();
    $total_perdido_general = (float)($rowTotal['total_general'] ?? 0);
    $stmtTotal->close();

    // Promedio sobre lo mostrado (Top 15)
    $promedio_por_estacion = count($data) > 0 ? $total_perdido_general / count($data) : 0;

    // Agrupar por área para resumen
    $resumen_areas = [];
    foreach ($estaciones_data as $item) {
        if (!isset($resumen_areas[$item['area']])) {
            $resumen_areas[$item['area']] = [
                'area' => $item['area'],
                'total_perdido' => 0,
                'total_estaciones' => 0,
                'total_registros' => 0
            ];
        }
        $resumen_areas[$item['area']]['total_perdido'] += $item['total_perdido'];
        $resumen_areas[$item['area']]['total_estaciones']++;
        $resumen_areas[$item['area']]['total_registros'] += $item['total_registros'];
    }

    // Ordenar resumen por total perdido
    uasort($resumen_areas, function($a, $b) {
        return $b['total_perdido'] <=> $a['total_perdido'];
    });

    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Dinero Perdido (USD)',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $borderColor,
                    'borderWidth' => 2
                ]
            ]
        ],
        'estaciones_detalle' => $estaciones_data,
        'resumen' => [
            'total_perdido_general' => $total_perdido_general,
            'promedio_por_estacion' => $promedio_por_estacion,
            'total_estaciones' => count($estaciones_data)
        ],
        'resumen_areas' => array_values($resumen_areas)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al obtener datos: ' . $e->getMessage()
    ]);
}

$mysqli->close();
?>
