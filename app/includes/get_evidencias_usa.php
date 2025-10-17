<?php
header('Content-Type: application/json');
require_once 'db_config.php';

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID de hallazgo inválido');
    }
    
    $hallazgo_id = (int)$_GET['id'];
    
    // Verificar que el hallazgo existe
    $stmt = $pdo->prepare("SELECT id FROM hallazgos_usa WHERE id = ?");
    $stmt->execute([$hallazgo_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Hallazgo no encontrado');
    }
    
    // Obtener evidencias
    $stmt = $pdo->prepare("
        SELECT 
            id,
            archivo_nombre,
            archivo_original,
            fecha_subida,
            file_size,
            tipo_mime
        FROM hallazgos_usa_evidencias 
        WHERE hallazgo_usa_id = ?
        ORDER BY fecha_subida ASC
    ");
    $stmt->execute([$hallazgo_id]);
    $evidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear las fechas
    foreach ($evidencias as &$evidencia) {
        $evidencia['fecha_subida'] = date('M d, Y H:i', strtotime($evidencia['fecha_subida']));
        $evidencia['file_size'] = $evidencia['file_size'] ?? 0;
    }
    
    echo json_encode([
        'success' => true,
        'evidencias' => $evidencias,
        'total' => count($evidencias)
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_evidencias_usa.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
