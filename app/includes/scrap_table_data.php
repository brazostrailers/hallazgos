<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db_config.php';

try {
    // Consulta completa para obtener registros en scrap con información de scrap_records
    $sql = "SELECT 
                h.id,
                h.fecha_creacion,
                h.area_ubicacion,
                h.modelo,
                h.no_parte,
                h.job_order,
                h.estacion,
                u.nombre as usuario_nombre,
                COALESCE(defectos_count.total, 0) as total_defectos,
                COALESCE(evidencias_count.total, 0) as total_evidencias,
                sr.fecha_scrap,
                sr.precio as valor_scrap,
                sr.no_ensamble,
                sr.observaciones as scrap_observaciones
            FROM hallazgos h
            LEFT JOIN usuarios u ON h.id_usuario = u.id
            LEFT JOIN (
                SELECT hallazgo_id, COUNT(*) as total 
                FROM hallazgos_defectos 
                GROUP BY hallazgo_id
            ) defectos_count ON h.id = defectos_count.hallazgo_id
            LEFT JOIN (
                SELECT hallazgo_id, COUNT(*) as total 
                FROM hallazgos_evidencias 
                GROUP BY hallazgo_id
            ) evidencias_count ON h.id = evidencias_count.hallazgo_id
            LEFT JOIN scrap_records sr ON h.id = sr.hallazgo_id
            WHERE h.estado = 'scrap'
            ORDER BY h.fecha_creacion DESC";

    $result = $mysqli->query($sql);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $mysqli->error);
    }
    
    $scrapData = [];
    while ($row = $result->fetch_assoc()) {
        $scrapData[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $scrapData,
        'count' => count($scrapData)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => []
    ]);
}

$mysqli->close();
?>
