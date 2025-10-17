<?php
// Test simple para verificar el endpoint
header('Content-Type: application/json');

// Test básico
echo json_encode([
    'success' => true, 
    'message' => 'Test endpoint funcionando',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
