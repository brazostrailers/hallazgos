<?php
// Configuración para Docker con manejo de errores mejorado
$host = 'hallazgos_db';
$db = 'hallazgos';
$user = 'usuario';
$pass = 'secreto';
$port = 3306;

// Log para debugging
error_log("[" . date('Y-m-d H:i:s') . "] Intentando conexión Docker: host=$host, db=$db, user=$user");

try {
    $mysqli = new mysqli($host, $user, $pass, $db, $port);
    
    if ($mysqli->connect_error) {
        error_log("[" . date('Y-m-d H:i:s') . "] Error conexión Docker: " . $mysqli->connect_error);
        die("Error de conexión Docker: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    // También crear conexión PDO para scripts que la necesiten
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    error_log("[" . date('Y-m-d H:i:s') . "] Conexión Docker exitosa (MySQLi y PDO)");
    
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Excepción en conexión Docker: " . $e->getMessage());
    die("Excepción en conexión Docker: " . $e->getMessage());
}
?>
