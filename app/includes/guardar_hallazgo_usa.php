<?php
session_start();
header('Content-Type: application/json');

// Verificar que se recibió una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Incluir configuración de base de datos
require_once 'db_config.php';

try {
    // Validar campos obligatorios
    $required_fields = ['fecha', 'job_order', 'warehouse', 'defecto', 'observaciones'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("El campo $field es obligatorio");
        }
    }

    // Validar que se subieron imágenes
    if (!isset($_FILES['evidencia_fotografica']) || empty($_FILES['evidencia_fotografica'])) {
        throw new Exception('Debe subir al menos una evidencia fotográfica');
    }

    // Procesar archivos múltiples
    $archivos = [];
    if (is_array($_FILES['evidencia_fotografica']['name'])) {
        // Múltiples archivos
        for ($i = 0; $i < count($_FILES['evidencia_fotografica']['name']); $i++) {
            if ($_FILES['evidencia_fotografica']['error'][$i] === UPLOAD_ERR_OK) {
                $archivos[] = [
                    'name' => $_FILES['evidencia_fotografica']['name'][$i],
                    'tmp_name' => $_FILES['evidencia_fotografica']['tmp_name'][$i],
                    'size' => $_FILES['evidencia_fotografica']['size'][$i],
                    'type' => $_FILES['evidencia_fotografica']['type'][$i]
                ];
            }
        }
    } else {
        // Un solo archivo
        if ($_FILES['evidencia_fotografica']['error'] === UPLOAD_ERR_OK) {
            $archivos[] = [
                'name' => $_FILES['evidencia_fotografica']['name'],
                'tmp_name' => $_FILES['evidencia_fotografica']['tmp_name'],
                'size' => $_FILES['evidencia_fotografica']['size'],
                'type' => $_FILES['evidencia_fotografica']['type']
            ];
        }
    }

    if (empty($archivos)) {
        throw new Exception('No se pudo procesar ningún archivo de evidencia');
    }

    // Validar formato de fecha
    $fecha = $_POST['fecha'];
    if (!DateTime::createFromFormat('Y-m-d', $fecha)) {
        throw new Exception('Formato de fecha inválido');
    }

    // Sanitizar datos de entrada
    $job_order = trim($_POST['job_order']);
    $warehouse = trim($_POST['warehouse']);
    $noparte = isset($_POST['noparte']) ? trim($_POST['noparte']) : null;
    $defecto = trim($_POST['defecto']);
    $observaciones = trim($_POST['observaciones']);
    
    // Obtener ID del usuario desde la sesión
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['usuario']['id'])) {
        throw new Exception('Usuario no autenticado. Por favor inicie sesión.');
    }
    
    $id_usuario = $_SESSION['usuario']['id'];

    // Validar tipos de defecto permitidos
    $defectos_permitidos = [
        'Incorrect Account',
        'Incorrect Measurement', 
        'Incorrect Stock Code',
        'Welding',
        'Shipping Damage',
        'Unsecured Cargo'
    ];
    
    // Recopilar todos los defectos (principal + adicionales)
    $defectos = [$defecto]; // Defecto principal
    
    // Agregar defectos adicionales si existen
    if (isset($_POST['defecto_adicional']) && is_array($_POST['defecto_adicional'])) {
        foreach ($_POST['defecto_adicional'] as $defecto_adicional) {
            $defecto_adicional = trim($defecto_adicional);
            if (!empty($defecto_adicional) && in_array($defecto_adicional, $defectos_permitidos)) {
                $defectos[] = $defecto_adicional;
            }
        }
    }

    // Validar archivos
    $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    foreach ($archivos as $archivo) {
        if (!in_array($archivo['type'], $tipos_permitidos)) {
            throw new Exception('Solo se permiten archivos de imagen (JPG, PNG, GIF)');
        }
        
        // Verificar tamaño del archivo (máximo 5MB)
        if ($archivo['size'] > 5 * 1024 * 1024) {
            throw new Exception('Uno o más archivos son demasiado grandes. Máximo 5MB permitido por archivo');
        }
    }
    
    // Crear directorio uploads si no existe
    if (!is_dir('../uploads')) {
        mkdir('../uploads', 0755, true);
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Insertar en tabla hallazgos_usa
        $sql_hallazgo = "INSERT INTO hallazgos_usa (id_usuario, fecha, job_order, warehouse, noparte, defecto, observaciones) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_hallazgo = $pdo->prepare($sql_hallazgo);
        $stmt_hallazgo->execute([
            $id_usuario,
            $fecha,
            $job_order,
            $warehouse,
            $noparte,
            $defectos[0], // Defecto principal
            $observaciones
        ]);
        
        $hallazgo_id = $pdo->lastInsertId();
        
        // Crear tabla para defectos múltiples si es necesario
        if (count($defectos) > 1) {
            // Crear tabla hallazgos_usa_defectos si no existe
            $sql_create_defectos = "CREATE TABLE IF NOT EXISTS `hallazgos_usa_defectos` (
                `id` int NOT NULL AUTO_INCREMENT,
                `hallazgo_usa_id` int NOT NULL,
                `defecto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
                `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_hallazgo_usa` (`hallazgo_usa_id`),
                KEY `idx_defecto` (`defecto`),
                CONSTRAINT `hallazgos_usa_defectos_ibfk_1` FOREIGN KEY (`hallazgo_usa_id`) REFERENCES `hallazgos_usa` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql_create_defectos);
            
            // Insertar defectos adicionales
            $sql_defecto = "INSERT INTO hallazgos_usa_defectos (hallazgo_usa_id, defecto) VALUES (?, ?)";
            $stmt_defecto = $pdo->prepare($sql_defecto);
            
            for ($i = 1; $i < count($defectos); $i++) {
                $stmt_defecto->execute([$hallazgo_id, $defectos[$i]]);
            }
        }
        
        // Procesar y guardar archivos
        $archivos_guardados = [];
        foreach ($archivos as $archivo) {
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombre_archivo = 'evidencia_usa_' . uniqid() . '.' . $extension;
            $ruta_destino = '../uploads/' . $nombre_archivo;
            
            // Mover archivo a carpeta de uploads
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                throw new Exception('Error al guardar el archivo: ' . $archivo['name']);
            }
            
            // Insertar evidencia en tabla hallazgos_usa_evidencias
            $sql_evidencia = "INSERT INTO hallazgos_usa_evidencias (hallazgo_usa_id, archivo_nombre, archivo_original, file_size, tipo_mime) 
                              VALUES (?, ?, ?, ?, ?)";
            
            $stmt_evidencia = $pdo->prepare($sql_evidencia);
            $stmt_evidencia->execute([
                $hallazgo_id,
                $nombre_archivo,
                $archivo['name'],
                $archivo['size'],
                $archivo['type']
            ]);
            
            $archivos_guardados[] = $ruta_destino;
        }
        
        // Confirmar transacción
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Hallazgo especial registrado correctamente',
            'hallazgo_id' => $hallazgo_id,
            'defectos_registrados' => count($defectos),
            'evidencias_registradas' => count($archivos_guardados)
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción
        $pdo->rollBack();
        
        // Eliminar archivos si se subieron
        if (isset($archivos_guardados)) {
            foreach ($archivos_guardados as $archivo_path) {
                if (file_exists($archivo_path)) {
                    unlink($archivo_path);
                }
            }
        }
        
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>
