<?php
// Script de verificación para debug del bind_param
header('Content-Type: text/plain');

echo "=== VERIFICACIÓN BIND_PARAM DEBUG ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Simular los parámetros
$params = [
    'id_usuario' => 10,
    'fecha' => '2025-08-14',
    'job_order' => '45',
    'no_ensamble' => '4-100-00188',
    'estacion' => 'Estación 2',
    'area_ubicacion' => 'Beam welder',
    'retrabajo' => 'Si',
    'modelo' => '9W LB LEVELING BOX',
    'no_parte' => '45',
    'cantidad_piezas' => 14,
    'observaciones' => 'not good',
    'fecha_creacion' => '2025-08-14 08:15:00',
    'fecha_actualizacion' => '2025-08-14 08:15:00',
    'estado' => 'inactivo'
];

echo "Parámetros a procesar:\n";
$count = 0;
$types = '';
foreach ($params as $key => $value) {
    $count++;
    $type = is_int($value) ? 'i' : 's';
    $types .= $type;
    echo "{$count}. {$key} = '{$value}' (tipo: {$type})\n";
}

echo "\nTotal parámetros: {$count}\n";
echo "String de tipos: '{$types}'\n";
echo "Longitud del string: " . strlen($types) . "\n";

if (strlen($types) === $count) {
    echo "\n✅ CORRECTO: El string de tipos coincide con el número de parámetros\n";
} else {
    echo "\n❌ ERROR: Desajuste entre tipos (" . strlen($types) . ") y parámetros ({$count})\n";
}
?>
