<?php
session_start();
require_once 'includes/db_config.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$user_name = $_SESSION['usuario']['nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analítica de Scrap - Sistema de Calidad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-dashboard.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-trash me-2"></i>
                Analítica de Scrap
            </a>
            <div class="navbar-nav ms-auto">
                <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="fas fa-tachometer-alt me-1"></i>
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
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h1 class="page-title">
                <i class="fas fa-chart-bar me-2"></i>
                Reportes y Metas de Scrap
            </h1>
            <div class="d-flex align-items-center flex-wrap gap-3">
                <p class="page-subtitle mb-0">Consulta la meta mensual y las tendencias de scrap.</p>
                <button id="btnRefreshScrap" class="btn btn-outline-primary btn-sm" title="Actualizar">
                    <i class="fas fa-rotate-right me-1"></i>Actualizar
                </button>
            </div>
        </div>

        <!-- Meta de Scrap Mensual -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card stat-card-modern border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Meta de Scrap Mensual
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <span id="scrapGoalLabel">$0.00</span>
                                </div>
                            </div>
                            <div class="stat-icon-large text-danger">
                                <i class="fas fa-bullseye"></i>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted d-flex align-items-center gap-2">
                            <label for="scrapGoalPicker" class="mb-0">Mes</label>
                            <input type="month" id="scrapGoalPicker" class="form-control form-control-sm" style="width: 170px;">
                            <span class="ms-2">Selecciona un mes para consultar/guardar la meta</span>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between">
                                <span class="small">Acumulado</span>
                                <span class="small fw-bold" id="scrapAccumulatedLabel">$0.00</span>
                            </div>
                            <div class="progress progress-sm mt-1">
                                <div id="scrapGoalProgress" class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span class="small">Restante</span>
                                <span class="small" id="scrapRemainingLabel">$0.00</span>
                            </div>
                            <div class="mt-2" id="scrapExceededBadge" style="display:none;">
                                <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i> Meta excedida</span>
                            </div>
                            <div class="mt-2">
                                <span class="small">Estado:</span>
                                <span id="scrapGoalStatus" class="badge bg-secondary ms-1">Sin meta</span>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2 align-items-end">
                            <div class="flex-grow-1">
                                <label for="scrapGoalInput" class="form-label small mb-1">Establecer meta ($)</label>
                                <input type="number" id="scrapGoalInput" class="form-control form-control-sm" min="0" step="0.01" placeholder="2000">
                            </div>
                            <button id="scrapGoalSaveBtn" class="btn btn-danger btn-sm" onclick="saveScrapGoal()">
                                <i class="fas fa-save me-1"></i>Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficas de Scrap (estilo tarjeta como en el dashboard) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>
                        Análisis de Scrap y Estaciones
                    </div>
                    <div class="card-body">
                        <div class="row">
                <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-dollar-sign me-2 text-danger"></i>
                                    Dinero Perdido en Scrap
                    <small class="text-muted ms-2">(Tendencia mensual)</small>
                                    <small class="text-muted ms-2" id="totalDineroPerdido">$0.00</small>
                                </h6>
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="chartScrap"></canvas>
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-industry me-2 text-danger"></i>
                                    Estaciones con Más Dinero Perdido por Scrap
                                    <small class="text-muted ms-2">(Agrupadas por área)</small>
                                    <small class="text-muted ms-2" id="totalEstacionesPerdido">$0.00</small>
                                </h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <label for="estacionesMonth" class="form-label mb-0 small text-muted">Mes</label>
                                    <input type="month" id="estacionesMonth" class="form-control form-control-sm" style="width: 170px;">
                                </div>
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="chartEstacionesScrap"></canvas>
                                </div>
                                <div class="mt-3">
                                    <div class="row" id="resumenAreasScrap"></div>
                                </div>
                            </div>

                            <!-- Gráfica semanal con filtro propio de mes -->
                            <div class="col-md-12 mb-4">
                                <h6 class="chart-title">
                                    <i class="fas fa-calendar-week me-2 text-danger"></i>
                                    Dinero Perdido en Scrap por Semanas
                                    <small class="text-muted ms-2">(Selecciona un mes para ver sus semanas)</small>
                                </h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <label for="scrapWeekMonth" class="form-label mb-0 small text-muted">Mes</label>
                                    <input type="month" id="scrapWeekMonth" class="form-control form-control-sm" style="width: 170px;">
                                </div>
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="chartScrapWeekly"></canvas>
                                </div>
                            </div>

                            <div class="col-md-12 mb-2">
                                <h6 class="chart-title">
                                    <i class="fas fa-barcode me-2 text-danger"></i>
                                    Números de Parte con Mayor Scrap
                                    <small class="text-muted ms-2">(incluye área)</small>
                                    <small class="text-muted ms-2" id="totalPartesPerdido">$0.00</small>
                                </h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <label for="partesMonth" class="form-label mb-0 small text-muted">Mes</label>
                                    <input type="month" id="partesMonth" class="form-control form-control-sm" style="width: 170px;">
                                </div>
                                <div class="chart-container" style="height: 360px;">
                                    <canvas id="chartPartesScrap"></canvas>
                                </div>
                            </div>

                            <div class="col-md-12 mb-2">
                                <h6 class="chart-title">
                                    <i class="fas fa-boxes-stacked me-2 text-primary"></i>
                                    Números de Parte por Cantidad de Piezas en Scrap
                                    <small class="text-muted ms-2">(incluye área)</small>
                                    <span class="badge bg-primary ms-2" id="totalPartesPiezas" style="font-size: 0.9rem;">0 piezas</span>
                                </h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <label for="partesPiezasMonth" class="form-label mb-0 small text-muted">Mes</label>
                                    <input type="month" id="partesPiezasMonth" class="form-control form-control-sm" style="width: 170px;">
                                </div>
                                <div class="chart-container" style="height: 360px;">
                                    <canvas id="chartPartesPiezasScrap"></canvas>
                                </div>
                            </div>

                            <div class="col-md-12 mb-2">
                                <h6 class="chart-title">
                                    <i class="fas fa-comment-dots me-2 text-secondary"></i>
                                    Observaciones más frecuentes en registros de Scrap
                                    <small class="text-muted ms-2">Top 5</small>
                                    <span class="badge bg-secondary ms-2" id="totalObsScrap" style="font-size: 0.9rem;">0 piezas</span>
                                </h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <label for="obsMonth" class="form-label mb-0 small text-muted">Mes</label>
                                    <input type="month" id="obsMonth" class="form-control form-control-sm" style="width: 170px;">
                                </div>
                                <div class="chart-container" style="height: 320px;">
                                    <canvas id="chartObsScrap"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                const picker = document.getElementById('scrapGoalPicker');
                let y = year, m = month;
                if (picker && picker.value) {
                    const [yy, mm] = picker.value.split('-');
                    y = y || parseInt(yy, 10);
                    m = m || parseInt(mm, 10);
                }
                y = y || now.getFullYear();
                m = m || (now.getMonth() + 1);
                const res = await fetch(`includes/scrap_goal_api.php?year=${y}&month=${m}`, { credentials: 'same-origin' });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Error obteniendo meta');
                const d = data.data;
                // Sin spans de año/mes; usamos el picker como fuente de verdad
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

                const status = document.getElementById('scrapGoalStatus');
                if (status) {
                    const met = Number(d.month_total) >= Number(d.goal) && Number(d.goal) > 0;
                    if (Number(d.goal) <= 0) {
                        status.textContent = 'Sin meta';
                        status.className = 'badge bg-secondary ms-1';
                    } else if (met) {
                        status.textContent = 'Cumplida';
                        status.className = 'badge bg-success ms-1';
                    } else {
                        status.textContent = 'No cumplida';
                        status.className = 'badge bg-secondary ms-1';
                    }
                }
            } catch (e) {
                console.error('Error loadScrapGoal:', e);
            }
        }

        async function saveScrapGoal() {
            const now = new Date();
            const picker = document.getElementById('scrapGoalPicker');
            let y = now.getFullYear();
            let m = now.getMonth() + 1;
            if (picker && picker.value) {
                const [yy, mm] = picker.value.split('-');
                y = parseInt(yy, 10);
                m = parseInt(mm, 10);
            }
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

        function refreshScrap() {
            loadScrapGoal();
            ChartManager.loadScrapChart();
            ChartManager.loadEstacionesScrapChart();
            ChartManager.loadPartesScrapChart();
            ChartManager.loadPartesPiezasScrapChart();
            ChartManager.loadScrapWeeklyChart();
            const ts = new Date().toLocaleString();
            let tsEl = document.getElementById('scrapLastUpdated');
            if (!tsEl) {
                tsEl = document.createElement('div');
                tsEl.id = 'scrapLastUpdated';
                tsEl.className = 'text-muted small mt-2';
                document.querySelector('.page-header')?.appendChild(tsEl);
            }
            tsEl.textContent = `Última actualización: ${ts}`;
        }

        function setActiveToggle(groupIds, activeId) {
            groupIds.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                if (id === activeId) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Cargar meta de scrap y gráficas (sin filtros de fecha/área por ahora)
            // Preseleccionar mes actual para la gráfica semanal
            const weekMonthEl = document.getElementById('scrapWeekMonth');
            if (weekMonthEl && !weekMonthEl.value) {
                const now = new Date();
                const yyyy = now.getFullYear();
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                weekMonthEl.value = `${yyyy}-${mm}`;
            }
            // Preseleccionar mes actual en el picker si no tiene valor
            const goalPicker = document.getElementById('scrapGoalPicker');
            if (goalPicker && !goalPicker.value) {
                const now = new Date();
                const yyyy = now.getFullYear();
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                goalPicker.value = `${yyyy}-${mm}`;
            }

            refreshScrap();
            document.getElementById('btnRefreshScrap')?.addEventListener('click', refreshScrap);

            // Cambiar de mes en meta de scrap
            document.getElementById('scrapGoalPicker')?.addEventListener('change', () => {
                const picker = document.getElementById('scrapGoalPicker');
                if (picker && picker.value) {
                    const [y, m] = picker.value.split('-');
                    loadScrapGoal(parseInt(y, 10), parseInt(m, 10));
                    // También refrescar gráficos al cambiar de mes de meta si lo deseas
                    // ChartManager.loadScrapChart();
                }
            });

            // Cargar semanal al cambiar el mes de esa sección
            document.getElementById('scrapWeekMonth')?.addEventListener('change', () => {
                ChartManager.loadScrapWeeklyChart();
            });

            // Selector mes: Estaciones
            document.getElementById('estacionesMonth')?.addEventListener('change', () => {
                ChartManager.loadEstacionesScrapChart();
            });

            // Selector mes: Partes
            document.getElementById('partesMonth')?.addEventListener('change', () => {
                ChartManager.loadPartesScrapChart();
            });

            // Selector mes: Partes por piezas
            document.getElementById('partesPiezasMonth')?.addEventListener('change', () => {
                ChartManager.loadPartesPiezasScrapChart();
            });

            // Preseleccionar mes actual para partes por piezas si no tiene valor
            const partesPiezasEl = document.getElementById('partesPiezasMonth');
            if (partesPiezasEl && !partesPiezasEl.value) {
                const now = new Date();
                const yyyy = now.getFullYear();
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                partesPiezasEl.value = `${yyyy}-${mm}`;
            }

            // Observaciones: set default month and listener
            const obsEl = document.getElementById('obsMonth');
            if (obsEl && !obsEl.value) {
                const now = new Date();
                const yyyy = now.getFullYear();
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                obsEl.value = `${yyyy}-${mm}`;
            }
            document.getElementById('obsMonth')?.addEventListener('change', () => {
                ChartManager.loadScrapObservacionesChart();
            });

        });
    </script>
</body>
</html>
