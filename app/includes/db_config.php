<?php
/**
 * Configuración de Base de Datos - Simplificada
 */

// Detectar entorno - Configuración corregida para Docker
$is_docker = false;

// Método 1: Variable de entorno específica de Docker
if (isset($_SERVER['DOCKER_ENV']) && $_SERVER['DOCKER_ENV'] === 'true') {
    $is_docker = true;
}

// Método 2: Verificar si estamos dentro del contenedor Docker
if (!$is_docker && file_exists('/.dockerenv')) {
    $is_docker = true;
}

// Método 3: Verificar estructura de directorios Docker
if (!$is_docker && file_exists('/var/www/html') && is_dir('/var/www/html')) {
    $is_docker = true;
}

// Método 4: Si se accede desde red (no localhost directo), asumir Docker
if (!$is_docker && isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    if (strpos($host, '192.168') !== false || strpos($host, '172.') !== false || strpos($host, '10.') !== false) {
        $is_docker = true; // Acceso desde red = Docker
    }
}

// Método 5: Forzar Docker si se accede por puerto 8085 (nuestro puerto Docker)
if (!$is_docker && isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], ':8085') !== false) {
    $is_docker = true; // Forzar Docker cuando se accede por 8085
}

// Método 6: Si no hay detección anterior, pero estamos en una aplicación web, asumir Docker
if (!$is_docker && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SERVER_NAME'])) {
    $is_docker = true; // Por defecto usar Docker para aplicaciones web
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
