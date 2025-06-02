<?php

$sqlTotalDia = "SELECT COUNT(*) as total FROM hallazgos WHERE DATE(creado_en) = CURDATE()";
$sqlExitososDia = "SELECT COUNT(*) as exitosos FROM hallazgos WHERE DATE(creado_en) = CURDATE() AND existe_defecto = 'No'";
$res = $mysqli->query($sqlTotalDia); $total_dia = $res->fetch_assoc()['total'] ?? 0;
$res = $mysqli->query($sqlExitososDia); $exitosos_dia = $res->fetch_assoc()['exitosos'] ?? 0;
$fpy_dia = $total_dia > 0 ? round(($exitosos_dia / $total_dia) * 100, 2) : 0;

// FPY de la semana
$sqlTotalSemana = "SELECT COUNT(*) as total FROM hallazgos WHERE YEARWEEK(creado_en, 1) = YEARWEEK(CURDATE(), 1)";
$sqlExitososSemana = "SELECT COUNT(*) as exitosos FROM hallazgos WHERE YEARWEEK(creado_en, 1) = YEARWEEK(CURDATE(), 1) AND existe_defecto = 'No'";
$res = $mysqli->query($sqlTotalSemana); $total_semana = $res->fetch_assoc()['total'] ?? 0;
$res = $mysqli->query($sqlExitososSemana); $exitosos_semana = $res->fetch_assoc()['exitosos'] ?? 0;
$fpy_semana = $total_semana > 0 ? round(($exitosos_semana / $total_semana) * 100, 2) : 0;

// FPY del mes
$sqlTotalMes = "SELECT COUNT(*) as total FROM hallazgos WHERE YEAR(creado_en) = YEAR(CURDATE()) AND MONTH(creado_en) = MONTH(CURDATE())";
$sqlExitososMes = "SELECT COUNT(*) as exitosos FROM hallazgos WHERE YEAR(creado_en) = YEAR(CURDATE()) AND MONTH(creado_en) = MONTH(CURDATE()) AND existe_defecto = 'No'";
$res = $mysqli->query($sqlTotalMes); $total_mes = $res->fetch_assoc()['total'] ?? 0;
$res = $mysqli->query($sqlExitososMes); $exitosos_mes = $res->fetch_assoc()['exitosos'] ?? 0;
$fpy_mes = $total_mes > 0 ? round(($exitosos_mes / $total_mes) * 100, 2) : 0;
// 1. Totales para las cards
$resumen = [
    'total' => 0,
    'rechazados' => 0, // registros con hallazgo
    'retrabajos' => 0,
    'cuarentena' => 0,
    'fpy' => 0
];

// Total de registros
$sqlTotal = "SELECT COUNT(*) as total FROM hallazgos";
$res = $mysqli->query($sqlTotal);
if ($row = $res->fetch_assoc()) $resumen['total'] = (int)$row['total'];

// Total de registros con hallazgo (rechazados)
$sqlRechazados = "SELECT COUNT(*) as rechazados FROM hallazgos WHERE existe_defecto = 'Sí'";
$res = $mysqli->query($sqlRechazados);
if ($row = $res->fetch_assoc()) $resumen['rechazados'] = (int)$row['rechazados'];

// Total con retrabajo
$sqlRetrabajos = "SELECT COUNT(*) as retrabajos FROM hallazgos WHERE existe_defecto = 'Sí' AND retrabajo = 'Sí'";
$res = $mysqli->query($sqlRetrabajos);
if ($row = $res->fetch_assoc()) $resumen['retrabajos'] = (int)$row['retrabajos'];

// Total en cuarentena
$sqlCuarentena = "SELECT COUNT(*) as cuarentena FROM hallazgos WHERE existe_defecto = 'Sí' AND cuarentena = 'Sí'";
$res = $mysqli->query($sqlCuarentena);
if ($row = $res->fetch_assoc()) $resumen['cuarentena'] = (int)$row['cuarentena'];

// FPY: porcentaje de registros sin hallazgo
$sqlSinHallazgo = "SELECT COUNT(*) as exitosos FROM hallazgos WHERE existe_defecto = 'No'";
$res = $mysqli->query($sqlSinHallazgo);
$exitosos = 0;
if ($row = $res->fetch_assoc()) $exitosos = (int)$row['exitosos'];
$resumen['fpy'] = $resumen['total'] > 0 ? round(($exitosos / $resumen['total']) * 100, 2) : 0;

// 2. Gráficas solo para registros con hallazgo
function getConteo($mysqli, $campo, $soloConHallazgo = false) {
    $where = $soloConHallazgo ? "WHERE existe_defecto = 'Sí'" : "";
    $sql = "SELECT $campo as nombre, COUNT(*) as cantidad FROM hallazgos $where GROUP BY $campo ORDER BY cantidad DESC";
    $res = $mysqli->query($sql);
    $labels = [];
    $values = [];
    while ($row = $res->fetch_assoc()) {
        $labels[] = $row['nombre'] ?: 'Sin dato';
        $values[] = (int)$row['cantidad'];
    }
    return ['labels' => $labels, 'values' => $values];
}

