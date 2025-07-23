<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    // Si es una petición AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Sesión expirada', 'redirect' => 'index.php']);
        exit;
    }
    header('Location: index.php');
    exit;
}

// Verificar si el usuario tiene rol de encargado
if ($_SESSION['usuario']['rol'] !== 'encargado') {
    // Si es una petición AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Acceso denegado', 'redirect' => 'acceso_denegado.php']);
        exit;
    }
    header('Location: acceso_denegado.php');
    exit;
}

// Usuario autenticado y con permisos correctos
$usuario = $_SESSION['usuario'];
?>
