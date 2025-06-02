<?php
$host = 'db';
$db   = 'hallazgos';
$user = 'usuario';
$pass = 'secreto';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}
?>