// Gráficas para registros con hallazgo
$por_area_hallazgo = getConteo($mysqli, 'area_ubicacion', true);
$por_modelo_hallazgo = getConteo($mysqli, 'modelo', true);
$por_estacion_hallazgo = getConteo($mysqli, 'estacion', true);
$por_tipo_defecto = getConteo($mysqli, 'tipo_defecto', true);

// Gráficas para registros exitosos (sin hallazgo)
$por_area_exitosos = getConteo($mysqli, 'area_ubicacion', false);

// Estaciones 100% exitosas (todas sus piezas sin hallazgos)
$estaciones_exitosas = ['labels' => [], 'values' => []];
$sql = "
    SELECT estacion, SUM(CASE WHEN existe_defecto = 'No' THEN 1 ELSE 0 END) as exitosos
    FROM hallazgos
    GROUP BY estacion
    HAVING exitosos > 0
    ORDER BY exitosos DESC
";
$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    $estacion = $row['estacion'] ?: 'Sin dato';
    $estaciones_exitosas['labels'][] = $estacion;
    $estaciones_exitosas['values'][] = (int)$row['exitosos'];
}

$modelos_exitosos = ['labels' => [], 'values' => []];
$sql = "
    SELECT modelo, COUNT(*) as exitosos
    FROM hallazgos
    WHERE existe_defecto = 'No'
    GROUP BY modelo
    HAVING exitosos > 0
    ORDER BY exitosos DESC
";
$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    $modelo = $row['modelo'] ?: 'Sin dato';
    $modelos_exitosos['labels'][] = $modelo;
    $modelos_exitosos['values'][] = (int)$row['exitosos'];
}
$registros_correctos = [];
$sqlCorrectos = "
    SELECT modelo, no_serie, area_ubicacion, estacion, creado_en
    FROM hallazgos
    WHERE existe_defecto = 'No'
    ORDER BY creado_en DESC
";
$res = $mysqli->query($sqlCorrectos);
while ($row = $res->fetch_assoc()) $registros_correctos[] = $row;

$registros_correctos = [];
$sqlCorrectos = "
    SELECT modelo, no_serie, area_ubicacion, estacion, creado_en
    FROM hallazgos
    WHERE existe_defecto = 'No'
    ORDER BY creado_en DESC
";
$res = $mysqli->query($sqlCorrectos);
while ($row = $res->fetch_assoc()) $registros_correctos[] = $row;

// 5. Últimos registros (puedes ajustar el WHERE según lo que quieras mostrar)
$ultimos = [];
$sqlUltimos = "
    SELECT h.*, u.nombre as reportado_por
    FROM hallazgos h
    LEFT JOIN usuarios u ON h.id_usuario = u.id
    ORDER BY h.creado_en DESC
    LIMIT 10
";
$res = $mysqli->query($sqlUltimos);
while ($row = $res->fetch_assoc()) $ultimos[] = $row;

// 6. Todos los hallazgos (para filtros dinámicos en JS)
$todos_hallazgos = [];
$sqlTodos = "
    SELECT h.*, u.nombre as reportado_por
    FROM hallazgos h
    LEFT JOIN usuarios u ON h.id_usuario = u.id
    ORDER BY h.creado_en DESC
";
$res = $mysqli->query($sqlTodos);
while ($row = $res->fetch_assoc()) $todos_hallazgos[] = $row;

// 7. Registros en cuarentena
$en_cuarentena = [];
$sqlCuarentena = "
    SELECT h.*, u.nombre as reportado_por, TIMESTAMPDIFF(HOUR, h.creado_en, NOW()) as horas_en_cuarentena
    FROM hallazgos h
    LEFT JOIN usuarios u ON h.id_usuario = u.id
    WHERE h.existe_defecto = 'Sí' AND h.cuarentena = 'Sí'
    ORDER BY h.creado_en DESC
";
$res = $mysqli->query($sqlCuarentena);
while ($row = $res->fetch_assoc()) $en_cuarentena[] = $row;


$res = $mysqli->query("SELECT COUNT(*) AS piezas_scrap, COALESCE(SUM(precio),0) AS dinero_scrap FROM Scrap");
$piezas_scrap = 0;
$dinero_scrap = 0;
if ($row = $res->fetch_assoc()) {
    $piezas_scrap = (int)$row['piezas_scrap'];
    $dinero_scrap = (float)$row['dinero_scrap'];

$hallazgos = [];
$sqlHallazgos = "
    SELECT h.*, u.nombre as reportado_por
    FROM hallazgos h
    LEFT JOIN usuarios u ON h.id_usuario = u.id
    WHERE h.existe_defecto = 'Sí'
    ORDER BY h.creado_en DESC
";
$res = $mysqli->query($sqlHallazgos);
while ($row = $res->fetch_assoc()) $hallazgos[] = $row;
}
