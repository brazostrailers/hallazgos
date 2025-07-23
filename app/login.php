<?php
session_start();
require_once 'includes/db_config.php';

$correo = trim($_POST['correo'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

// Validar que se enviaron los datos
if (empty($correo) || empty($contrasena)) {
    echo "<script>alert('Por favor, complete todos los campos');window.location='index.php';</script>";
    exit;
}

try {
    $sql = "SELECT * FROM usuarios WHERE correo = ? AND contrasena = MD5(?)";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error en la consulta: " . $mysqli->error);
    }
    
    $stmt->bind_param('ss', $correo, $contrasena);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        $_SESSION['usuario'] = $user;
        
        if ($user['rol'] === 'encargado') {
            header('Location: admin_dashboard.php');
            exit;
        } else {
            header('Location: dashboard.php');
            exit;
        }
    } else {
        echo "<script>alert('Credenciales incorrectas');window.location='index.php';</script>";
        exit;
    }
} catch (Exception $e) {
    echo "<script>alert('Error del sistema: " . $e->getMessage() . "');window.location='index.php';</script>";
    exit;
}