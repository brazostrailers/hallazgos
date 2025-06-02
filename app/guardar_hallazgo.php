<?php
session_start();
require_once 'includes/db.php';

// Verifica que el usuario esté logueado
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: index.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$fecha = $_POST['fecha'] ?? null;
$modelo = $_POST['modelo'] ?? null;
$no_parte = $_POST['no_parte'] ?? null;
$no_serie = $_POST['no_serie'] ?? null;
$estacion = $_POST['estacion'] ?? null;
$area_ubicacion = $_POST['area_ubicacion'] ?? null;
$existe_defecto = $_POST['existe_defecto'] ?? 'No';
$retrabajo = $_POST['retrabajo'] ?? null;
$cuarentena = $_POST['cuarentena'] ?? null;
$tipo_defecto = $_POST['tipo_defecto'] ?? null;
$observaciones = $_POST['observaciones'] ?? null;
$resultado = null; // O como lo manejes en tu flujo

// Manejo de la evidencia fotográfica
$evidencia = null;
if (isset($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
    $dir = "uploads/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $ext = pathinfo($_FILES['evidencia']['name'], PATHINFO_EXTENSION);
    $nombreArchivo = uniqid('evidencia_') . '.' . $ext;
    $ruta = $dir . $nombreArchivo;
    if (move_uploaded_file($_FILES['evidencia']['tmp_name'], $ruta)) {
        $evidencia = $nombreArchivo;
    }
}

if ($existe_defecto !== 'Sí') {
    $retrabajo = null;
    $cuarentena = null;
    $tipo_defecto = null;
    $observaciones = null;
    $evidencia = null;
}

// Inserta el hallazgo
$stmt = $mysqli->prepare(
    "INSERT INTO hallazgos 
    (id_usuario, fecha, modelo, no_parte, no_serie, estacion, area_ubicacion, existe_defecto, retrabajo, cuarentena, tipo_defecto, observaciones, evidencia, resultado)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "isssssssssssss",
    $id_usuario,
    $fecha,
    $modelo,
    $no_parte,
    $no_serie,
    $estacion,
    $area_ubicacion,
    $existe_defecto,
    $retrabajo,
    $cuarentena,
    $tipo_defecto,
    $observaciones,
    $evidencia,
    $resultado
);

if ($stmt->execute()) {
    echo "<script>alert('Hallazgo registrado correctamente');window.location='dashboard.php';</script>";
} else {
    echo "<script>alert('Error al registrar el hallazgo');window.location='dashboard.php';</script>";
}
?>