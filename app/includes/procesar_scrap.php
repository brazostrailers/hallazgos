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
    $fecha_scrap = $input['fecha_scrap'] ?? '';

    // Helper: normaliza distintos formatos de fecha a 'Y-m-d H:i:s'
    $normalizeFecha = function($value) {
        $val = trim((string)$value);
        if ($val === '') return null;

        // Caso 1: YYYY-MM-DD o YYYY-MM-DDTHH:MM (de inputs type=date/datetime-local)
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2})(?::(\d{2}))?)?$/', $val, $m)) {
            $y = (int)$m[1]; $mo = (int)$m[2]; $d = (int)$m[3];
            $hh = isset($m[4]) ? (int)$m[4] : 0; $mm = isset($m[5]) ? (int)$m[5] : 0; $ss = isset($m[6]) ? (int)$m[6] : 0;
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $y, $mo, $d, $hh, $mm, $ss);
        }

        // Caso 2: DD/MM/YYYY (común en UI con locale es-ES)
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $val, $m)) {
            $d = (int)$m[1]; $mo = (int)$m[2]; $y = (int)$m[3];
            return sprintf('%04d-%02d-%02d 00:00:00', $y, $mo, $d);
        }

        // Caso 3: Solo año (evitar error de '2025'): asumir 1ro de enero
        if (preg_match('/^(\d{4})$/', $val, $m)) {
            $y = (int)$m[1];
            return sprintf('%04d-01-01 00:00:00', $y);
        }

        // Fallback: usar strtotime si puede parsear
        $ts = strtotime($val);
        if ($ts !== false) {
            return date('Y-m-d H:i:s', $ts);
        }
        return null;
    };
    
    if (!$hallazgo_id) {
        echo json_encode(['success' => false, 'message' => 'ID de hallazgo requerido']);
        exit;
    }
    
    // Verificar que el hallazgo existe (sin restricción de estado)
    $check_sql = "SELECT id, estado FROM hallazgos WHERE id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param('i', $hallazgo_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Hallazgo no encontrado']);
        exit;
    }
    
    // Iniciar transacción
    $mysqli->begin_transaction();
    
    try {
        // Normalizar fecha_scrap; si no viene o es inválida, usamos el momento actual
        $fecha_dt = $normalizeFecha($fecha_scrap);
        if ($fecha_dt) {
            $scrap_sql = "INSERT INTO scrap_records (hallazgo_id, modelo, no_parte, no_ensamble, precio, fecha_scrap, usuario_scrap, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $scrap_stmt = $mysqli->prepare($scrap_sql);
            // tipos: i (hallazgo_id), s (modelo), s (no_parte), s (no_ensamble), d (precio), s (fecha_scrap), i (usuario_scrap), s (observaciones)
            $scrap_stmt->bind_param('isssdsis', $hallazgo_id, $modelo, $no_parte, $no_ensamble, $precio, $fecha_dt, $usuario_id, $observaciones);
        } else {
            // Si no hay fecha válida, forzamos la fecha actual para evitar errores de DEFAULT
            $fecha_dt = date('Y-m-d H:i:s');
            $scrap_sql = "INSERT INTO scrap_records (hallazgo_id, modelo, no_parte, no_ensamble, precio, fecha_scrap, usuario_scrap, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $scrap_stmt = $mysqli->prepare($scrap_sql);
            $scrap_stmt->bind_param('isssdsis', $hallazgo_id, $modelo, $no_parte, $no_ensamble, $precio, $fecha_dt, $usuario_id, $observaciones);
        }
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
