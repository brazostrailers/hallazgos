<?php
require_once '../includes/db.php';
header('Content-Type: application/json');
$id = intval($_POST['id'] ?? 0);
$cuarentena = ($_POST['cuarentena'] ?? 'No') === 'Sí' ? 'Sí' : 'No';
if ($id > 0) {
    $stmt = $mysqli->prepare("UPDATE hallazgos SET cuarentena = ? WHERE id = ?");
    $stmt->bind_param("si", $cuarentena, $id);
    $ok = $stmt->execute();
    echo json_encode(['ok' => $ok]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
}
?>