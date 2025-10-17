<?php
/**
 * Configuración específica para manejo de archivos grandes en Android
 * Sistema de Hallazgos - Optimizado para 10 fotos de 5MB cada una
 */

// Configurar límites de PHP para archivos grandes
ini_set('upload_max_filesize', '50M');        // 50MB por archivo
ini_set('post_max_size', '500M');             // 500MB total
ini_set('max_file_uploads', 50);              // Hasta 50 archivos
ini_set('max_execution_time', 600);           // 10 minutos de timeout
ini_set('max_input_time', 600);               // 10 minutos para recibir datos
ini_set('memory_limit', '1024M');             // 1GB de memoria para procesamiento

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de errores para desarrollo
if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '192.168') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Headers específicos para Android con archivos grandes
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Android-App, X-Android-Version, X-Test-Mode, X-Test-With-Files, Cache-Control, Accept');
header('Access-Control-Max-Age: 86400');

// Manejo de peticiones OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Funciones útiles para debugging de archivos grandes
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function logAndroidUpload($message, $data = null) {
    $logFile = __DIR__ . '/android_uploads.log';
    $timestamp = date('Y-m-d H:i:s');
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $isAndroid = strpos($userAgent, 'Android') !== false ? 'YES' : 'NO';
    
    $logMessage = "[$timestamp] [Android: $isAndroid] $message";
    if ($data) {
        $logMessage .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    $logMessage .= "\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Log de configuración al cargar
logAndroidUpload('Configuración cargada', [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
]);

?>
