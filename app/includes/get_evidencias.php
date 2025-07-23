<?php
// Limpiar cualquier output previo
ob_start();

require_once 'db_config.php';

// Limpiar buffer y establecer headers
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    $id = $_GET['id'] ?? '';
    
    // Validar ID
    if (empty($id) || !is_numeric($id)) {
        $response = [
            'success' => false,
            'message' => 'ID de hallazgo requerido'
        ];
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Buscar evidencias del hallazgo en la tabla hallazgos_evidencias
    $stmt = $mysqli->prepare("
        SELECT archivo_nombre, archivo_original, tamaño_archivo, tipo_mime
        FROM hallazgos_evidencias 
        WHERE hallazgo_id = ?
        ORDER BY fecha_subida ASC
    ");
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $evidencias = [];
    while ($row = $result->fetch_assoc()) {
        $evidencias[] = [
            'archivo' => $row['archivo_nombre'],
            'nombre_original' => $row['archivo_original'],
            'tamaño' => $row['tamaño_archivo'],
            'tipo' => $row['tipo_mime']
        ];
    }
    
    $response = [
        'success' => true,
        'evidencias' => $evidencias,
        'count' => count($evidencias)
    ];
    
    // Limpiar cualquier output adicional antes de enviar JSON
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Limpiar buffer en caso de error
    ob_clean();
    
    $errorResponse = [
        'success' => false,
        'message' => 'Error al obtener evidencias: ' . $e->getMessage()
    ];
    
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
}

$mysqli->close();

// Limpiar y terminar
ob_end_flush();
exit;
?>
