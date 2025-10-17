<?php
session_start();
if (
    !isset($_SESSION['usuario']) ||
    ($_SESSION['usuario']['rol'] !== 'usa' && $_SESSION['usuario']['rol'] !== 'encargadousa')
) {
    header('Location: ../login.php');
    exit;
}

// Conexión directa y simple
try {
    $pdo = new PDO("mysql:host=hallazgos_db;port=3306;dbname=hallazgos;charset=utf8mb4", 
                   'usuario', 'secreto', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Variables de filtros
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $warehouse = $_GET['warehouse'] ?? '';
    $defecto = $_GET['defecto'] ?? '';
    
    // Construir WHERE simple
    $where_parts = [];
    $params = [];
    
    if (!empty($fecha_inicio)) {
        $where_parts[] = "fecha >= ?";
        $params[] = $fecha_inicio;
    }
    if (!empty($fecha_fin)) {
        $where_parts[] = "fecha <= ?";
        $params[] = $fecha_fin;
    }
    if (!empty($warehouse)) {
        $where_parts[] = "warehouse = ?";
        $params[] = $warehouse;
    }
    if (!empty($defecto)) {
        $where_parts[] = "defecto = ?";
        $params[] = $defecto;
    }
    
    $where_clause = '';
    if (!empty($where_parts)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_parts);
    }
    
    // 1. Total registros
    if (empty($params)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM hallazgos_usa");
        $total_registros = $stmt->fetch()['total'];
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM hallazgos_usa $where_clause");
        $stmt->execute($params);
        $total_registros = $stmt->fetch()['total'];
    }
    
    // 2. Defectos
    if (empty($params)) {
        $stmt = $pdo->query("SELECT defecto, COUNT(*) as total FROM hallazgos_usa GROUP BY defecto ORDER BY total DESC");
        $defectos_data = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT defecto, COUNT(*) as total FROM hallazgos_usa $where_clause GROUP BY defecto ORDER BY total DESC");
        $stmt->execute($params);
        $defectos_data = $stmt->fetchAll();
    }
    
    $defecto_labels = [];
    $defecto_counts = [];
    foreach ($defectos_data as $row) {
        $defecto_labels[] = $row['defecto'];
        $defecto_counts[] = (int)$row['total'];
    }
    
    // 3. Warehouses
    if (empty($params)) {
        $stmt = $pdo->query("SELECT warehouse, COUNT(*) as total FROM hallazgos_usa GROUP BY warehouse ORDER BY total DESC LIMIT 10");
        $warehouse_data = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT warehouse, COUNT(*) as total FROM hallazgos_usa $where_clause GROUP BY warehouse ORDER BY total DESC LIMIT 10");
        $stmt->execute($params);
        $warehouse_data = $stmt->fetchAll();
    }
    
    $warehouse_labels = [];
    $warehouse_counts = [];
    foreach ($warehouse_data as $row) {
        $warehouse_labels[] = $row['warehouse'];
        $warehouse_counts[] = (int)$row['total'];
    }
    
    // 4. Evolución mensual
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha, '%Y-%m') as mes,
            COUNT(*) as total
        FROM hallazgos_usa 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha, '%Y-%m')
        ORDER BY mes ASC
    ");
    $evolucion_data = $stmt->fetchAll();
    
    $evolucion_labels = [];
    $evolucion_counts = [];
    foreach ($evolucion_data as $row) {
        $evolucion_labels[] = $row['mes'];
        $evolucion_counts[] = (int)$row['total'];
    }

    // 5. Registros para la tabla con filtros
    if (empty($params)) {
        $stmt = $pdo->query("SELECT * FROM hallazgos_usa ORDER BY fecha_creacion DESC LIMIT 50");
        $registros = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM hallazgos_usa $where_clause ORDER BY fecha_creacion DESC LIMIT 50");
        $stmt->execute($params);
        $registros = $stmt->fetchAll();
    }
    
    // 6. Para cada registro, obtener evidencias y defectos de forma simple
    foreach ($registros as &$registro) {
        // Obtener evidencias
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, MIN(archivo_nombre) as primera FROM hallazgos_usa_evidencias WHERE hallazgo_usa_id = ?");
        $stmt->execute([$registro['id']]);
        $evidencia = $stmt->fetch();
        $registro['total_evidencias'] = $evidencia['total'];
        $registro['primera_evidencia'] = $evidencia['primera'];
        
        // Obtener todos los defectos (principal + adicionales)
        $stmt = $pdo->prepare("
            SELECT DISTINCT defecto 
            FROM (
                SELECT ? as defecto
                UNION ALL
                SELECT defecto FROM hallazgos_usa_defectos WHERE hallazgo_usa_id = ?
            ) defectos_union 
            WHERE defecto IS NOT NULL AND defecto != ''
            ORDER BY defecto
        ");
        $stmt->execute([$registro['defecto'], $registro['id']]);
        $defectos_data = $stmt->fetchAll();
        
        $registro['todos_defectos'] = [];
        foreach ($defectos_data as $defecto_row) {
            $registro['todos_defectos'][] = $defecto_row['defecto'];
        }
        $registro['total_defectos'] = count($registro['todos_defectos']);
    }
    
    // 7. Listas para filtros
    $stmt = $pdo->query("SELECT DISTINCT warehouse FROM hallazgos_usa ORDER BY warehouse");
    $all_warehouses = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT DISTINCT defecto FROM hallazgos_usa ORDER BY defecto");
    $all_defectos = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error en calidadusa.php: " . $e->getMessage());
    $total_registros = 0;
    $defecto_labels = [];
    $defecto_counts = [];
    $warehouse_labels = [];
    $warehouse_counts = [];
    $evolucion_labels = [];
    $evolucion_counts = [];
    $registros = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quality USA Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .main-container {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .dashboard-title { 
            font-weight: 700; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
        }
        
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .filter-card .form-control, .filter-card .form-select {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 10px;
            backdrop-filter: blur(5px);
        }
        
        .filter-card .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .filter-card .form-control:focus, .filter-card .form-select:focus {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
            box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.25);
            color: white;
        }
        
        .filter-card .form-select option {
            background: #333;
            color: white;
        }
        
        .btn-filter {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 10px;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }
        
        .btn-filter:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,255,255,0.2);
        }
        
        .card-summary { 
            border: none; 
            border-radius: 15px; 
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .card-summary:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .chart-card { 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: white;
        }
        
        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .chart-card .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px 15px 0 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .modern-table {
            border-radius: 0 0 15px 15px;
            overflow: hidden;
        }
        
        .modern-table thead th {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            font-weight: 600;
            border: none;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .table-row-hover:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }
        
        .evidence-thumbnail:hover {
            transform: scale(1.1);
            transition: all 0.3s ease;
            border-color: #667eea !important;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        /* Estilos mejorados para evidencias múltiples */
        .multiple-evidence {
            position: relative;
            animation: pulse-border 2s infinite;
        }
        
        .multiple-evidence:hover {
            transform: scale(1.15) !important;
            border-color: #28a745 !important;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4) !important;
        }
        
        /* Animación sutil para evidencias múltiples */
        @keyframes pulse-border {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }
            70% {
                box-shadow: 0 0 0 3px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        /* Mejorar visibilidad del badge de múltiples evidencias */
        .multiple-evidence-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
            animation: badge-glow 3s ease-in-out infinite;
        }
        
        @keyframes badge-glow {
            0%, 100% {
                box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
            }
            50% {
                box-shadow: 0 4px 12px rgba(40, 167, 69, 0.6);
            }
        }
        
        /* Estilos para el contador de evidencias */
        .evidence-counter {
            font-size: 0.75rem;
            margin-top: 4px;
        }
        
        .evidence-counter.multiple {
            color: #28a745;
            font-weight: 600;
        }
        
        .evidence-counter.single {
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Estilos para múltiples defectos */
        .main-defect {
            font-weight: 600;
            border: 2px solid #ffc107;
        }
        
        .multiple-defects-indicator {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
            color: white !important;
            cursor: help;
            font-weight: 600;
            font-size: 0.7rem;
            animation: defect-glow 2s ease-in-out infinite;
        }
        
        .multiple-defects-indicator:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%) !important;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }
        
        @keyframes defect-glow {
            0%, 100% {
                box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
            }
            50% {
                box-shadow: 0 4px 12px rgba(23, 162, 184, 0.6);
            }
        }
        
        /* Estilos para el collapse de defectos */
        .defects-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .defects-list .badge {
            font-size: 0.8rem;
            margin-bottom: 2px;
        }
        
        /* Botón para expandir defectos */
        .expand-defects-btn {
            color: #6c757d;
            font-size: 0.75rem;
            transition: all 0.2s ease;
        }
        
        .expand-defects-btn:hover {
            color: #495057;
            text-decoration: underline !important;
        }
        
        .expand-defects-btn i {
            transition: transform 0.2s ease;
        }
        
        .expand-defects-btn[aria-expanded="true"] i {
            transform: rotate(180deg);
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: none;
            border-radius: 10px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
            color: white;
        }
        
        .export-btn {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            border: none;
            border-radius: 10px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background: linear-gradient(135deg, #229954 0%, #1e8449 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
            color: white;
        }
        
        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 1.8rem;
            }
            .main-container {
                margin: 10px;
                border-radius: 15px;
            }
        }
        
        /* Modal Styles - Solo aplicar cuando está visible */
        .modal.show {
            z-index: 1055 !important;
            display: flex !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            overflow: auto !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 1rem !important;
        }
        
        .modal-backdrop.show {
            z-index: 1050 !important;
        }
        
        .modal-dialog {
            position: relative !important;
            width: auto !important;
            max-width: 90vw !important;
            max-height: 90vh !important;
            margin: 0 auto !important;
            pointer-events: auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .modal-content {
            position: relative;
            pointer-events: auto !important;
        }
        
        /* Asegurar que el modal esté oculto por defecto */
        .modal:not(.show) {
            display: none !important;
            pointer-events: none !important;
        }
        
        /* Permitir interacción cuando el modal está activo */
        .modal.show, .modal.show * {
            pointer-events: auto !important;
        }
        
        .modal-content {
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            pointer-events: auto !important;
            background-color: #fff !important;
            background-clip: padding-box !important;
            border: none !important;
            border-radius: 15px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
            outline: 0 !important;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            border-radius: 15px 15px 0 0 !important;
            border-bottom: none !important;
            display: flex !important;
            flex-shrink: 0 !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 1rem 1rem !important;
        }
        
        .modal-body {
            position: relative !important;
            flex: 1 1 auto !important;
            padding: 2rem !important;
            background: white !important;
            border-radius: 0 0 15px 15px !important;
        }
        
        .modal-body img {
            border-radius: 10px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
            max-width: 100% !important;
            height: auto !important;
            max-height: 70vh !important;
        }
        
        .btn-close {
            background: transparent !important;
            border: 0 !important;
            border-radius: 0.375rem !important;
            color: white !important;
            cursor: pointer !important;
            filter: invert(1) !important;
            opacity: 1 !important;
            padding: 0.25rem 0.25rem !important;
            margin: -0.25rem -0.25rem -0.25rem auto !important;
        }
        
        .btn-close:hover {
            opacity: 0.75 !important;
        }
        
        /* Remove backdrop filter from main container when modal is open */
        body.modal-open .main-container {
            filter: none !important;
        }
        
        /* Image gallery in modal - Fixed positioning */
        .modal-image-container {
            position: relative !important;
            display: inline-block !important;
            width: 100% !important;
            text-align: center !important;
        }
        
        .modal-nav-btn {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background: rgba(0,0,0,0.7) !important;
            color: white !important;
            border: none !important;
            border-radius: 50% !important;
            width: 50px !important;
            height: 50px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            z-index: 1060 !important;
            font-size: 1.2rem !important;
            transition: all 0.3s ease !important;
            pointer-events: auto !important;
        }
        
        .modal-nav-btn:hover {
            background: rgba(0,0,0,0.9) !important;
            transform: translateY(-50%) scale(1.1) !important;
        }
        
        .modal-nav-btn:active {
            transform: translateY(-50%) scale(0.95) !important;
        }
        
        .modal-nav-prev {
            left: 20px !important;
        }
        
        .modal-nav-next {
            right: 20px !important;
        }
        
        /* Forzar que todos los botones sean clickeables */
        button, .btn, a[href] {
            pointer-events: auto !important;
            cursor: pointer !important;
            z-index: 9999 !important;
        }
        
        /* Asegurar que no hay overlays bloqueando */
        body::before, body::after, 
        .container::before, .container::after,
        .main-container::before, .main-container::after {
            pointer-events: none !important;
        }
        
        /* Eliminar cualquier overlay invisible */
        body > *:not(.main-container):not(script):not(style) {
            position: relative !important;
            z-index: auto !important;
        }
        
        /* Forzar interactividad en área de contenido */
        .main-container, .main-container * {
            pointer-events: auto !important;
        }
        
        /* Debug: resaltar elementos clickeables */
        button:hover, .btn:hover, a[href]:hover {
            outline: 2px solid red !important;
            background-color: rgba(255,0,0,0.1) !important;
        }
        
        .modal-nav-prev {
            left: 10px !important;
        }
        
        .modal-nav-next {
            right: 10px !important;
        }
        
        .image-counter {
            position: absolute !important;
            bottom: -40px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            background: rgba(0,0,0,0.8) !important;
            color: white !important;
            padding: 8px 16px !important;
            border-radius: 20px !important;
            font-size: 14px !important;
            font-weight: 500 !important;
        }
        
        /* Mobile adjustments */
        @media (max-width: 768px) {
            .modal-nav-prev {
                left: 5px !important;
            }
            
            .modal-nav-next {
                right: 5px !important;
            }
            
            .modal-nav-btn {
                width: 40px !important;
                height: 40px !important;
                font-size: 1rem !important;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
<div class="container-fluid py-5">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h1 class="dashboard-title mb-0">
            <i class="bi bi-patch-check-fill"></i> USA Quality Dashboard
        </h1>
        <div class="d-flex gap-3">
            <a href="registro_usa.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle-fill"></i> New Finding
            </a>
            <a href="../logout.php" class="btn btn-outline-danger btn-lg">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card filter-card">
        <div class="card-body p-4">
            <h5 class="card-title text-white mb-4">
                <i class="bi bi-funnel-fill"></i> Advanced Filters
            </h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-white fw-bold">
                        <i class="bi bi-calendar-range"></i> Start Date
                    </label>
                    <input type="date" name="fecha_inicio" class="form-control" 
                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-white fw-bold">
                        <i class="bi bi-calendar-check"></i> End Date
                    </label>
                    <input type="date" name="fecha_fin" class="form-control" 
                           value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-white fw-bold">
                        <i class="bi bi-building"></i> Warehouse
                    </label>
                    <select name="warehouse" class="form-select">
                        <option value="">🏢 All Warehouses</option>
                        <?php foreach ($all_warehouses as $wh): ?>
                            <option value="<?= htmlspecialchars($wh['warehouse']) ?>" 
                                    <?= $warehouse === $wh['warehouse'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wh['warehouse']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-white fw-bold">
                        <i class="bi bi-exclamation-triangle"></i> Defect Type
                    </label>
                    <select name="defecto" class="form-select">
                        <option value="">⚠️ All Defects</option>
                        <?php foreach ($all_defectos as $def): ?>
                            <option value="<?= htmlspecialchars($def['defecto']) ?>" 
                                    <?= $defecto === $def['defecto'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($def['defecto']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="submit" class="btn btn-filter btn-lg px-4">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                        <a href="?" class="btn btn-filter btn-lg px-4">
                            <i class="bi bi-arrow-clockwise"></i> Clear All
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cards resumen -->
    <div class="row g-4 mb-5">
        <div class="col-12 col-lg-3 col-md-6">
            <div class="card card-summary text-center h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="mb-3">
                        <i class="bi bi-clipboard-data" style="font-size: 2.5rem; color: rgba(255,255,255,0.9);"></i>
                    </div>
                    <div class="fs-1 fw-bold mb-2"><?= number_format($total_registros) ?></div>
                    <div class="fs-6 opacity-85 text-uppercase fw-semibold">Total Findings</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3 col-md-6">
            <div class="card card-summary text-center h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="mb-3">
                        <i class="bi bi-building" style="font-size: 2.5rem; color: rgba(255,255,255,0.9);"></i>
                    </div>
                    <div class="fs-1 fw-bold mb-2"><?= count($warehouse_labels) ?></div>
                    <div class="fs-6 opacity-85 text-uppercase fw-semibold">Warehouses</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3 col-md-6">
            <div class="card card-summary text-center h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="mb-3">
                        <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem; color: rgba(255,255,255,0.9);"></i>
                    </div>
                    <div class="fs-1 fw-bold mb-2"><?= count($defecto_labels) ?></div>
                    <div class="fs-6 opacity-85 text-uppercase fw-semibold">Defect Types</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3 col-md-6">
            <div class="card card-summary text-center h-100" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="mb-3">
                        <i class="bi bi-calendar-week" style="font-size: 2.5rem; color: rgba(255,255,255,0.9);"></i>
                    </div>
                    <div class="fs-1 fw-bold mb-2"><?= date('W') ?></div>
                    <div class="fs-6 opacity-85 text-uppercase fw-semibold">Current Week</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="bi bi-bar-chart-line me-2 text-primary"></i>
                        Findings by Defect Type
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="barDefectos" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="bi bi-pie-chart me-2 text-success"></i>
                        Defect Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="pieDefectos" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="bi bi-building me-2 text-info"></i>
                        Top Warehouses
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="barWarehouses" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card chart-card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="bi bi-graph-up me-2 text-warning"></i>
                        Monthly Trend
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="lineEvolucion" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <div class="card border-0 shadow-lg mb-5">
        <div class="card-header bg-gradient-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-table me-2" style="font-size: 1.2rem;"></i>
                    <h5 class="mb-0 fw-bold text-white">Findings Records</h5>
                </div>
                <div>
                    <span class="badge bg-white text-primary fw-bold fs-6 px-3 py-2"><?= count($registros) ?> records</span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 modern-table">
                    <thead class="table-dark">
                        <tr>
                            <th class="px-4 py-3">
                                <i class="bi bi-calendar3 me-1"></i> Date
                            </th>
                            <th class="px-4 py-3">
                                <i class="bi bi-file-text me-1"></i> Job Order
                            </th>
                            <th class="px-4 py-3">
                                <i class="bi bi-building me-1"></i> Warehouse
                            </th>
                            <th class="px-4 py-3">
                                <i class="bi bi-box me-1"></i> Part No.
                            </th>
                            <th class="px-4 py-3">
                                <i class="bi bi-exclamation-triangle me-1"></i> Defect
                            </th>
                            <th class="px-4 py-3 text-center">
                                <i class="bi bi-image me-1"></i> Evidence
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-search" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <div class="mt-2 fs-5">No findings found</div>
                                    <div class="small">Try adjusting your filters</div>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($registros as $index => $registro): ?>
                        <tr class="table-row-hover">
                            <td class="px-4 py-3">
                                <div class="fw-semibold"><?= date('M d, Y', strtotime($registro['fecha'])) ?></div>
                                <div class="small text-muted"><?= date('H:i', strtotime($registro['fecha'])) ?></div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-primary fs-6 px-3 py-2"><?= htmlspecialchars($registro['job_order']) ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-building text-info me-2"></i>
                                    <span class="fw-medium"><?= htmlspecialchars($registro['warehouse']) ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (!empty($registro['noparte'])): ?>
                                    <code class="bg-light text-dark px-2 py-1 rounded"><?= htmlspecialchars($registro['noparte']) ?></code>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($registro['total_defectos'] > 1): ?>
                                    <!-- Múltiples defectos -->
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-warning text-dark fs-6 px-2 py-1 main-defect">
                                            <?= htmlspecialchars($registro['defecto']) ?>
                                        </span>
                                        <span class="badge bg-info text-white px-2 py-1 multiple-defects-indicator" 
                                              title="<?= implode(', ', array_map('htmlspecialchars', $registro['todos_defectos'])) ?>">
                                            +<?= $registro['total_defectos'] - 1 ?> más
                                        </span>
                                    </div>
                                    <div class="mt-1 small text-muted">
                                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                        <?= $registro['total_defectos'] ?> defectos total
                                    </div>
                                    <!-- Lista expandible de todos los defectos -->
                                    <div class="collapse mt-2" id="defects-<?= $registro['id'] ?>">
                                        <div class="card card-body p-2 bg-light">
                                            <div class="small">
                                                <strong>Todos los defectos:</strong><br>
                                                <?php foreach ($registro['todos_defectos'] as $idx => $defecto): ?>
                                                    <span class="badge bg-secondary me-1 mb-1">
                                                        <?= ($idx + 1) ?>. <?= htmlspecialchars($defecto) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn btn-link btn-sm p-0 mt-1 text-decoration-none expand-defects-btn" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#defects-<?= $registro['id'] ?>" 
                                            aria-expanded="false">
                                        <small><i class="bi bi-chevron-down"></i> Ver todos</small>
                                    </button>
                                <?php else: ?>
                                    <!-- Un solo defecto -->
                                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                                        <?= htmlspecialchars($registro['defecto']) ?>
                                    </span>
                                    <div class="mt-1 small text-muted">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        1 defecto
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($registro['total_evidencias'] > 0 && !empty($registro['primera_evidencia'])): ?>
                                    <div class="position-relative d-inline-block">
                                        <img src="../uploads/<?= htmlspecialchars($registro['primera_evidencia']) ?>"
                                             class="evidence-thumbnail <?= $registro['total_evidencias'] > 1 ? 'multiple-evidence' : '' ?>"
                                             style="width:50px;height:50px;object-fit:cover;border-radius:8px;cursor:pointer;border:2px solid <?= $registro['total_evidencias'] > 1 ? '#28a745' : '#dee2e6' ?>;"
                                             title="<?= $registro['total_evidencias'] ?> file(s) - Click to view all"
                                             onclick="showImageModal('<?= htmlspecialchars($registro['primera_evidencia']) ?>', <?= $registro['id'] ?>)">
                                        
                                        <?php if ($registro['total_evidencias'] > 1): ?>
                                            <!-- Badge mejorado para múltiples evidencias -->
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill multiple-evidence-badge border border-2 border-white" 
                                                  style="font-size: 0.7rem; font-weight: bold; min-width: 20px; z-index: 10;">
                                                <?= $registro['total_evidencias'] ?>
                                                <i class="bi bi-images ms-1" style="font-size: 0.6rem;"></i>
                                            </span>
                                            
                                            <!-- Indicador visual adicional -->
                                            <div class="position-absolute bottom-0 end-0 translate-middle-x" 
                                                 style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; font-size: 0.6rem; 
                                                        padding: 1px 4px; border-radius: 3px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                                GALLERY
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Texto descriptivo debajo de la imagen -->
                                    <div class="evidence-counter <?= $registro['total_evidencias'] > 1 ? 'multiple' : 'single' ?>">
                                        <?php if ($registro['total_evidencias'] > 1): ?>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="bi bi-images me-1"></i>
                                                <span class="fw-bold"><?= $registro['total_evidencias'] ?> files</span>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.65rem;">Click to browse all</div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image me-1"></i>
                                                <span>1 file</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <div class="text-muted mb-1">
                                            <i class="bi bi-image-alt" style="font-size: 1.5rem; opacity: 0.3;"></i>
                                        </div>
                                        <div class="small text-muted">No evidence</div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for image preview -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">
                    <i class="bi bi-image me-2"></i>Evidence Preview
                </h5>
                <div class="d-flex align-items-center me-3">
                    <span class="badge bg-white text-primary px-3 py-2" id="modalImageCount" style="display: none;">
                        <i class="bi bi-images me-1"></i>
                        <span id="modalCurrentIndex">1</span> of <span id="modalTotalImages">1</span>
                    </span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status" id="imageLoader" style="display: none;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                
                <div class="modal-image-container" id="imageContainer" style="display: none;">
                    <button class="modal-nav-btn modal-nav-prev" id="prevBtn" style="display: none;" onclick="navigateImage(-1)">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    
                    <img id="modalImage" src="" class="img-fluid rounded shadow" alt="Evidence" 
                         style="max-height: 70vh; max-width: 100%;"
                         onload="hideLoader()" onerror="showError()">
                    
                    <button class="modal-nav-btn modal-nav-next" id="nextBtn" style="display: none;" onclick="navigateImage(1)">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                    
                    <div class="image-counter" id="imageCounter" style="display: none;">
                        <span id="currentImageIndex">1</span> / <span id="totalImages">1</span>
                    </div>
                </div>
                
                <div id="imageError" style="display: none;" class="text-danger">
                    <i class="bi bi-exclamation-triangle"></i> Error loading image
                </div>
                
                <!-- Additional close button -->
                <div class="mt-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales para el modal de imágenes
let currentImages = [];
let currentImageIndex = 0;

// Function to show image modal with better error handling
function showImageModal(imageName, hallazgoId = null) {
    console.log('Opening modal for image:', imageName, 'hallazgo:', hallazgoId);
    
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    
    const loader = document.getElementById('imageLoader');
    const errorDiv = document.getElementById('imageError');
    const imageContainer = document.getElementById('imageContainer');
    const modalTitle = document.getElementById('imageModalLabel');
    
    // Reset states
    imageContainer.style.display = 'none';
    errorDiv.style.display = 'none';
    loader.style.display = 'block';
    
    // Update modal title
    if (modalTitle) {
        modalTitle.innerHTML = '<i class="bi bi-image me-2"></i>Evidence Preview';
    }
    
    // Si tenemos hallazgoId, obtener todas las imágenes
    if (hallazgoId) {
        fetchAllImages(hallazgoId, imageName);
    } else {
        // Solo mostrar una imagen
        currentImages = [imageName];
        currentImageIndex = 0;
        loadCurrentImage();
    }
    
    // Show modal and force center positioning
    modal.show();
    
    // Forzar centrado después de mostrar el modal
    setTimeout(() => {
        const modalElement = document.getElementById('imageModal');
        const modalDialog = modalElement.querySelector('.modal-dialog');
        
        if (modalElement && modalDialog) {
            // Forzar posicionamiento fijo en el centro del viewport
            modalElement.style.position = 'fixed';
            modalElement.style.top = '0';
            modalElement.style.left = '0';
            modalElement.style.width = '100vw';
            modalElement.style.height = '100vh';
            modalElement.style.display = 'flex';
            modalElement.style.alignItems = 'center';
            modalElement.style.justifyContent = 'center';
            modalElement.style.overflow = 'auto';
            modalElement.style.zIndex = '1055';
            
            // Centrar el dialog
            modalDialog.style.margin = '0';
            modalDialog.style.position = 'relative';
            modalDialog.style.transform = 'none';
        }
    }, 100);
}

// Función para obtener todas las imágenes de un hallazgo
function fetchAllImages(hallazgoId, currentImage) {
    fetch(`get_images.php?hallazgo_id=${hallazgoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.images.length > 0) {
                currentImages = data.images;
                // Encontrar el índice de la imagen actual
                currentImageIndex = currentImages.indexOf(currentImage);
                if (currentImageIndex === -1) {
                    currentImageIndex = 0;
                }
                loadCurrentImage();
            } else {
                // Fallback a imagen única
                currentImages = [currentImage];
                currentImageIndex = 0;
                loadCurrentImage();
            }
        })
        .catch(error => {
            console.error('Error fetching images:', error);
            // Fallback a imagen única
            currentImages = [currentImage];
            currentImageIndex = 0;
            loadCurrentImage();
        });
}

// Cargar la imagen actual con mejor manejo
function loadCurrentImage() {
    console.log('Loading image at index:', currentImageIndex);
    console.log('Image name:', currentImages[currentImageIndex]);
    
    const modalImage = document.getElementById('modalImage');
    const loader = document.getElementById('imageLoader');
    const imageContainer = document.getElementById('imageContainer');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const counter = document.getElementById('imageCounter');
    const currentIndexSpan = document.getElementById('currentImageIndex');
    const totalImagesSpan = document.getElementById('totalImages');
    
    // Elementos del header del modal
    const modalImageCount = document.getElementById('modalImageCount');
    const modalCurrentIndex = document.getElementById('modalCurrentIndex');
    const modalTotalImages = document.getElementById('modalTotalImages');
    
    // Show loader
    loader.style.display = 'block';
    imageContainer.style.display = 'none';
    
    // Set image source
    modalImage.src = '../uploads/' + currentImages[currentImageIndex];
    
    // Update counter in modal body
    if (currentIndexSpan && totalImagesSpan) {
        currentIndexSpan.textContent = currentImageIndex + 1;
        totalImagesSpan.textContent = currentImages.length;
    }
    
    // Update counter in modal header
    if (modalCurrentIndex && modalTotalImages && modalImageCount) {
        modalCurrentIndex.textContent = currentImageIndex + 1;
        modalTotalImages.textContent = currentImages.length;
        
        // Show/hide header counter based on number of images
        if (currentImages.length > 1) {
            modalImageCount.style.display = 'inline-block';
        } else {
            modalImageCount.style.display = 'none';
        }
    }
    
    // Show/hide navigation buttons
    if (currentImages.length > 1) {
        if (prevBtn) {
            prevBtn.style.display = currentImageIndex > 0 ? 'flex' : 'none';
            console.log('Prev button display:', prevBtn.style.display);
        }
        if (nextBtn) {
            nextBtn.style.display = currentImageIndex < currentImages.length - 1 ? 'flex' : 'none';
            console.log('Next button display:', nextBtn.style.display);
        }
        if (counter) {
            counter.style.display = 'block';
        }
    } else {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
        if (counter) counter.style.display = 'none';
    }
}

// Function to close modal
function closeImageModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('imageModal'));
    if (modal) {
        modal.hide();
    }
}

// Navegar entre imágenes con mejor debug
function navigateImage(direction) {
    console.log('Navigate called with direction:', direction);
    console.log('Current index:', currentImageIndex);
    console.log('Total images:', currentImages.length);
    
    if (!currentImages || currentImages.length === 0) {
        console.log('No images available');
        return;
    }
    
    const newIndex = currentImageIndex + direction;
    console.log('New index would be:', newIndex);
    
    if (newIndex >= 0 && newIndex < currentImages.length) {
        currentImageIndex = newIndex;
        console.log('Updating to index:', currentImageIndex);
        loadCurrentImage();
    } else {
        console.log('Navigation blocked - out of bounds');
    }
}

// Helper functions for image loading
function hideLoader() {
    const loader = document.getElementById('imageLoader');
    const imageContainer = document.getElementById('imageContainer');
    
    if (loader) loader.style.display = 'none';
    if (imageContainer) imageContainer.style.display = 'inline-block';
}

function showError() {
    const loader = document.getElementById('imageLoader');
    const errorDiv = document.getElementById('imageError');
    const imageContainer = document.getElementById('imageContainer');
    
    if (loader) loader.style.display = 'none';
    if (imageContainer) imageContainer.style.display = 'none';
    if (errorDiv) errorDiv.style.display = 'block';
}

// Keyboard navigation simplificado
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageModal');
    if (modal && modal.classList.contains('show')) {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            navigateImage(-1);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            navigateImage(1);
        }
    }
});

// DOM ready - versión simplificada
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded successfully');
    
    // Test básico para verificar si JavaScript funciona
    console.log('Testing JavaScript functionality...');
    
    // Verificar si hay errores en las funciones
    try {
        console.log('Bootstrap version:', bootstrap);
        console.log('jQuery available:', typeof $ !== 'undefined');
    } catch (e) {
        console.error('Error in bootstrap/jQuery check:', e);
    }
    
    // Animate cards on load
    const cards = document.querySelectorAll('.card-summary, .chart-card');
    console.log('Cards found:', cards.length);
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Add smooth scrolling
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Test de botones
    setTimeout(() => {
        console.log('Testing buttons...');
        
        // Test del botón de filtros (primera versión simple)
        const filterBtnOriginal = document.querySelector('button[type="submit"]');
        if (filterBtnOriginal) {
            console.log('Filter button found:', filterBtnOriginal);
            filterBtnOriginal.addEventListener('click', function(e) {
                console.log('Filter button clicked!');
            });
        } else {
            console.log('Filter button NOT found');
        }
        
        // Test del botón de logout (primera versión simple) 
        const logoutBtnOriginal = document.querySelector('a[href="../logout.php"]');
        if (logoutBtnOriginal) {
            console.log('Logout button found:', logoutBtnOriginal);
            logoutBtnOriginal.addEventListener('click', function(e) {
                console.log('Logout button clicked!');
            });
        } else {
            console.log('Logout button NOT found');
        }
        
        // Test de botones con métodos más directos
        const filterBtnTest = document.querySelector('button[type="submit"]');
        if (filterBtnTest) {
            console.log('Filter button found:', filterBtnTest);
            
            // Probar múltiples métodos de evento
            filterBtnTest.onclick = function(e) {
                console.log('Filter button onclick triggered!');
            };
            
            filterBtnTest.addEventListener('click', function(e) {
                console.log('Filter button addEventListener triggered!');
            });
            
            // Test de hover para ver si el elemento responde
            filterBtnTest.addEventListener('mouseenter', function() {
                console.log('Filter button hover detected!');
            });
        }
        
        // Test del botón de logout
        const logoutBtnTest = document.querySelector('a[href="../logout.php"]');
        if (logoutBtnTest) {
            console.log('Logout button found:', logoutBtnTest);
            
            logoutBtnTest.onclick = function(e) {
                console.log('Logout button onclick triggered!');
            };
            
            logoutBtnTest.addEventListener('mouseenter', function() {
                console.log('Logout button hover detected!');
            });
        }
        
        // Test más general de todos los botones con múltiples eventos
        const allButtons = document.querySelectorAll('button, .btn, a[href]');
        console.log('All clickable elements found:', allButtons.length);
        
        allButtons.forEach((btn, index) => {
            // Agregar múltiples tipos de eventos
            ['click', 'mousedown', 'mouseup', 'touchstart'].forEach(eventType => {
                btn.addEventListener(eventType, function(e) {
                    console.log(`Element ${index} ${eventType} detected:`, btn.tagName, btn.className);
                }, { passive: false, capture: true });
            });
            
            btn.addEventListener('mouseenter', function() {
                console.log(`Element ${index} hover detected:`, btn.tagName, btn.className);
            });
            
            // Forzar onclick si no tiene uno
            if (!btn.onclick) {
                btn.onclick = function(e) {
                    console.log(`Element ${index} onclick fallback:`, btn.tagName, btn.className);
                    
                    // Si es el botón de submit, forzar envío
                    if (btn.type === 'submit') {
                        console.log('Forcing form submit...');
                        const form = btn.closest('form');
                        if (form) {
                            form.submit();
                        }
                    }
                    
                    // Si es un enlace, forzar navegación
                    if (btn.tagName === 'A' && btn.href) {
                        console.log('Forcing navigation to:', btn.href);
                        window.location.href = btn.href;
                    }
                };
            }
        });
        
    }, 1000);
    
    console.log('DOM setup completed');
});

// Enhanced Chart.js configurations
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
Chart.defaults.font.size = 12;

// Datos para gráficas
const defectLabels = <?= json_encode($defecto_labels) ?>;
const defectCounts = <?= json_encode($defecto_counts) ?>;
const warehouseLabels = <?= json_encode($warehouse_labels) ?>;
const warehouseCounts = <?= json_encode($warehouse_counts) ?>;
const evolucionLabels = <?= json_encode($evolucion_labels) ?>;
const evolucionCounts = <?= json_encode($evolucion_counts) ?>;

// Gráfica de barras - Defectos
if (defectLabels.length > 0) {
    new Chart(document.getElementById('barDefectos'), {
        type: 'bar',
        data: {
            labels: defectLabels,
            datasets: [{
                label: 'Findings',
                data: defectCounts,
                backgroundColor: '#667eea'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

// Gráfica de pie - Distribución
if (defectLabels.length > 0) {
    new Chart(document.getElementById('pieDefectos'), {
        type: 'doughnut',
        data: {
            labels: defectLabels,
            datasets: [{
                data: defectCounts,
                backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

// Gráfica warehouses
if (warehouseLabels.length > 0) {
    new Chart(document.getElementById('barWarehouses'), {
        type: 'bar',
        data: {
            labels: warehouseLabels,
            datasets: [{
                label: 'Findings',
                data: warehouseCounts,
                backgroundColor: '#43e97b'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
}

// Gráfica evolución
if (evolucionLabels.length > 0) {
    new Chart(document.getElementById('lineEvolucion'), {
        type: 'line',
        data: {
            labels: evolucionLabels,
            datasets: [{
                label: 'Monthly Findings',
                data: evolucionCounts,
                borderColor: '#f093fb',
                backgroundColor: 'rgba(240, 147, 251, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}
</script>
</body>
</html>
