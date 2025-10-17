<?php
/**
 * API para obtener hallazgos con sus defectos y evidencias
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=hallazgos;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = (int)($_GET['offset'] ?? 0);
    $hallazgo_id = $_GET['id'] ?? null;
    
    if ($hallazgo_id) {
        // Obtener un hallazgo específico
        $sql = "SELECT * FROM hallazgos WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hallazgo_id]);
        $hallazgo = $stmt->fetch();
        
        if (!$hallazgo) {
            throw new Exception("Hallazgo no encontrado");
        }
        
        // Obtener defectos
        $sql = "SELECT * FROM hallazgos_defectos WHERE hallazgo_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hallazgo_id]);
        $hallazgo['defectos'] = $stmt->fetchAll();
        
        // Obtener evidencias
        $sql = "SELECT * FROM hallazgos_evidencias WHERE hallazgo_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hallazgo_id]);
        $hallazgo['evidencias'] = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $hallazgo
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // Obtener lista de hallazgos
        $sql = "SELECT * FROM hallazgos ORDER BY fecha_creacion DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->query($sql);
        $hallazgos = $stmt->fetchAll();
        
        // Obtener defectos y evidencias para cada hallazgo
        foreach ($hallazgos as &$hallazgo) {
            // Defectos
            $sql = "SELECT * FROM hallazgos_defectos WHERE hallazgo_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$hallazgo['id']]);
            $hallazgo['defectos'] = $stmt->fetchAll();
            
            // Evidencias
            $sql = "SELECT * FROM hallazgos_evidencias WHERE hallazgo_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$hallazgo['id']]);
            $hallazgo['evidencias'] = $stmt->fetchAll();
        }
        
        // Contar total
        $sql = "SELECT COUNT(*) as total FROM hallazgos";
        $stmt = $pdo->query($sql);
        $total = $stmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $hallazgos,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($hallazgos)
            ]
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
