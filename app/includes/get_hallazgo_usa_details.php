<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID de hallazgo inválido');
    }
    
    $hallazgo_id = (int)$_GET['id'];
    
    // Obtener detalles del hallazgo
    $stmt = $pdo->prepare("
        SELECT 
            h.*,
            u.nombre as usuario_nombre,
            u.correo as usuario_correo
        FROM hallazgos_usa h
        LEFT JOIN usuarios u ON h.id_usuario = u.id
        WHERE h.id = ?
    ");
    $stmt->execute([$hallazgo_id]);
    $hallazgo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hallazgo) {
        throw new Exception('Hallazgo no encontrado');
    }
    
    // Formatear las fechas
    $hallazgo['fecha'] = date('M d, Y', strtotime($hallazgo['fecha']));
    $hallazgo['fecha_creacion'] = date('M d, Y H:i', strtotime($hallazgo['fecha_creacion']));
    
    // Obtener defectos adicionales si existen
    $stmt = $pdo->prepare("
        SELECT defecto 
        FROM hallazgos_usa_defectos 
        WHERE hallazgo_usa_id = ?
        ORDER BY id ASC
    ");
    $stmt->execute([$hallazgo_id]);
    $defectos_adicionales = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener total de evidencias
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_evidencias
        FROM hallazgos_usa_evidencias 
        WHERE hallazgo_usa_id = ?
    ");
    $stmt->execute([$hallazgo_id]);
    $total_evidencias = $stmt->fetch(PDO::FETCH_ASSOC)['total_evidencias'];
    
    echo json_encode([
        'success' => true,
        'hallazgo' => $hallazgo,
        'defectos_adicionales' => $defectos_adicionales,
        'total_evidencias' => $total_evidencias
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_hallazgo_usa_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
