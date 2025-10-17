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
    // Parámetros de filtro
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;
    $area = $_GET['area'] ?? null;

    $mysqli->set_charset('utf8mb4');

    $sql = "
        SELECT 
            CASE 
                WHEN h.no_parte IS NULL OR TRIM(h.no_parte) = '' THEN 'Sin número de parte' 
                ELSE h.no_parte 
            END AS no_parte,
            COALESCE(h.area_ubicacion, 'Sin área') AS area,
            SUM(sr.precio) AS total_perdido,
            COUNT(sr.id) AS total_registros
        FROM scrap_records sr
        INNER JOIN hallazgos h ON sr.hallazgo_id = h.id
        WHERE 1=1
    ";

    $params = [];
    $types = '';

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
    if ($area) {
        $sql .= " AND h.area_ubicacion = ?";
        $params[] = $area;
        $types .= 's';
    }

    $sql .= "
        GROUP BY h.no_parte, h.area_ubicacion
        HAVING total_perdido > 0
        ORDER BY total_perdido DESC
        LIMIT 15
    ";

    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $partes = [];
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
        'Diseño' => '#8BC34A',
        'Sin área' => '#999999'
    ];

    while ($row = $result->fetch_assoc()) {
        $partes[] = [
            'no_parte' => $row['no_parte'],
            'area' => $row['area'],
            'total_perdido' => (float)$row['total_perdido'],
            'total_registros' => (int)$row['total_registros'],
            'color' => $areas_colores[$row['area']] ?? '#999999'
        ];
    }

    $labels = [];
    $data = [];
    $backgroundColor = [];
    $borderColor = [];

    foreach ($partes as $p) {
        $labels[] = $p['no_parte'] . ' (' . $p['area'] . ')';
        $data[] = $p['total_perdido'];
        $backgroundColor[] = $p['color'] . '80';
        $borderColor[] = $p['color'];
    }

    $total_perdido_general = array_sum($data);
    $promedio_por_parte = count($data) > 0 ? $total_perdido_general / count($data) : 0;

    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Dinero Perdido (USD)',
                'data' => $data,
                'backgroundColor' => $backgroundColor,
                'borderColor' => $borderColor,
                'borderWidth' => 2
            ]]
        ],
        'partes_detalle' => $partes,
        'resumen' => [
            'total_perdido_general' => $total_perdido_general,
            'promedio_por_parte' => $promedio_por_parte,
            'total_partes' => count($partes)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener datos: ' . $e->getMessage()
    ]);
}

$mysqli->close();
?>
