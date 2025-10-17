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
    $fechaInicio = $_GET['fecha_inicio'] ?? null;
    $fechaFin = $_GET['fecha_fin'] ?? null;

    $mysqli->set_charset('utf8mb4');

    $sql = "
        SELECT 
            CASE 
                WHEN sr.observaciones IS NULL OR TRIM(sr.observaciones) = '' THEN 'Sin observación'
                ELSE sr.observaciones
            END AS observacion,
            SUM(COALESCE(h.cantidad_piezas, 0)) AS total_piezas
        FROM scrap_records sr
        LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
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

    $sql .= "
        GROUP BY observacion
        ORDER BY total_piezas DESC
        LIMIT 5
    ";

    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $labels = [];
    $data = [];

    while ($row = $res->fetch_assoc()) {
        $labels[] = $row['observacion'];
        $data[] = (int)$row['total_piezas'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Piezas',
                'data' => $data,
                'backgroundColor' => array_fill(0, count($data), 'rgba(54, 162, 235, 0.7)'),
                'borderColor' => array_fill(0, count($data), 'rgba(54, 162, 235, 1)'),
                'borderWidth' => 1
            ]]
        ],
        'resumen' => [
            'total_piezas' => array_sum($data)
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
