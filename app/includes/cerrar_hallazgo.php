<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    // Verificar autenticación básica
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
        exit;
    }

    $hallazgo_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $fecha_cierre = $_POST['fecha_cierre'] ?? '';
    $solucion = $_POST['solucion'] ?? '';

    if ($hallazgo_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de hallazgo inválido']);
        exit;
    }

    if (empty($fecha_cierre)) {
        // Usar ahora si no viene
        $fecha_cierre = date('Y-m-d H:i:s');
    }

    // Asegurar que las columnas existan (fecha_cierre, solucion)
    $columnsNeeded = ['fecha_cierre' => "DATETIME NULL", 'solucion' => "TEXT NULL"];
    foreach ($columnsNeeded as $col => $definition) {
        $checkSql = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hallazgos' AND COLUMN_NAME = ?";
        $checkStmt = $mysqli->prepare($checkSql);
        $checkStmt->bind_param('s', $col);
        $checkStmt->execute();
        $res = $checkStmt->get_result()->fetch_assoc();
        if (intval($res['cnt'] ?? 0) === 0) {
            $alterSql = "ALTER TABLE hallazgos ADD COLUMN $col $definition";
            if (!$mysqli->query($alterSql)) {
                echo json_encode(['success' => false, 'message' => 'Error al alterar tabla: ' . $mysqli->error]);
                exit;
            }
        }
    }

    // Asegurar que el enum de 'estado' incluya 'cerrada'
    $enumSql = "SELECT COLUMN_TYPE, COLLATION_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hallazgos' AND COLUMN_NAME = 'estado'";
    $enumRes = $mysqli->query($enumSql);
    if ($enumRes && $row = $enumRes->fetch_assoc()) {
        $columnType = $row['COLUMN_TYPE'] ?? '';
        $collation = $row['COLLATION_NAME'] ?? 'utf8mb4_unicode_ci';
        if (stripos($columnType, "enum(") !== false && stripos($columnType, "'cerrada'") === false) {
            // Extraer valores actuales del enum
            if (preg_match("/enum\\((.*)\\)/i", $columnType, $m)) {
                $vals = $m[1]; // e.g. 'activo','inactivo','cuarentena','scrap'
                $valsArray = array_map(function($v){ return trim($v); }, explode(',', $vals));
                // Agregar 'cerrada' al final
                $valsArray[] = "'cerrada'";
                $newEnum = 'ENUM(' . implode(',', $valsArray) . ')';
                $alterEnumSql = "ALTER TABLE hallazgos MODIFY COLUMN estado $newEnum COLLATE $collation NOT NULL DEFAULT 'activo'";
                if (!$mysqli->query($alterEnumSql)) {
                    echo json_encode(['success' => false, 'message' => 'Error al actualizar enum estado: ' . $mysqli->error, 'alter' => $alterEnumSql]);
                    exit;
                }
            }
        }
    }

    // Normalizar formato de fecha (aceptar datetime-local "YYYY-MM-DDTHH:MM")
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $fecha_cierre)) {
        $fecha_cierre = str_replace('T', ' ', $fecha_cierre) . ':00';
    }

    // Actualizar el hallazgo a estado 'cerrada' y guardar datos
    $sql = "UPDATE hallazgos SET estado = 'cerrada', fecha_cierre = ?, solucion = ?, fecha_actualizacion = NOW() WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssi', $fecha_cierre, $solucion, $hallazgo_id);
    $ok = $stmt->execute();

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => 'Error al cerrar hallazgo: ' . $stmt->error]);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Hallazgo cerrado correctamente']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();
?>
