<?php
session_start();
require_once 'includes/db_config.php';

// Solo usuarios encargados
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
    <title>Histórico de Hallazgos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-dashboard.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-chart-line me-2"></i>
                Sistema de Calidad - Histórico
            </a>
            <div class="navbar-nav ms-auto">
                <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-home me-1"></i>
                    Dashboard
                </a>
                <span class="navbar-text me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo htmlspecialchars($user_name); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-history me-2"></i>
                Histórico de Hallazgos
            </h1>
            <p class="page-subtitle">Consulta y exporta todos los registros con filtros avanzados</p>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Estado</label>
                        <select class="form-select" id="fEstado">
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                            <option value="cuarentena">Cuarentena</option>
                            <option value="scrap">Scrap</option>
                            <option value="cerrada">Cerrada</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha inicio</label>
                        <input type="date" class="form-control" id="fFechaInicio">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha fin</label>
                        <input type="date" class="form-control" id="fFechaFin">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Área</label>
                        <select class="form-select" id="fArea">
                            <option value="">Todas</option>
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
                    <div class="col-md-2">
                        <label class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="fModelo" placeholder="Buscar modelo">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="fUsuario" placeholder="Nombre o ID">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Retrabajo</label>
                        <select class="form-select" id="fRetrabajo">
                            <option value="">Todos</option>
                            <option value="Si">Si</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" id="btnBuscar">
                            <i class="fas fa-search me-1"></i>Buscar
                        </button>
                        <button class="btn btn-outline-secondary" id="btnLimpiar">
                            <i class="fas fa-eraser me-1"></i>Limpiar
                        </button>
                        <button class="btn btn-success" id="btnExportar">
                            <i class="fas fa-file-excel me-1"></i>Exportar Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Área</th>
                                <th>Modelo</th>
                                <th>No. Parte</th>
                                <th>Job Order</th>
                                <th>Usuario</th>
                                <th>Retrabajo</th>
                                <th>Piezas</th>
                                <th>Defectos</th>
                                <th>Evidencias</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="historicoBody">
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">Use los filtros y presione Buscar</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="small text-muted" id="historicoResumen"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <!-- Modal Solución -->
    <div class="modal fade" id="solucionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-lightbulb me-2"></i>Solución Hallazgo #<span id="solucionHallazgoId">-</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="solucionModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
                        <p class="mt-2">Cargando solución...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-1"></i>Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/historico.js"></script>
</body>
</html>
