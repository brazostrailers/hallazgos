<?php
// Limpiar cualquier output previo
ob_start();

require_once 'db_config.php';

// Limpiar buffer y establecer headers
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = [
        'success' => false,
        'message' => 'Método no permitido'
    ];
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Leer datos JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $id = $data['id'] ?? $_POST['id'] ?? '';
    $estado = $data['estado'] ?? $_POST['estado'] ?? '';
    
    // Validar datos
    if (empty($id) || empty($estado)) {
        $response = [
            'success' => false,
            'message' => 'ID y estado son requeridos'
        ];
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validar estados permitidos - asegurar que coincidan exactamente con la DB
    $estadosPermitidos = ['activo', 'inactivo', 'cuarentena', 'scrap'];
    if (!in_array($estado, $estadosPermitidos)) {
        $response = [
            'success' => false,
            'message' => 'Estado no válido. Estados permitidos: ' . implode(', ', $estadosPermitidos)
        ];
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Verificar que el hallazgo existe
    $checkSql = "SELECT id, estado FROM hallazgos WHERE id = ?";
    $checkStmt = $mysqli->prepare($checkSql);
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $response = [
            'success' => false,
            'message' => 'Hallazgo no encontrado'
        ];
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $hallazgo = $result->fetch_assoc();
    $estadoAnterior = $hallazgo['estado'];
    
    // Log para debug
    error_log("Cambiando estado: ID=$id, de '$estadoAnterior' a '$estado'");
    
    // Actualizar estado
    $updateSql = "UPDATE hallazgos SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?";
    $updateStmt = $mysqli->prepare($updateSql);
    $updateStmt->bind_param("si", $estado, $id);
    
    if ($updateStmt->execute()) {
        if ($updateStmt->affected_rows > 0) {
            $response = [
                'success' => true,
                'message' => "Estado cambiado de '$estadoAnterior' a '$estado' exitosamente",
                'hallazgo_id' => $id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estado
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No se pudo actualizar el estado - ninguna fila afectada'
            ];
        }
    } else {
        // Log detallado del error
        $error = $mysqli->error;
        $errno = $mysqli->errno;
        error_log("Error MySQL: [$errno] $error al cambiar estado a '$estado'");
        
        $response = [
            'success' => false,
            'message' => 'Error al actualizar el estado: ' . $error,
            'debug_info' => [
                'estado_enviado' => $estado,
                'mysql_error' => $error,
                'mysql_errno' => $errno
            ]
        ];
    }
    
    // Limpiar cualquier output adicional antes de enviar JSON
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Limpiar buffer en caso de error
    ob_clean();
    
    $errorResponse = [
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage(),
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
