<?php
/**
 * Backend para registrar hallazgos múltiples con evidencias
 * Basado en la estructura real de la base de datos vista en HeidiSQL
 * Optimizado para Android con archivos grandes (10 fotos de 5MB cada una)
 */

// Log de inicio inmediato SIMPLE
file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - SCRIPT INICIADO - guardar_hallazgo_multiple.php\n", FILE_APPEND);
error_log("[" . date('Y-m-d H:i:s') . "] INICIO - guardar_hallazgo_multiple.php");

// Headers importantes primero
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Cargar configuración específica para Android
require_once 'config_android.php';

file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - CONFIG CARGADO\n", FILE_APPEND);
error_log("[" . date('Y-m-d H:i:s') . "] Config Android cargado");

// Iniciar sesión para obtener datos del usuario
session_start();

error_log("[" . date('Y-m-d H:i:s') . "] Sesión iniciada");

// Función para detectar dispositivo Android
function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // Detectar Android, iPad, iPhone, Samsung, tablet, etc.
    return preg_match('/Android|iPad|iPhone|Samsung|Tablet|SM-|GT-|Tab/i', $userAgent);
}

function isAndroidDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return preg_match('/Android/i', $userAgent);
}

// Log de debugging optimizado para Android
function logAndroidDebug($message, $data = null) {
    $isAndroid = isAndroidDevice();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] " . 
                  ($isAndroid ? "[🤖 ANDROID] " : "[💻 DESKTOP] ") . 
                  $message;
    
    if ($data !== null) {
        $logMessage .= " | Data: " . print_r($data, true);
    }
    
    error_log($logMessage);
}

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - MÉTODO NO ES POST: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Solo se permite método POST']);
    exit;
}

file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - MÉTODO POST VERIFICADO\n", FILE_APPEND);

// MODO DE PRUEBA para Android - NO guardar en BD
if (isset($_POST['test_mode']) && $_POST['test_mode'] === 'true') {
    logAndroidDebug("MODO DE PRUEBA ACTIVADO - No se guardará en BD");
    
    // Simular procesamiento
    sleep(1); // Simular tiempo de procesamiento
    
    echo json_encode([
        'success' => true,
        'message' => 'Prueba exitosa - Formulario procesado correctamente (no guardado)',
        'test_mode' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'android_detected' => isAndroidDevice(),
        'data_received' => [
            'post_fields' => count($_POST),
            'files_received' => count($_FILES),
            'total_size' => $_SERVER['CONTENT_LENGTH'] ?? 0
        ]
    ]);
    exit;
}

// MODO REAL - Log más detallado
logAndroidDebug("🔥 MODO REAL ACTIVADO - Iniciando guardado en BD", [
    'test_mode' => $_POST['test_mode'] ?? 'no definido',
    'all_post_keys' => array_keys($_POST),
    'files_keys' => array_keys($_FILES)
]);

// Incluir configuración de base de datos
require_once 'includes/db_config.php';

error_log("[" . date('Y-m-d H:i:s') . "] db_config.php incluido");
file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - DB CONFIG INCLUIDO\n", FILE_APPEND);

// Debug: Verificar que db_config.php se cargó correctamente
logAndroidDebug("DB Config cargado", [
    'mysqli_exists' => isset($mysqli) ? 'Si' : 'No',
    'mysqli_class' => isset($mysqli) ? get_class($mysqli) : 'N/A',
    'connection_status' => isset($mysqli) ? ($mysqli->connect_error ? 'Error: ' . $mysqli->connect_error : 'OK') : 'No disponible',
    'server_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
    'docker_detection' => 'Ver archivo db_config.php'
]);

file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - DEBUG DE CONEXIÓN COMPLETADO\n", FILE_APPEND);

error_log("[" . date('Y-m-d H:i:s') . "] Debug de conexión realizado");

// Log inicial para debugging
logAndroidDebug("Inicio de registro de hallazgo", [
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'No especificado',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'No especificado',
    'post_count' => count($_POST),
    'files_count' => count($_FILES),
    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 100),
    'is_android' => isAndroidDevice() ? 'Si' : 'No'
]);

file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - LOG INICIAL COMPLETADO\n", FILE_APPEND);

