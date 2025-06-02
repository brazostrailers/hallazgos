<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$modelo = $_POST['modelo'] ?? '';
$no_serie = $_POST['no_serie'] ?? '';
$numero_pieza = $_POST['numero_pieza'] ?? '';
$precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
$id_hallazgo = intval($_POST['id_hallazgo'] ?? 0);

if ($modelo && $no_serie && $numero_pieza && $precio > 0 && $id_hallazgo > 0) {
    $stmt = $mysqli->prepare("INSERT INTO Scrap (modelo, no_serie, numero_pieza, precio) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['ok' => false, 'msg' => 'Error SQL: ' . $mysqli->error]);
        exit;
    }
    $stmt->bind_param("sssd", $modelo, $no_serie, $numero_pieza, $precio);
    $ok = $stmt->execute();

    if ($ok) {
        $stmt2 = $mysqli->prepare("DELETE FROM hallazgos WHERE id = ?");
        if ($stmt2) {
            $stmt2->bind_param("i", $id_hallazgo);
            $stmt2->execute();
        }
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'No se pudo registrar el scrap: ' . $stmt->error]);
    }
} else {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']);
}
?>