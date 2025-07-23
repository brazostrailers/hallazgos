<?php
require_once 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $hallazgo_id = $_GET['hallazgo_id'] ?? '';
    
    if (empty($hallazgo_id)) {
        throw new Exception('ID de hallazgo requerido');
    }
    
    // Verificar que el hallazgo existe
    $checkSql = "SELECT COUNT(*) as count FROM hallazgos WHERE id = ?";
    $checkStmt = $mysqli->prepare($checkSql);
    $checkStmt->bind_param('i', $hallazgo_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $count = $checkResult->fetch_assoc()['count'];
    
    if ($count == 0) {
        throw new Exception('Hallazgo no encontrado');
    }
    
    // Obtener información del hallazgo con observaciones
    $hallazgoSql = "
        SELECT h.*, u.nombre as usuario_nombre
        FROM hallazgos h
        LEFT JOIN usuarios u ON h.id_usuario = u.id
        WHERE h.id = ?
    ";
    
    $hallazgoStmt = $mysqli->prepare($hallazgoSql);
    $hallazgoStmt->bind_param('i', $hallazgo_id);
    $hallazgoStmt->execute();
    $hallazgoResult = $hallazgoStmt->get_result();
    
    if ($hallazgoResult->num_rows === 0) {
        throw new Exception('No se pudo obtener la información del hallazgo');
    }
    
    $hallazgoInfo = $hallazgoResult->fetch_assoc();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'hallazgo_info' => $hallazgoInfo
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]
    ], JSON_UNESCAPED_UNICODE);
}

$mysqli->close();
?>