try {
    logAndroidDebug("🔥 ENTRANDO AL TRY PRINCIPAL");
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - ENTRANDO AL TRY PRINCIPAL\n", FILE_APPEND);
    
    // Verificar conexión a la base de datos
    if (!isset($mysqli)) {
        logAndroidDebug("ERROR CRÍTICO: \$mysqli no está definido");
        file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - ERROR: mysqli no definido\n", FILE_APPEND);
        throw new Exception("Error de configuración: conexión a base de datos no disponible");
    }
    
    logAndroidDebug("🔥 \$mysqli existe, verificando conexión...");
    
    if ($mysqli->connect_error) {
        logAndroidDebug("ERROR CRÍTICO: Error de conexión MySQL", [
            'error' => $mysqli->connect_error,
            'errno' => $mysqli->connect_errno
        ]);
        file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - ERROR CONEXIÓN: " . $mysqli->connect_error . "\n", FILE_APPEND);
        throw new Exception("Error de conexión a la base de datos: " . $mysqli->connect_error);
    }
    
    logAndroidDebug("✅ Conexión a base de datos verificada exitosamente");
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - CONEXIÓN BD VERIFICADA OK\n", FILE_APPEND);
    
    logAndroidDebug("🔥 Iniciando obtención de datos del formulario...");
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - OBTENIENDO DATOS DEL FORMULARIO\n", FILE_APPEND);
    
    // Obtener y validar datos del formulario
    // Priorizar: 1) POST del formulario, 2) Sesión del usuario, 3) Usuario por defecto
    
    // Debug: Mostrar datos de sesión y POST
    logAndroidDebug("DEBUG - Datos de sesión y POST", [
        'session_exists' => isset($_SESSION['usuario']),
        'session_user_id' => $_SESSION['usuario']['id'] ?? 'No existe',
        'session_user_name' => $_SESSION['usuario']['nombre'] ?? 'No existe',
        'post_id_usuario' => $_POST['id_usuario'] ?? 'No existe',
        'post_keys' => array_keys($_POST)
    ]);
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - DEBUG SESIÓN Y POST COMPLETADO\n", FILE_APPEND);
    
    $id_usuario = $_POST['id_usuario'] ?? $_SESSION['usuario']['id'] ?? 1;
    
    logAndroidDebug("🔥 ID de usuario obtenido", ['id_usuario' => $id_usuario]);
    
    // Debug: Mostrar ID final seleccionado
    logAndroidDebug("DEBUG - ID Usuario final", [
        'id_usuario_final' => $id_usuario,
        'source' => isset($_POST['id_usuario']) ? 'POST' : (isset($_SESSION['usuario']['id']) ? 'SESSION' : 'DEFAULT')
    ]);
    
    logAndroidDebug("🔥 Iniciando verificación de usuario en BD...");
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - INICIANDO VERIFICACIÓN USUARIO\n", FILE_APPEND);
    
    // Verificar que el usuario existe en la base de datos
    $checkUserSql = "SELECT id, nombre FROM usuarios WHERE id = ?";
    $checkStmt = $mysqli->prepare($checkUserSql);
    
    if (!$checkStmt) {
        logAndroidDebug("ERROR: No se pudo preparar la consulta de usuario", ['error' => $mysqli->error]);
        file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - ERROR PREPARAR CONSULTA USUARIO\n", FILE_APPEND);
        throw new Exception("Error preparando consulta de usuario: " . $mysqli->error);
    }
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - CONSULTA USUARIO PREPARADA\n", FILE_APPEND);
    
    $checkStmt->bind_param('i', $id_usuario);
    
    if (!$checkStmt->execute()) {
        logAndroidDebug("ERROR: No se pudo ejecutar la consulta de usuario", ['error' => $checkStmt->error]);
        file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - ERROR EJECUTAR CONSULTA USUARIO\n", FILE_APPEND);
        throw new Exception("Error ejecutando consulta de usuario: " . $checkStmt->error);
    }
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - CONSULTA USUARIO EJECUTADA\n", FILE_APPEND);
    
    $userResult = $checkStmt->get_result();
    $userExists = $userResult->fetch_assoc();
    $checkStmt->close();
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - RESULTADO USUARIO OBTENIDO\n", FILE_APPEND);
    
    logAndroidDebug("🔥 Consulta de usuario completada", ['user_found' => $userExists ? 'Si' : 'No']);
    
    if (!$userExists) {
        logAndroidDebug("ERROR - Usuario no existe", ['id_usuario' => $id_usuario]);
        file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - ERROR: USUARIO NO EXISTE\n", FILE_APPEND);
        throw new Exception("Error: Usuario no válido. Por favor inicia sesión nuevamente.");
    }
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - USUARIO VERIFICADO OK\n", FILE_APPEND);
    
    logAndroidDebug("DEBUG - Usuario verificado", [
        'id' => $userExists['id'],
        'nombre' => $userExists['nombre']
    ]);
    
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $job_order = $_POST['job_order'] ?? '';
    $no_ensamble = $_POST['no_ensamble'] ?? '';
    $estacion = $_POST['estacion'] ?? '';
    $area_ubicacion = $_POST['area'] ?? $_POST['area_ubicacion'] ?? '';
    $retrabajo = $_POST['retrabajo'] ?? 'No';
    $modelo = $_POST['modelo'] ?? '';
    $no_parte = $_POST['no_parte'] ?? '';
    $cantidad_piezas = isset($_POST['cantidad_piezas']) ? intval($_POST['cantidad_piezas']) : 1;
    $observaciones = $_POST['observaciones'] ?? '';
    $defectos = $_POST['defectos'] ?? [];
    
    // Debug específico para fecha
    logAndroidDebug("DEBUG - Variables de fecha", [
        'fecha_raw' => $fecha,
        'fecha_post' => $_POST['fecha'] ?? 'No existe',
        'fecha_default' => date('Y-m-d'),
        'fecha_length' => strlen($fecha)
    ]);
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - FECHA DEBUG: " . $fecha . "\n", FILE_APPEND);
    
    // Log de datos recibidos para debugging
    logAndroidDebug("Datos del formulario recibidos", [
        'no_ensamble' => $no_ensamble,
        'area' => $area_ubicacion,
        'cantidad_piezas' => $cantidad_piezas,
        'observaciones' => substr($observaciones, 0, 50) . '...',
        'defectos_count' => is_array($defectos) ? count($defectos) : 'No es array',
        'files_received' => !empty($_FILES['evidencias']['name'][0]) ? 'Si' : 'No'
    ]);

    // Validación para cantidad_piezas
    if ($cantidad_piezas < 1) {
        logAndroidDebug("Error de validación: Cantidad de piezas inválida", ['cantidad_piezas' => $cantidad_piezas]);
        throw new Exception('La cantidad de piezas debe ser al menos 1');
    }
    
    // Validaciones básicas
    if (empty($no_ensamble)) {
        logAndroidDebug("Error de validación: Número de ensamble vacío", ['no_ensamble' => $no_ensamble]);
        throw new Exception('El número de ensamble es requerido');
    }
    
    if (empty($area_ubicacion)) {
        logAndroidDebug("Error de validación: Área vacía", ['area' => $area_ubicacion]);
        throw new Exception('El área/ubicación es requerida');
    }
    
    if (empty($observaciones)) {
        logAndroidDebug("Error de validación: Observaciones vacías", ['observaciones' => $observaciones]);
        throw new Exception('Las observaciones son requeridas');
    }
    
    logAndroidDebug("Validaciones básicas pasadas exitosamente");
    
    logAndroidDebug("🔥 Preparando datos para inserción...");
    
    // Validar y corregir formato de fecha
    if (empty($fecha) || strlen($fecha) < 8) {
        $fecha = date('Y-m-d'); // Usar fecha actual si hay problema
        logAndroidDebug("CORRECCIÓN: Fecha inválida corregida", ['fecha_corregida' => $fecha]);
    }
    
    // Preparar fecha y hora completa con validación
    $fecha_creacion = $fecha . ' ' . date('H:i:s');
    $fecha_actualizacion = date('Y-m-d H:i:s');
    
    // Debug de fechas finales
    logAndroidDebug("DEBUG - Fechas finales", [
        'fecha_base' => $fecha,
        'fecha_creacion' => $fecha_creacion,
        'fecha_actualizacion' => $fecha_actualizacion
    ]);
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - FECHAS FINALES: creacion=" . $fecha_creacion . ", actualizacion=" . $fecha_actualizacion . "\n", FILE_APPEND);
    
    // Determinar estado basado en retrabajo
    $estado = ($retrabajo === 'Si') ? 'inactivo' : 'activo';
    
    logAndroidDebug("🔥 Datos preparados", [
        'fecha_creacion' => $fecha_creacion,
        'estado' => $estado
    ]);
    
    logAndroidDebug("🔥 Iniciando transacción...");
    
    // Iniciar transacción
    $mysqli->autocommit(false);
    
    logAndroidDebug("🔥 Preparando inserción de hallazgo principal...");
    
    // Insertar hallazgo principal
    $sql_hallazgo = "INSERT INTO hallazgos (
        id_usuario, 
        fecha, 
        job_order, 
        no_ensamble, 
        estacion, 
        area_ubicacion, 
        retrabajo, 
        modelo, 
        no_parte, 
        cantidad_piezas,
        observaciones, 
        fecha_creacion, 
        fecha_actualizacion, 
        estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    logAndroidDebug("🔥 SQL preparado para hallazgo");
    
    $stmt_hallazgo = $mysqli->prepare($sql_hallazgo);
    
    if (!$stmt_hallazgo) {
        logAndroidDebug("ERROR: No se pudo preparar la consulta de hallazgo", ['error' => $mysqli->error]);
        throw new Exception('Error preparando consulta de hallazgo: ' . $mysqli->error);
    }
    
    logAndroidDebug("🔥 Binding parameters...", [
        'id_usuario' => $id_usuario,
        'fecha' => $fecha,
        'no_ensamble' => $no_ensamble,
        'area_ubicacion' => $area_ubicacion
    ]);
    
    // Debug COMPLETO de todas las variables antes del bind
    logAndroidDebug("DEBUG COMPLETO - Variables para bind_param", [
        'id_usuario' => $id_usuario . ' (tipo: ' . gettype($id_usuario) . ')',
        'fecha' => $fecha . ' (tipo: ' . gettype($fecha) . ')',
        'job_order' => $job_order . ' (tipo: ' . gettype($job_order) . ')',
        'no_ensamble' => $no_ensamble . ' (tipo: ' . gettype($no_ensamble) . ')',
        'estacion' => $estacion . ' (tipo: ' . gettype($estacion) . ')',
        'area_ubicacion' => $area_ubicacion . ' (tipo: ' . gettype($area_ubicacion) . ')',
        'retrabajo' => $retrabajo . ' (tipo: ' . gettype($retrabajo) . ')',
        'modelo' => $modelo . ' (tipo: ' . gettype($modelo) . ')',
        'no_parte' => $no_parte . ' (tipo: ' . gettype($no_parte) . ')',
        'cantidad_piezas' => $cantidad_piezas . ' (tipo: ' . gettype($cantidad_piezas) . ')',
        'observaciones' => substr($observaciones, 0, 50) . '... (tipo: ' . gettype($observaciones) . ')',
        'fecha_creacion' => $fecha_creacion . ' (tipo: ' . gettype($fecha_creacion) . ')',
        'fecha_actualizacion' => $fecha_actualizacion . ' (tipo: ' . gettype($fecha_actualizacion) . ')',
        'estado' => $estado . ' (tipo: ' . gettype($estado) . ')'
    ]);
    
    file_put_contents('simple_execution.log', date('Y-m-d H:i:s') . " - ANTES BIND: fecha_actualizacion=" . $fecha_actualizacion . "\n", FILE_APPEND);
    
    $stmt_hallazgo->bind_param('issssssssissss', 
        $id_usuario,           // i - integer
        $fecha,                // s - string
        $job_order,            // s - string
        $no_ensamble,          // s - string
        $estacion,             // s - string
        $area_ubicacion,       // s - string
        $retrabajo,            // s - string
        $modelo,               // s - string
        $no_parte,             // s - string
        $cantidad_piezas,      // i - integer
        $observaciones,        // s - string
        $fecha_creacion,       // s - string
        $fecha_actualizacion,  // s - string
        $estado                // s - string
    );
    
    logAndroidDebug("🔥 Ejecutando inserción de hallazgo...");
    
    if (!$stmt_hallazgo->execute()) {
        logAndroidDebug("ERROR: Fallo al ejecutar inserción de hallazgo", ['error' => $stmt_hallazgo->error]);
        throw new Exception('Error al insertar hallazgo: ' . $stmt_hallazgo->error);
    }
    
    $hallazgo_id = $mysqli->insert_id;
    $stmt_hallazgo->close();
    
    logAndroidDebug("✅ Hallazgo insertado exitosamente", ['hallazgo_id' => $hallazgo_id]);
    
    // Procesar defectos múltiples
    $defectos_count = 0;
    if (!empty($defectos)) {
        // Si defectos viene como string, convertir a array
        if (!is_array($defectos)) {
            $defectos = explode(',', $defectos);
        }
        
        $sql_defecto = "INSERT INTO hallazgos_defectos (hallazgo_id, defecto, fecha_creacion) VALUES (?, ?, ?)";
        $stmt_defecto = $mysqli->prepare($sql_defecto);
        
        foreach ($defectos as $defecto) {
            $defecto = trim($defecto);
            if (!empty($defecto)) {
                $fecha_actual = date('Y-m-d H:i:s');
                $stmt_defecto->bind_param('iss', $hallazgo_id, $defecto, $fecha_actual);
                if ($stmt_defecto->execute()) {
                    $defectos_count++;
                }
            }
        }
        $stmt_defecto->close();
    }
    
    // Procesar evidencias (múltiples archivos)
    $evidencias_count = 0;
    $evidencias_procesadas = [];
    
    if (!empty($_FILES['evidencias']['name'][0])) {
        $upload_dir = 'uploads/';
        
        // Crear directorio si no existe
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $files = $_FILES['evidencias'];
        $total_files = count($files['name']);
        
        // Tipos de archivo permitidos
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $max_file_size = 10 * 1024 * 1024; // 10MB
        
        $sql_evidencia = "INSERT INTO hallazgos_evidencias (
            hallazgo_id, 
            archivo_nombre, 
            archivo_original, 
            fecha_subida
        ) VALUES (?, ?, ?, ?)";
        $stmt_evidencia = $mysqli->prepare($sql_evidencia);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $original_name = $files['name'][$i];
                $tmp_name = $files['tmp_name'][$i];
                $file_size = $files['size'][$i];
                $file_type = $files['type'][$i];
                
                // Validar tipo de archivo
                if (!in_array($file_type, $allowed_types)) {
                    continue; // Saltar archivos no permitidos
                }
                
                // Validar tamaño
                if ($file_size > $max_file_size) {
                    continue; // Saltar archivos muy grandes
                }
                
                // Generar nombre único
                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                $unique_name = 'evid_' . uniqid() . '_' . $hallazgo_id . '.' . $file_extension;
                $upload_path = $upload_dir . $unique_name;
                
                // Mover archivo
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    // Guardar en base de datos
                    $fecha_subida = date('Y-m-d H:i:s');
                    $stmt_evidencia->bind_param('isss', 
                        $hallazgo_id,
                        $unique_name,
                        $original_name,
                        $fecha_subida
                    );
                    
                    if ($stmt_evidencia->execute()) {
                        $evidencias_count++;
                        $evidencias_procesadas[] = [
                            'original' => $original_name,
                            'guardado' => $unique_name,
                            'tamaño' => $file_size
                        ];
                    }
                }
            }
        }
        $stmt_evidencia->close();
    }
    
    // Confirmar transacción
    $mysqli->commit();
    $mysqli->autocommit(true);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Hallazgo registrado exitosamente',
        'data' => [
            'hallazgo_id' => $hallazgo_id,
            'defectos_registrados' => $defectos_count,
            'evidencias_subidas' => $evidencias_count,
            'estado' => $estado,
            'fecha_registro' => $fecha_creacion,
            'evidencias' => $evidencias_procesadas,
            'resumen' => [
                'no_ensamble' => $no_ensamble,
                'area' => $area_ubicacion,
                'job_order' => $job_order,
                'modelo' => $modelo,
                'retrabajo' => $retrabajo
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (mysqli_sql_exception $e) {
    // Rollback en caso de error de base de datos
    if (isset($mysqli)) {
        $mysqli->rollback();
        $mysqli->autocommit(true);
    }
    
    logAndroidDebug("Error MySQL en guardar_hallazgo_multiple.php", [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    
    error_log("Error MySQL en guardar_hallazgo_multiple.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage(),
        'code' => 'DB_ERROR'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Rollback en caso de cualquier otro error
    if (isset($mysqli)) {
        $mysqli->rollback();
        $mysqli->autocommit(true);
    }
    
    // Log específico para móviles
    logAndroidDebug("Error general en registro", [
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile()),
        'post_data_summary' => [
            'post_keys' => array_keys($_POST),
            'post_count' => count($_POST),
            'files_keys' => array_keys($_FILES),
            'files_count' => count($_FILES)
        ]
    ]);
    
    error_log("Error general en guardar_hallazgo_multiple.php: " . $e->getMessage());
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'VALIDATION_ERROR',
        'debug' => [
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
            'is_mobile' => isMobileDevice(),
            'post_received' => !empty($_POST),
            'files_received' => !empty($_FILES)
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
