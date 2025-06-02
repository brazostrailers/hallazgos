
<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: index.php");
  exit;
}

function es_encargado() {
  return $_SESSION['usuario']['rol'] === 'encargado';
}

function es_calidad() {
  return $_SESSION['usuario']['rol'] === 'calidad';
}
?>
