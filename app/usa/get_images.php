<?php
header('Content-Type: application/json');
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario']) || 
    ($_SESSION['usuario']['rol'] !== 'usa' && $_SESSION['usuario']['rol'] !== 'encargadousa')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['hallazgo_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing hallazgo_id parameter']);
    exit;
}

$hallazgo_id = (int)$_GET['hallazgo_id'];

try {
    $pdo = new PDO("mysql:host=hallazgos_db;port=3306;dbname=hallazgos;charset=utf8mb4", 
                   'usuario', 'secreto', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Obtener todas las evidencias del hallazgo
    $stmt = $pdo->prepare("SELECT archivo_nombre FROM hallazgos_usa_evidencias WHERE hallazgo_usa_id = ? ORDER BY id ASC");
    $stmt->execute([$hallazgo_id]);
    $evidencias = $stmt->fetchAll();
    
    $images = [];
    foreach ($evidencias as $evidencia) {
        $images[] = $evidencia['archivo_nombre'];
    }
    
    echo json_encode([
        'success' => true,
        'images' => $images,
        'total' => count($images)
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_images.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
