<?php
session_start();
require_once 'includes/db_config.php';

// Verificar si el usuario está autenticado y es encargado
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'encargado') {
    header('Location: index.php');
    exit;
}

$user_name = $_SESSION['usuario']['nombre'] ?? 'Encargado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Sistema de Calidad</title>
    <meta name="theme-color" content="#667eea">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="manifest.json" crossorigin="anonymous">
    <link rel="apple-touch-icon" href="assets/img/Logo.jpg">
    <link rel="icon" sizes="192x192" href="assets/img/Logo.jpg" type="image/jpeg">
    <link rel="icon" sizes="512x512" href="assets/img/Logo.jpg" type="image/jpeg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-dashboard.css" rel="stylesheet">
    <style>
        /* Estilos para Cards de Estadísticas Modernas */
        .stat-card-modern {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .stat-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
        }
        
        /* Bordes coloridos en las cards */
        .border-left-primary {
            border-left: 5px solid #4e73df !important;
            background: linear-gradient(135deg, #f8f9ff 0%, #e3ebff 100%);
        }
        .border-left-success {
            border-left: 5px solid #1cc88a !important;
            background: linear-gradient(135deg, #f0fff4 0%, #dcf8e5 100%);
        }
        .border-left-warning {
            border-left: 5px solid #f6c23e !important;
            background: linear-gradient(135deg, #fffbf0 0%, #fff2cc 100%);
        }
        .border-left-danger {
            border-left: 5px solid #e74a3b !important;
            background: linear-gradient(135deg, #fef8f8 0%, #fce4e4 100%);
        }
        .border-left-info {
            border-left: 5px solid #36b9cc !important;
            background: linear-gradient(135deg, #f0fdff 0%, #d1ecf1 100%);
        }
        .border-left-secondary {
            border-left: 5px solid #858796 !important;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        /* Iconos grandes en las cards */
        .stat-icon-large {
            font-size: 2.5rem;
            opacity: 0.3;
            transition: all 0.3s ease;
        }
        .stat-card-modern:hover .stat-icon-large {
            opacity: 0.6;
            transform: scale(1.1);
        }
        
        /* Iconos pequeños junto al texto */
        .stat-icon-small {
            font-size: 0.9rem;
        }

        /* Texto de estadísticas */
        .text-xs {
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .font-weight-bold {
            font-weight: 700 !important;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        .text-gray-300 {
            color: #dddfeb !important;
        }

        /* Colores personalizados para texto */
        .text-primary {
            color: #4e73df !important;
        }
        .text-success {
            color: #1cc88a !important;
        }
        .text-warning {
            color: #f6c23e !important;
        }
        .text-danger {
            color: #e74a3b !important;
        }
        .text-info {
            color: #36b9cc !important;
        }
        .text-secondary {
            color: #858796 !important;
        }

        /* Estilos para barras de progreso */
        .progress-sm {
            height: 0.5rem;
        }
        .progress {
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-bar {
            transition: width 1s ease-in-out;
        }

        /* Sistema de grid sin gutters */
        .no-gutters {
            margin-right: 0;
            margin-left: 0;
        }
        .no-gutters > .col,
        .no-gutters > [class*="col-"] {
            padding-right: 0;
            padding-left: 0;
        }

        /* Animación de contadores */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .h5 {
            animation: countUp 0.5s ease-out;
        }

        .defectos-clickeable:hover {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }
        .defecto-item {
            transition: all 0.3s ease;
        }
        .defecto-item:hover {
            background-color: #f8f9fa;
            border-color: #ffc107 !important;
        }
        .defecto-texto {
            color: #495057;
            font-size: 0.95rem;
        }
        .defectos-list {
            max-height: 400px;
            overflow-y: auto;
        }
        /* Estilos para observaciones */
        .observaciones-content .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .observaciones-text {
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 300px;
            overflow-y: auto;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            min-width: 200px;
        }
        .action-buttons .btn {
            flex: 0 0 auto;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .action-buttons .btn i {
            margin-right: 0.25rem;
        }
        /* Estilos específicos para botones de acción */
        .btn-sm {
            white-space: nowrap;
        }
        /* Ajustar ancho de la columna de acciones */
        .table th:last-child,
        .table td:last-child {
            min-width: 220px;
        }
        
        /* Estilos para la gráfica de estaciones */
        .border-left-danger {
            border-left: 0.25rem solid #e74a3b !important;
        }
        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        .text-xs {
            font-size: 0.75rem;
        }
        .font-weight-bold {
            font-weight: 700 !important;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        .text-gray-300 {
            color: #dddfeb !important;
        }
        .no-gutters {
            margin-right: 0;
            margin-left: 0;
        }
        .no-gutters > .col,
        .no-gutters > [class*="col-"] {
            padding-right: 0;
            padding-left: 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                Sistema de Calidad - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a href="scrap_dashboard.php" class="btn btn-outline-danger btn-sm me-2">
                    <i class="fas fa-trash me-1"></i>
                    Scrap
                </a>
                <a href="historico.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-history me-1"></i>
                    Histórico
                </a>
                <span class="navbar-text me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    Bienvenido, <?php echo htmlspecialchars($user_name); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard Administrativo
            </h1>
            <p class="page-subtitle">
                Monitoreo y análisis de registros de calidad 
            </p>
        </div>

        <!-- Filtros -->
        <div class="filter-section">
            <h5><i class="fas fa-filter me-2"></i>Filtros de Fecha y Área</h5>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fechaInicio">
                    <div class="date-range-helper">Selecciona la fecha de inicio</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fechaFin">
                    <div class="date-range-helper">Selecciona la fecha de fin</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Área</label>
                    <select class="form-select" id="filtroArea">
                        <option value="">Todas las áreas</option>
                        <option value="Plasma">Plasma</option>
                        <option value="Prensas">Prensas</option>
                        <option value="Beam welder">Beam welder</option>
                        <option value="Roladora">Roladora</option>
                        <option value="Sierras">Sierras</option>
                        <option value="Fresadora">Fresadora</option>
                        <option value="Vulcanizadora">Vulcanizadora</option>
                        <option value="soldadura">Soldadura</option>
                        <option value="ejes">Ejes</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="aplicarFiltros()">
                        <i class="fas fa-search me-1"></i>
                        Filtrar
                    </button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <label class="form-label">Rangos rápidos:</label>
                    <div class="quick-date-buttons">
                        <button type="button" class="quick-date-btn" onclick="setQuickRange('today')">Hoy</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickRange('yesterday')">Ayer</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickRange('thisWeek')">Esta semana</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickRange('lastWeek')">Semana pasada</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickRange('thisMonth')">Este mes</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickRange('lastMonth')">Mes pasado</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickRange('last30Days')">Últimos 30 días</button>
                        <button type="button" class="quick-date-btn" onclick="setQuickRange('last90Days')">Últimos 90 días</button>
                        <button type="button" class="quick-date-btn" onclick="clearDates()">Limpiar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Mejoradas -->
        <div class="row mb-4">
            <!-- Total de Registros -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total de Registros
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="totalRegistros">0</span>
                                </div>
                                <div class="mt-2 d-flex align-items-center">
                                    <div class="stat-icon-small text-primary me-2">
                                        <i class="fas fa-cubes"></i>
                                    </div>
                                    <div class="small text-muted">
                                        <span id="totalPiezas" class="font-weight-bold text-primary">0</span> piezas procesadas
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stat-icon-large text-primary">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registros con Defectos -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Con Registros
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="registrosConHallazgos">0</span>
                                </div>
                                <div class="mt-2 d-flex align-items-center">
                                    <div class="stat-icon-small text-success me-2">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="small text-muted">
                                        <span id="piezasDefectuosas" class="font-weight-bold text-success">0</span> piezas defectuosas
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stat-icon-large text-success">
                                    <i class="fas fa-search"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registros con Retrabajo -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    En Retrabajo
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="registrosRetrabajo">0</span>
                                </div>
                                <div class="mt-2 d-flex align-items-center">
                                    <div class="stat-icon-small text-warning me-2">
                                        <i class="fas fa-redo"></i>
                                    </div>
                                    <div class="small text-muted">
                                        <span id="piezasRetrabajo" class="font-weight-bold text-warning">0</span> piezas en retrabajo
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stat-icon-large text-warning">
                                    <i class="fas fa-tools"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registros en Cuarentena -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    En Cuarentena
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="registrosCuarentena">0</span>
                                </div>
                                <div class="mt-2 d-flex align-items-center">
                                    <div class="stat-icon-small text-danger me-2">
                                        <i class="fas fa-pause"></i>
                                    </div>
                                    <div class="small text-muted">
                                        <span id="piezasCuarentena" class="font-weight-bold text-danger">0</span> piezas en cuarentena
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stat-icon-large text-danger">
                                    <i class="fas fa-ban"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de Métricas Adicionales -->
        <div class="row mb-4">
            <!-- Porcentaje de Piezas Defectuosas -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    % Piezas Defectuosas
                                </div>
                                <div class="row no-gutters align-items-center">
                                    <div class="col-auto">
                                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                            <span id="porcentajeDefectuosas">0</span>%
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="progress progress-sm mr-2">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 id="progressDefectuosas" style="width: 0%" 
                                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stat-icon-large text-info">
                                    <i class="fas fa-percentage"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Promedio de Piezas por Hallazgo -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-secondary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                    Promedio por Hallazgo
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="promedioPiezas">0</span>
                                </div>
                                <div class="mt-2">
                                    <div class="small text-muted">
                                        <i class="fas fa-calculator me-1"></i>
                                        piezas por registro
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stat-icon-large text-secondary">
                                    <i class="fas fa-calculator"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Eficiencia (Piezas OK) -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Piezas Sin Defectos
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="piezasOK">0</span>
                                </div>
                                <div class="mt-2">
                                    <div class="small text-success font-weight-bold">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <span id="porcentajeOK">0</span>% eficiencia
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stat-icon-large text-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tendencia -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Tendencia Semanal
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="tendenciaValor">0</span>
                                </div>
                                <div class="mt-2">
                                    <div class="small" id="tendenciaTexto">
                                        <i class="fas fa-chart-line me-1"></i>
                                        <span>Sin datos</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="stat-icon-large text-warning">
                                    <i class="fas fa-chart-line" id="tendenciaIcon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Meta de Scrap Mensual movida a scrap_dashboard.php -->

        <!-- Gráficos de Análisis -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>
                        Análisis de Registros y Piezas Afectadas por Categorías
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Áreas con más registros -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    Áreas: Piezas Afectadas (Registros)
                                    <small class="text-muted ms-2">(Ordenado por total de piezas)</small>
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartAreas"></canvas>
                                </div>
                            </div>
                            
                            <!-- Modelos con más registros -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-cog me-2"></i>
                                    Modelos: Impacto por Piezas Defectuosas
                                    <small class="text-muted ms-2">(Ordenado por total de piezas)</small>
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartModelos"></canvas>
                                </div>
                            </div>
                            
                            <!-- Usuarios con más registros reportados -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-users me-2"></i>
                                    Usuarios: Eficiencia en Identificación de Piezas
                                    <small class="text-muted ms-2">(Ordenado por piezas identificadas)</small>
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartUsuarios"></canvas>
                                </div>
                            </div>
                            
                            <!-- No. de parte con más registros -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-barcode me-2"></i>
                                    Partes: Impacto Real por No. de Parte
                                    <small class="text-muted ms-2">(Ordenado por piezas afectadas)</small>
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartNoParte"></canvas>
                                </div>
                            </div>
                            
                            <!-- Defectos más reportados -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Defectos: Impacto por Cantidad de Piezas
                                    <small class="text-muted ms-2">(Ordenado por piezas afectadas)</small>
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartDefectos"></canvas>
                                </div>
                            </div>
                            
                            <!-- Gráficas de scrap movidas a scrap_dashboard.php -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Sección de Gestión de Registros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-list-alt me-2"></i>
                        Gestión de Registros
                    </div>
                    <div class="card-body">
                        <!-- Filtros Mejorados para Gestión de Hallazgos -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-filter me-2"></i>
                                Filtros de Búsqueda
                            </h6>
                            


                            <!-- Filtros detallados -->
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-day me-1"></i>
                                        Fecha Inicio
                                    </label>
                                    <input type="date" class="form-control" id="filtroTablaFechaInicio">
                                    <small class="text-muted">Fecha de inicio del rango</small>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        Fecha Fin
                                    </label>
                                    <input type="date" class="form-control" id="filtroTablaFechaFin">
                                    <small class="text-muted">Fecha de fin del rango</small>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Área
                                    </label>
                                    <select class="form-select" id="filtroTablaArea">
                                        <option value="">🏭 Todas las áreas</option>
                                        <option value="Plasma">⚡ Plasma</option>
                                        <option value="Prensas">🔧 Prensas</option>
                                        <option value="Beam welder">🔥 Beam welder</option>
                                        <option value="Roladora">🎯 Roladora</option>
                                        <option value="Sierras">🪚 Sierras</option>
                                        <option value="Fresadora">⚙️ Fresadora</option>
                                        <option value="Vulcanizadora">🔴 Vulcanizadora</option>
                                        <option value="soldadura">🔗 Soldadura</option>
                                        <option value="ejes">🚛 Ejes</option>
                                        <option value="Diseño">📐 Diseño</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">
                                        <i class="fas fa-redo me-1"></i>
                                        Retrabajo
                                    </label>
                                    <select class="form-select" id="filtroTablaRetrabajo">
                                        <option value="">📋 Todos los estados</option>
                                        <option value="Si">⚠️ Con retrabajo</option>
                                        <option value="No">✅ Sin retrabajo</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">
                                        <i class="fas fa-cog me-1"></i>
                                        Modelo
                                    </label>
                                    <input type="text" class="form-control" id="filtroTablaModelo" placeholder="🔍 Buscar modelo...">
                                    <small class="text-muted">Escriba el nombre del modelo</small>
                                </div>
                                <div class="col-md-2 d-flex flex-column">
                                    <label class="form-label">
                                        <i class="fas fa-search me-1"></i>
                                        Acciones
                                    </label>
                                    <button class="btn btn-primary mb-2" onclick="aplicarFiltrosTabla()">
                                        <i class="fas fa-search me-1"></i>
                                        Buscar
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">
                                        <i class="fas fa-eraser me-1"></i>
                                        Limpiar
                                    </button>
                                </div>
                            </div>

                            <!-- Información de filtros aplicados -->
                            <div class="mt-3">
                                <div class="alert alert-info d-none" id="filtrosAplicadosInfo">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Filtros aplicados:</strong>
                                    <span id="resumenFiltros">Ninguno</span>
                                    <button class="btn btn-sm btn-outline-info ms-2" onclick="limpiarFiltros()">
                                        <i class="fas fa-times me-1"></i>
                                        Quitar filtros
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Registros Activos -->
                        <div class="table-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-table me-2"></i>
                                    Registros 
                                    <span class="badge bg-primary ms-2" id="contadorActivos">0</span>
                                </h6>
                                <button class="btn btn-outline-secondary btn-sm" onclick="exportarTabla('activos')">
                                    <i class="fas fa-download me-1"></i>
                                    Exportar
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaRegistrosActivos">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>No.</th>
                                            <th>Fecha</th>
                                            <th>Área</th>
                                            <th>Modelo</th>
                                            <th>No. Parte</th>
                                            <th>Job Order</th>
                                            <th>Usuario</th>
                                            <th>Cantidad</th>
                                            <th>Retrabajo</th>
                                            <th>Defectos</th>
                                            <th>Evidencias</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRegistrosActivosBody">
                                        <tr>
                                            <td colspan="12" class="text-center">
                                                <div class="loading-table">
                                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                                    Cargando registros...
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Separador -->
                        <hr class="my-5">

                        <!-- Tabla de Registros en Cuarentena -->
                        <div class="table-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Registros en Cuarentena
                                    <span class="badge bg-warning text-dark ms-2" id="contadorCuarentena">0</span>
                                </h6>
                                <button class="btn btn-outline-warning btn-sm" onclick="exportarTabla('cuarentena')">
                                    <i class="fas fa-download me-1"></i>
                                    Exportar
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaRegistrosCuarentena">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>No.</th>
                                            <th>Fecha</th>
                                            <th>Área</th>
                                            <th>Modelo</th>
                                            <th>No. Parte</th>
                                            <th>Job Order</th>
                                            <th>Usuario</th>
                                            <th>Cantidad</th>
                                            <th>Defectos</th>
                                            <th>Evidencias</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRegistrosCuarentenaBody">
                                        <tr>
                                            <td colspan="11" class="text-center">
                                                <div class="loading-table">
                                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                                    Cargando registros en cuarentena...
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Separador -->
                        <hr class="my-5">

                        <!-- Tabla de Registros en Scrap -->
                        <div class="table-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-danger">
                                    <i class="fas fa-trash me-2"></i>
                                    Registros en Scrap
                                    <span class="badge bg-danger ms-2" id="contadorScrap">0</span>
                                </h6>
                                <button class="btn btn-outline-danger btn-sm" onclick="exportarTabla('scrap')">
                                    <i class="fas fa-download me-1"></i>
                                    Exportar
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover" id="tablaRegistrosScrap">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>No.</th>
                                            <th>Fecha</th>
                                            <th>Área</th>
                                            <th>Modelo</th>
                                            <th>No. Parte</th>
                                            <th>Job Order</th>
                                            <th>Usuario</th>
                                            <th>Cantidad</th>
                                            <th>Defectos</th>
                                            <th>Evidencias</th>
                                            <th>Fecha Scrap</th>
                                            <th>Valor Scrap</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRegistrosScrapBody">
                                        <tr>
                                            <td colspan="12" class="text-center">
                                                <div class="loading-table">
                                                    <i class="fas fa-spinner fa-spin me-2"></i>
                                                    Cargando registros en scrap...
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i>
                        Resumen del Sistema
                    </div>
                    <div class="card-body">
                        <div class="loading" id="loadingInfo">
                            <div class="spinner"></div>
                            <p>Cargando información...</p>
                        </div>
                        <div id="infoContent" style="display: none;">
                            <p class="mb-2">
                                <strong>Filtros aplicados:</strong> 
                                <span id="filtrosAplicados">Mostrando todos los registros</span>
                            </p>
                            <p class="mb-0">
                                <strong>Última actualización:</strong> 
                                <span id="ultimaActualizacion">-</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver evidencias -->
    <div class="modal fade" id="evidenciaModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-images me-2"></i>
                        Evidencias del Hallazgo #<span id="evidenciaHallazgoId">-</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="evidenciaModalBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando evidencias...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver defectos -->
    <div class="modal fade" id="defectosModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Defectos del Hallazgo #<span id="defectosHallazgoId">-</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="defectosModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando defectos...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver observaciones -->
    <div class="modal fade" id="observacionesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-comment-alt me-2"></i>
                        Observaciones del Hallazgo #<span id="observacionesHallazgoId">-</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="observacionesModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando observaciones...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Cerrar Hallazgo -->
    <div class="modal fade" id="cerrarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-lock me-2"></i>
                        Cerrar Hallazgo #<span id="cerrarHallazgoId">-</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cerrarFecha" class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i>
                            Fecha de cierre
                        </label>
                        <input type="datetime-local" class="form-control" id="cerrarFecha">
                        <small class="text-muted">Se llena automáticamente, pero puedes editarla.</small>
                    </div>
                    <div class="mb-3">
                        <label for="cerrarSolucion" class="form-label">
                            <i class="fas fa-comment-dots me-1"></i>
                            Solución
                        </label>
                        <textarea class="form-control" id="cerrarSolucion" rows="4" placeholder="Describe la solución aplicada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmarCierre">
                        <i class="fas fa-lock me-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para cambios de estado -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-question-circle me-2"></i>
                        Confirmar Acción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">¿Estás seguro de realizar esta acción?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmButton">
                        <i class="fas fa-check me-1"></i>
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Scrap -->
    <div class="modal fade" id="scrapModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>
                        Enviar a Scrap
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Esta acción cambiará el estado del hallazgo a <strong>SCRAP</strong> y registrará la información para control de pérdidas.
                    </div>
                    
                    <form id="scrapForm">
                        <input type="hidden" id="scrapHallazgoId" name="hallazgo_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scrapModelo" class="form-label">
                                    <i class="fas fa-cog me-1"></i>
                                    Modelo *
                                </label>
                                <input type="text" class="form-control" id="scrapModelo" name="modelo" placeholder="Ingrese el modelo" required>
                                <small class="text-muted">Escriba el modelo del producto</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scrapNoParte" class="form-label">
                                    <i class="fas fa-puzzle-piece me-1"></i>
                                    No. Parte *
                                </label>
                                <input type="text" class="form-control" id="scrapNoParte" name="no_parte" placeholder="Ingrese el número de parte" required>
                                <small class="text-muted">Escriba el número de parte</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scrapNoEnsamble" class="form-label">
                                    <i class="fas fa-layer-group me-1"></i>
                                    No. Ensamble *
                                </label>
                                <input type="text" class="form-control" id="scrapNoEnsamble" name="no_ensamble" placeholder="Ingrese el número de ensamble">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scrapPrecio" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>
                                    Precio * (USD)
                                </label>
                                <input type="number" class="form-control" id="scrapPrecio" name="precio" step="0.01" min="0" placeholder="0.00" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scrapFecha" class="form-label">
                                    <i class="fas fa-calendar-day me-1"></i>
                                    Fecha de Scrap
                                </label>
                                <input type="date" class="form-control" id="scrapFecha" name="fecha_scrap">
                                <small class="text-muted">Por defecto hoy. Puedes modificarla.</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="scrapObservaciones" class="form-label">
                                <i class="fas fa-comment me-1"></i>
                                Observaciones
                            </label>
                            <select class="form-select" id="scrapObservaciones" name="observaciones" required>
                                <option value="" selected disabled>Seleccione la observación</option>
                                <option value="Cambio de Diseño - Error en el dibujo">Cambio de Diseño - Error en el dibujo</option>
                                <option value="Mal corte">Mal corte</option>
                                <option value="Mal doblez">Mal doblez</option>
                                <option value="Mal ensamble">Mal ensamble</option>
                                <option value="Pandeada">Pandeada</option>
                            </select>
                            <small class="text-muted">Selecciona el motivo por el que se enviará a scrap</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmarScrap">
                        <i class="fas fa-trash me-1"></i>
                        Confirmar Scrap
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón de refresh flotante -->
    <button class="refresh-btn" onclick="refreshData()" title="Actualizar datos">
        <i class="fas fa-sync-alt"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script src="assets/js/admin-dashboard-clean.js"></script>
    <script>
        function fmtMoney(value) {
            const n = Number(value || 0);
            return n.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
        }

        async function loadScrapGoal(year, month) {
            try {
                const now = new Date();
                const y = year || now.getFullYear();
                const m = month || (now.getMonth() + 1);
                const res = await fetch(`includes/scrap_goal_api.php?year=${y}&month=${m}`, { credentials: 'same-origin' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Error obteniendo meta');
                const d = data.data;
                const yearEl = document.getElementById('scrapGoalYear');
                const monthEl = document.getElementById('scrapGoalMonth');
                if (yearEl) yearEl.textContent = d.year;
                if (monthEl) monthEl.textContent = String(d.month).padStart(2, '0');
                const goalLabel = document.getElementById('scrapGoalLabel');
                const accLabel = document.getElementById('scrapAccumulatedLabel');
                const remLabel = document.getElementById('scrapRemainingLabel');
                if (goalLabel) goalLabel.textContent = fmtMoney(d.goal);
                if (accLabel) accLabel.textContent = fmtMoney(d.month_total);
                if (remLabel) remLabel.textContent = fmtMoney(d.remaining);
                const bar = document.getElementById('scrapGoalProgress');
                if (bar) {
                    bar.style.width = `${d.percent}%`;
                    bar.setAttribute('aria-valuenow', d.percent);
                }
                const input = document.getElementById('scrapGoalInput');
                if (input) input.value = d.goal || '';
                const badge = document.getElementById('scrapExceededBadge');
                if (badge) badge.style.display = d.exceeded ? 'block' : 'none';
            } catch (e) {
                console.error('Error loadScrapGoal:', e);
            }
        }

        async function saveScrapGoal() {
            const now = new Date();
            const y = document.getElementById('scrapGoalYear')?.textContent || now.getFullYear();
            const m = document.getElementById('scrapGoalMonth')?.textContent || (now.getMonth() + 1);
            const amount = parseFloat(document.getElementById('scrapGoalInput')?.value || '0');
            try {
                const form = new FormData();
                form.append('year', y);
                form.append('month', m);
                form.append('amount', amount);
                const res = await fetch('includes/scrap_goal_api.php', { method: 'POST', body: form, credentials: 'same-origin' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || '');
                await loadScrapGoal(y, m);
            } catch (e) {
                console.error('Error saveScrapGoal:', e);
                alert('No se pudo guardar la meta: ' + e.message);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadScrapGoal();
        });
    </script>
</body>
</html>
