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
    $retrabajo = $data['retrabajo'] ?? $_POST['retrabajo'] ?? '';
    
    // Validar datos
    if (empty($id) || empty($retrabajo)) {
        $response = [
            'success' => false,
            'message' => 'ID y retrabajo son requeridos'
        ];
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validar valores permitidos para retrabajo
    $retrabajoPermitidos = ['Si', 'No'];
    if (!in_array($retrabajo, $retrabajoPermitidos)) {
        $response = [
            'success' => false,
            'message' => 'Valor de retrabajo no válido. Valores permitidos: ' . implode(', ', $retrabajoPermitidos)
        ];
        ob_clean();
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Verificar que el hallazgo existe
    $checkSql = "SELECT id, retrabajo FROM hallazgos WHERE id = ?";
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
    $retrabajoAnterior = $hallazgo['retrabajo'];
    
    // Actualizar retrabajo
    $updateSql = "UPDATE hallazgos SET retrabajo = ?, fecha_actualizacion = NOW() WHERE id = ?";
    $updateStmt = $mysqli->prepare($updateSql);
    $updateStmt->bind_param("si", $retrabajo, $id);
    
    if ($updateStmt->execute()) {
        $response = [
            'success' => true,
            'message' => "Retrabajo cambiado de '$retrabajoAnterior' a '$retrabajo' exitosamente",
            'hallazgo_id' => $id,
            'retrabajo_anterior' => $retrabajoAnterior,
            'retrabajo_nuevo' => $retrabajo
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error al actualizar el retrabajo: ' . $mysqli->error
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
