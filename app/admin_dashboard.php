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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-dashboard.css" rel="stylesheet">
    <style>
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
                Monitoreo y análisis de hallazgos de calidad en tiempo real
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

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-number" id="totalRegistros">0</div>
                    <div class="stat-label">Total de Registros</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-number" id="registrosConHallazgos">0</div>
                    <div class="stat-label">Registros con Hallazgos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-number" id="registrosRetrabajo">0</div>
                    <div class="stat-label">Registros con Retrabajo</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="stat-number" id="registrosCuarentena">0</div>
                    <div class="stat-label">Registros en Cuarentena</div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Análisis -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>
                        Análisis de Hallazgos por Categorías
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Áreas con más hallazgos -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    Áreas con Más Hallazgos
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartAreas"></canvas>
                                </div>
                            </div>
                            
                            <!-- Modelos con más hallazgos -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-cog me-2"></i>
                                    Modelos con Más Hallazgos
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartModelos"></canvas>
                                </div>
                            </div>
                            
                            <!-- Usuarios con más hallazgos reportados -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-users me-2"></i>
                                    Usuarios con Más Hallazgos Reportados
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartUsuarios"></canvas>
                                </div>
                            </div>
                            
                            <!-- No. de parte con más hallazgos -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-barcode me-2"></i>
                                    No. de Parte con Más Hallazgos
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartNoParte"></canvas>
                                </div>
                            </div>
                            
                            <!-- Defectos más reportados -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Defectos Más Reportados
                                </h6>
                                <div class="chart-container">
                                    <canvas id="chartDefectos"></canvas>
                                </div>
                            </div>
                            
                            <!-- Dinero perdido en scrap -->
                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-dollar-sign me-2 text-danger"></i>
                                    Dinero Perdido en Scrap
                                    <small class="text-muted ms-2" id="totalDineroPerdido">$0.00</small>
                                </h6>
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="chartScrap"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Gestión de Hallazgos -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-list-alt me-2"></i>
                        Gestión de Hallazgos
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
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Área</th>
                                            <th>Modelo</th>
                                            <th>No. Parte</th>
                                            <th>Job Order</th>
                                            <th>Usuario</th>
                                            <th>Retrabajo</th>
                                            <th>Defectos</th>
                                            <th>Evidencias</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRegistrosActivosBody">
                                        <tr>
                                            <td colspan="11" class="text-center">
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
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Área</th>
                                            <th>Modelo</th>
                                            <th>No. Parte</th>
                                            <th>Job Order</th>
                                            <th>Usuario</th>
                                            <th>Defectos</th>
                                            <th>Evidencias</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRegistrosCuarentenaBody">
                                        <tr>
                                            <td colspan="10" class="text-center">
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
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Área</th>
                                            <th>Modelo</th>
                                            <th>No. Parte</th>
                                            <th>Job Order</th>
                                            <th>Usuario</th>
                                            <th>Defectos</th>
                                            <th>Evidencias</th>
                                            <th>Fecha Scrap</th>
                                            <th>Valor Scrap</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaRegistrosScrapBody">
                                        <tr>
                                            <td colspan="11" class="text-center">
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
                        
                        <div class="mb-3">
                            <label for="scrapObservaciones" class="form-label">
                                <i class="fas fa-comment me-1"></i>
                                Observaciones
                            </label>
                            <textarea class="form-control" id="scrapObservaciones" name="observaciones" rows="3" placeholder="Información adicional sobre el scrap..."></textarea>
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
    <script src="assets/js/admin-dashboard-clean.js"></script>
</body>
</html>
