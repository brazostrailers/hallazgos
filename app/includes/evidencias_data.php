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
    $checkSql = "SELECT id FROM hallazgos WHERE id = ?";
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
    
    // Buscar evidencias en la tabla hallazgos_evidencias (si existe)
    $evidencias = [];
    
    // Primero intentar con tabla de evidencias
    $evidenciasSql = "SELECT archivo_nombre as archivo, archivo_original as descripcion, fecha_subida 
                      FROM hallazgos_evidencias 
                      WHERE hallazgo_id = ? 
                      ORDER BY fecha_subida DESC";
    
    $evidenciasStmt = $mysqli->prepare($evidenciasSql);
    if ($evidenciasStmt) {
        $evidenciasStmt->bind_param('i', $hallazgo_id);
        $evidenciasStmt->execute();
        $evidenciasResult = $evidenciasStmt->get_result();
        
        while ($row = $evidenciasResult->fetch_assoc()) {
            $evidencias[] = $row;
        }
    }
    
    // Si no hay tabla de evidencias, buscar en el directorio uploads con patrón
    if (empty($evidencias)) {
        $uploadsDir = __DIR__ . '/../uploads/';
        $webUploadsDir = 'uploads/'; // Ruta web para mostrar imágenes
        $pattern = "evidencia_{$hallazgo_id}_*";
        $pattern2 = "evid_{$hallazgo_id}*";
        
        if (is_dir($uploadsDir)) {
            $files = glob($uploadsDir . $pattern);
            $files2 = glob($uploadsDir . $pattern2);
            $allFiles = array_merge($files, $files2);
            
            foreach ($allFiles as $file) {
                $fileName = basename($file);
                if (is_file($file)) {
                    $evidencias[] = [
                        'archivo' => $fileName,
                        'descripcion' => 'Evidencia fotográfica',
                        'fecha_subida' => date('Y-m-d H:i:s', filemtime($file)),
                        'size' => filesize($file),
                        'exists' => true
                    ];
                }
            }
        }
    }
    
    // Si aún no hay evidencias, buscar archivos que contengan el ID del hallazgo
    if (empty($evidencias)) {
        $uploadsDir = __DIR__ . '/../uploads/';
        
        if (is_dir($uploadsDir)) {
            $files = scandir($uploadsDir);
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($uploadsDir . $file)) {
                    // Buscar archivos que contengan el ID del hallazgo
                    if (strpos($file, (string)$hallazgo_id) !== false) {
                        $evidencias[] = [
                            'archivo' => $file,
                            'descripcion' => 'Evidencia fotográfica',
                            'fecha_subida' => date('Y-m-d H:i:s', filemtime($uploadsDir . $file)),
                            'size' => filesize($uploadsDir . $file),
                            'exists' => true
                        ];
                    }
                }
            }
        }
    }
    
    // Filtrar solo archivos de imagen
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    $evidenciasImagen = array_filter($evidencias, function($evidencia) use ($imageExtensions) {
        $extension = strtolower(pathinfo($evidencia['archivo'], PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    });
    
    echo json_encode([
        'success' => true,
        'evidencias' => array_values($evidenciasImagen),
        'count' => count($evidenciasImagen),
        'hallazgo_id' => $hallazgo_id,
        'debug' => [
            'total_encontradas' => count($evidencias),
            'imagenes_filtradas' => count($evidenciasImagen),
            'uploads_dir_exists' => is_dir(__DIR__ . '/../uploads/'),
            'uploads_dir_path' => __DIR__ . '/../uploads/',
            'uploads_dir_readable' => is_readable(__DIR__ . '/../uploads/'),
            'pattern_searched' => ["evidencia_{$hallazgo_id}_*", "evid_{$hallazgo_id}*"],
            'files_in_uploads' => is_dir(__DIR__ . '/../uploads/') ? count(glob(__DIR__ . '/../uploads/*')) : 0
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener evidencias: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}

$mysqli->close();
?>
