<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/db_config.php';

// Opcional: restringir a usuarios autenticados (encargado)
if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'encargado') {
    // Permitimos GET anónimo si se desea mostrar, pero bloqueamos cambios
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }
}

// Crear tabla si no existe
$createSql = "CREATE TABLE IF NOT EXISTS scrap_goals (
    id INT NOT NULL AUTO_INCREMENT,
    year INT NOT NULL,
    month TINYINT NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_year_month (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$mysqli->query($createSql)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error creando tabla de metas: ' . $mysqli->error]);
    exit;
}

// Utilidades
function get_int($key, $default) {
    if (isset($_GET[$key])) return (int)$_GET[$key];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST[$key])) return (int)$_POST[$key];
        $raw = file_get_contents('php://input');
        if ($raw) {
            $json = json_decode($raw, true);
            if (is_array($json) && isset($json[$key])) return (int)$json[$key];
        }
    }
    return (int)$default;
}

function get_decimal($key, $default) {
    if (isset($_POST[$key])) return (float)$_POST[$key];
    if (isset($_GET[$key])) return (float)$_GET[$key];
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json) && isset($json[$key])) return (float)$json[$key];
    }
    return (float)$default;
}

$now = new DateTime('now');
$year = get_int('year', (int)$now->format('Y'));
$month = get_int('month', (int)$now->format('n'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = get_decimal('amount', 0);
    if ($amount < 0) $amount = 0;

    $stmt = $mysqli->prepare("INSERT INTO scrap_goals (year, month, amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error preparando guardado: ' . $mysqli->error]);
        exit;
    }
    $stmt->bind_param('iid', $year, $month, $amount);
    $ok = $stmt->execute();
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error guardando meta: ' . $stmt->error]);
        exit;
    }
    $stmt->close();
}

// Obtener meta
$goalAmount = 0.0;
$stmt = $mysqli->prepare("SELECT amount FROM scrap_goals WHERE year = ? AND month = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('ii', $year, $month);
    $stmt->execute();
    $stmt->bind_result($amountRes);
    if ($stmt->fetch()) {
        $goalAmount = (float)$amountRes;
    }
    $stmt->close();
}

// Total de scrap del mes (usar fecha de scrap; si falta, caer a fecha del hallazgo)
$stmt = $mysqli->prepare("SELECT COALESCE(SUM(sr.precio), 0) AS total
                                                    FROM scrap_records sr
                                                    LEFT JOIN hallazgos h ON sr.hallazgo_id = h.id
                                                    WHERE YEAR(COALESCE(sr.fecha_scrap, h.fecha)) = ?
                                                        AND MONTH(COALESCE(sr.fecha_scrap, h.fecha)) = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error preparando consulta total: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('ii', $year, $month);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : ['total' => 0];
$monthTotal = (float)($row['total'] ?? 0);
$stmt->close();

$percent = $goalAmount > 0 ? min(100, round(($monthTotal / $goalAmount) * 100, 2)) : 0;
$exceeded = $goalAmount > 0 ? ($monthTotal > $goalAmount) : false;
$remaining = max(0, $goalAmount - $monthTotal);

echo json_encode([
    'success' => true,
    'data' => [
        'year' => $year,
        'month' => $month,
        'goal' => round($goalAmount, 2),
        'month_total' => round($monthTotal, 2),
        'remaining' => round($remaining, 2),
        'percent' => $percent,
        'exceeded' => $exceeded
    ]
]);

// No cerramos $mysqli aquí para permitir inclusiones múltiples si aplica

?>
