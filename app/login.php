<?php
session_start();
require_once 'includes/db.php';

$correo = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';

$sql = "SELECT * FROM usuarios WHERE correo = ? AND contrasena = MD5(?)";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ss', $correo, $contrasena);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $_SESSION['usuario'] = $user;
    if ($user['rol'] === 'encargado') {
        header('Location: ../encargado/ver_hallazgos.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
} else {
    echo "<script>alert('Credenciales incorrectas');window.location='index.php';</script>";
}