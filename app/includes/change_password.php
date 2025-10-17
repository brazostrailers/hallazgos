<?php
session_start();
require_once 'db_config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $contrasena_nueva = $_POST['contrasena_nueva'] ?? '';

    if (!$correo || !$contrasena_actual || !$contrasena_nueva) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    if (strlen($contrasena_nueva) < 6) {
        echo json_encode(['success' => false, 'message' => 'La nueva contraseña es demasiado corta']);
        exit;
    }

    // Verificar usuario + contraseña actual
    $sql = "SELECT id FROM usuarios WHERE correo = ? AND contrasena = MD5(?) LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) throw new Exception('Error preparando consulta');
    $stmt->bind_param('ss', $correo, $contrasena_actual);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Correo o contraseña actual incorrectos']);
        exit;
    }

    // Actualizar contraseña
    $upd = $mysqli->prepare("UPDATE usuarios SET contrasena = MD5(?) WHERE id = ?");
    if (!$upd) throw new Exception('Error preparando actualización');
    $upd->bind_param('si', $contrasena_nueva, $user['id']);
    $ok = $upd->execute();

    if (!$ok) throw new Exception('No se pudo actualizar la contraseña');

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();
