<?php
/**
 * Configuración de Base de Datos - Simplificada
 */

// Detectar entorno
$is_docker = false;

// Método 1: Variable de entorno
if (isset($_SERVER['DB_HOST'])) {
    $is_docker = true;
}

// Método 2: Verificar si existe el hostname 'db' (nombre del servicio Docker)
if (!$is_docker && gethostbyname('db') !== 'db') {
    $is_docker = true;
}

// Método 3: Verificar estructura de directorios Docker
if (!$is_docker && file_exists('/var/www/html') && is_dir('/var/www/html')) {
    $is_docker = true;
}

try {
    if ($is_docker) {
        // Configuración para Docker
        require_once __DIR__ . '/db_docker.php';
    } else {
        // Configuración para desarrollo local (Laragon)
        require_once __DIR__ . '/db.php';
    }
} catch (Exception $e) {
    die("Error de configuración de base de datos: " . $e->getMessage());
}

// Verificar conexión
if (!isset($mysqli) || $mysqli === null) {
    die("Error: La conexión a la base de datos no fue establecida");
}
?>
