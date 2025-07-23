<?php
// Configuraci??n para Docker con credenciales correctas
$host = 'hallazgos_db';
$db = 'hallazgos';
$user = 'usuario';
$pass = 'secreto';
$port = 3306;

$mysqli = new mysqli($host, $user, $pass, $db, $port);

if ($mysqli->connect_error) {
    die("Error de conexi??n Docker: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");
?>
