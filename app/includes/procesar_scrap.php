<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar autenticación - usar la estructura correcta de sesión
if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
    // Debug: mostrar información de la sesión si está en modo debug
    $debug_info = [
        'session_exists' => isset($_SESSION),
        'usuario_exists' => isset($_SESSION['usuario']),
        'session_vars' => array_keys($_SESSION ?? [])
    ];
    
    echo json_encode([
        'success' => false, 
        'message' => 'Usuario no autenticado',
        'debug' => $debug_info
    ]);
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $hallazgo_id = $input['hallazgo_id'] ?? null;
    $modelo = $input['modelo'] ?? '';
    $no_parte = $input['no_parte'] ?? '';
    $no_ensamble = $input['no_ensamble'] ?? '';
    $precio = $input['precio'] ?? 0;
    $observaciones = $input['observaciones'] ?? '';
    
    if (!$hallazgo_id) {
        echo json_encode(['success' => false, 'message' => 'ID de hallazgo requerido']);
        exit;
    }
    
    // Verificar que el hallazgo existe y está en cuarentena
    $check_sql = "SELECT id, estado FROM hallazgos WHERE id = ? AND estado = 'cuarentena'";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param('i', $hallazgo_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Hallazgo no encontrado o no está en cuarentena']);
        exit;
    }
    
    // Iniciar transacción
    $mysqli->begin_transaction();
    
    try {
        // Insertar registro en scrap_records
        $scrap_sql = "INSERT INTO scrap_records (hallazgo_id, modelo, no_parte, no_ensamble, precio, usuario_scrap, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $scrap_stmt = $mysqli->prepare($scrap_sql);
        $scrap_stmt->bind_param('isssdis', $hallazgo_id, $modelo, $no_parte, $no_ensamble, $precio, $usuario_id, $observaciones);
        $scrap_stmt->execute();
        
        // Actualizar estado del hallazgo a 'scrap'
        $update_sql = "UPDATE hallazgos SET estado = 'scrap', fecha_actualizacion = NOW() WHERE id = ?";
        $update_stmt = $mysqli->prepare($update_sql);
        $update_stmt->bind_param('i', $hallazgo_id);
        $update_stmt->execute();
        
        // Confirmar transacción
        $mysqli->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Registro enviado a scrap exitosamente',
            'scrap_id' => $mysqli->insert_id
        ]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar scrap: ' . $e->getMessage()
    ]);
}

$mysqli->close();
?>
