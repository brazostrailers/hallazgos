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
    // Consulta basada en scrap_records (1 fila por registro de scrap) para evitar duplicados
    $sql = "SELECT 
                h.id AS id,                 -- mantener nombre 'id' para frontend (hallazgo)
                sr.id AS scrap_id,          -- id del registro de scrap
                h.fecha_creacion,
                h.area_ubicacion,
                h.modelo,
                h.no_parte,
                h.job_order,
                h.estacion,
                h.cantidad_piezas,
                u.nombre as usuario_nombre,
                COALESCE(defectos_count.total, 0) as total_defectos,
                COALESCE(evidencias_count.total, 0) as total_evidencias,
                sr.fecha_scrap,
                sr.precio as valor_scrap,
                sr.no_ensamble,
                sr.observaciones as scrap_observaciones
            FROM scrap_records sr
            LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
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
            ORDER BY sr.fecha_scrap DESC, sr.id DESC";

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
