<?php
require_once '../includes/db.php';
$id = intval($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';
if ($id > 0 && in_array($accion, ['Aprobado','Rechazado'])) {
    $stmt = $mysqli->prepare("UPDATE hallazgos SET resultado=? WHERE id=?");
    $stmt->bind_param('si', $accion, $id);
    $ok = $stmt->execute();
    echo json_encode(['ok' => $ok]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
}