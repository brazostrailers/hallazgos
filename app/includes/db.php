<?php
// Configuración para Laragon
$host = 'localhost';
$db   = 'hallazgos';
$user = 'root';
$pass = '';

// Intentar conexión con reintentos
$max_retries = 3;
$retry_count = 0;
$mysqli = null;

while ($retry_count < $max_retries && $mysqli === null) {
    try {
        $mysqli = new mysqli($host, $user, $pass, $db);
        
        // Verificar si la conexión fue exitosa
        if ($mysqli->connect_error) {
            throw new Exception("Error de conexión: " . $mysqli->connect_error);
        }
        
        // Configurar charset para evitar problemas con caracteres especiales
        $mysqli->set_charset("utf8");
        
        // Verificar que la base de datos existe
        $result = $mysqli->query("SELECT DATABASE() as db_name");
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row['db_name'] !== $db) {
                throw new Exception("Base de datos incorrecta: " . $row['db_name']);
            }
        }
        
        break; // Conexión exitosa
        
    } catch (Exception $e) {
        $retry_count++;
        if ($retry_count >= $max_retries) {
            die("Error de conexión después de $max_retries intentos: " . $e->getMessage() . 
                "<br><br>Verifica que:<br>" .
                "1. Docker esté ejecutándose<br>" .
                "2. MySQL container esté iniciado<br>" .
                "3. La base de datos 'hallazgos' exista<br>" .
                "4. Las credenciales sean correctas");
        }
        
        // Esperar un poco antes del siguiente intento
        sleep(1);
        $mysqli = null;
    }
}

if ($mysqli === null) {
    die("No se pudo establecer conexión con la base de datos");
}

// Crear alias para compatibilidad
$conn = $mysqli;
?>
