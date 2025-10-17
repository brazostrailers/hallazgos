<?php
// Limpiar cualquier output previo
ob_start();

require_once 'db_config.php';

// Limpiar buffer y establecer headers
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    $estado = $_GET['estado'] ?? '';
    $area = $_GET['area'] ?? '';
    $modelo = $_GET['modelo'] ?? '';
    $usuario = $_GET['usuario'] ?? '';
    $fechaInicio = $_GET['fechaInicio'] ?? '';
    $fechaFin = $_GET['fechaFin'] ?? '';
    $retrabajo = $_GET['retrabajo'] ?? '';
    $hallazgo_id = $_GET['hallazgo_id'] ?? ''; // Nuevo parámetro para obtener hallazgo específico
    
    // Construir la consulta base con JOIN para obtener defectos
    $sql = "SELECT h.*, u.nombre as usuario_nombre,
                   GROUP_CONCAT(DISTINCT hd.defecto ORDER BY hd.defecto SEPARATOR ', ') as defectos,
                   COUNT(DISTINCT hd.id) as total_defectos,
                   COUNT(DISTINCT he.id) as total_evidencias,
                   h.cantidad_piezas
            FROM hallazgos h 
            LEFT JOIN usuarios u ON h.id_usuario = u.id 
            LEFT JOIN hallazgos_defectos hd ON h.id = hd.hallazgo_id
            LEFT JOIN hallazgos_evidencias he ON h.id = he.hallazgo_id
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Si se solicita un hallazgo específico, filtrar por ID
    if (!empty($hallazgo_id)) {
        $sql .= " AND h.id = ?";
        $params[] = $hallazgo_id;
        $types .= 'i';
    } else {
    if (!empty($estado)) {
        if ($estado === 'activo') {
            $sql .= " AND h.estado = 'activo'";
        } elseif ($estado === 'cuarentena') {
            $sql .= " AND h.estado = 'cuarentena'";
        } elseif ($estado === 'no_cuarentena') {
            $sql .= " AND h.estado != 'cuarentena'";
        } elseif ($estado === 'activo,inactivo') {
            $sql .= " AND h.estado IN ('activo', 'inactivo')";
        } elseif ($estado === 'activo,inactivo,cerrada') { // NUEVO: incluir cerradas en el listado principal
            $sql .= " AND h.estado IN ('activo','inactivo','cerrada')";
        } else {
            $sql .= " AND h.estado = ?";
            $params[] = $estado;
            $types .= 's';
        }
    }
    
    // Aplicar filtros
    if (!empty($area)) {
        $sql .= " AND h.area_ubicacion LIKE ?";
        $params[] = "%$area%";
        $types .= 's';
    }
    
    if (!empty($modelo)) {
        $sql .= " AND h.modelo LIKE ?";
        $params[] = "%$modelo%";
        $types .= 's';
    }
    
    if (!empty($usuario)) {
        $sql .= " AND (u.nombre LIKE ? OR h.id_usuario = ?)";
        $params[] = "%$usuario%";
        $params[] = $usuario;
        $types .= 'ss';
    }
    
    if (!empty($fechaInicio)) {
        $sql .= " AND DATE(h.fecha) >= ?";
        $params[] = $fechaInicio;
        $types .= 's';
    }
    
    if (!empty($fechaFin)) {
        $sql .= " AND DATE(h.fecha) <= ?";
        $params[] = $fechaFin;
        $types .= 's';
    }
    
    if (!empty($retrabajo)) {
        $sql .= " AND h.retrabajo = ?";
        $params[] = $retrabajo;
        $types .= 's';
    }
    
    } // Cerrar else de hallazgo_id
    
    // Agrupar por hallazgo y ordenar
    $sql .= " GROUP BY h.id ORDER BY h.fecha DESC, h.id DESC";
    
    // Preparar y ejecutar la consulta
    $stmt = $mysqli->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $response = [
        'success' => true,
        'data' => $data,
        'count' => count($data),
        'query' => $sql,
        'params' => $params,
        'filters' => [
            'estado' => $estado,
            'area' => $area,
            'modelo' => $modelo,
            'usuario' => $usuario,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'retrabajo' => $retrabajo
        ]
    ];
    
    // Limpiar cualquier output adicional antes de enviar JSON
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Limpiar buffer en caso de error
    ob_clean();
    
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ];
    
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
}

$mysqli->close();

// Limpiar y terminar
ob_end_flush();
exit;
?>
