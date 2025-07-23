<?php
require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $hallazgo_id = $_GET['hallazgo_id'] ?? '';
    
    if (empty($hallazgo_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de hallazgo requerido'
        ]);
        exit;
    }
    
    // Verificar que el hallazgo existe
    $checkSql = "SELECT id, modelo, no_parte, area_ubicacion FROM hallazgos WHERE id = ?";
    $checkStmt = $mysqli->prepare($checkSql);
    $checkStmt->bind_param('i', $hallazgo_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Hallazgo no encontrado'
        ]);
        exit;
    }
    
    $hallazgo = $checkResult->fetch_assoc();
    
    // Buscar defectos del hallazgo
    $defectosSql = "SELECT id, defecto, fecha_creacion 
                    FROM hallazgos_defectos 
                    WHERE hallazgo_id = ? 
                    ORDER BY fecha_creacion ASC";
    
    $defectosStmt = $mysqli->prepare($defectosSql);
    $defectosStmt->bind_param('i', $hallazgo_id);
    $defectosStmt->execute();
    $defectosResult = $defectosStmt->get_result();
    
    $defectos = [];
    while ($row = $defectosResult->fetch_assoc()) {
        $defectos[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'defectos' => $defectos,
        'count' => count($defectos),
        'hallazgo_id' => $hallazgo_id,
        'hallazgo_info' => $hallazgo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener defectos: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

$mysqli->close();
?>
