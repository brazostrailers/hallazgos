/**
 * Admin Dashboard JavaScript - Sistema de Calidad
 * Gestión de Hallazgos - Versión Refactorizada
 * 
 * @version 2.0
 * @author Sistema de Calidad
 */

/**
 * ============================================================================
 * CONFIGURACIÓN Y VARIABLES GLOBALES
 * ============================================================================
 */

// Configuración del sistema
const AppConfig = {
    AUTO_REFRESH_INTERVAL: 30000,
    CHART_COLORS: [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
        '#FF9F40', '#C9CBCF', '#4BC0C0', '#FF6384', '#36A2EB'
    ],
    DATE_FORMAT_OPTIONS: {
        day: '2-digit',
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }
};

// Estado global de la aplicación
const AppState = {
    currentFilters: {},
    hallazgosData: [],
    cuarentenaData: [],
    scrapData: [],
    charts: {
        area: null,
        modelo: null,
        usuario: null,
        noParte: null,
        defectos: null,
        scrap: null,
        trend: null
    },
    isLoading: false
};

/**
 * ============================================================================
 * UTILIDADES GENERALES
 * ============================================================================
 */

const Utils = {
    /**
     * Formatear fecha para mostrar
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', AppConfig.DATE_FORMAT_OPTIONS);
    },

    /**
     * Obtener fecha de hoy en formato YYYY-MM-DD
     */
    getTodayString() {
        return new Date().toISOString().split('T')[0];
    },

    /**
     * Calcular fecha n días atrás
     */
    getDaysAgo(days) {
        const date = new Date();
        date.setDate(date.getDate() - days);
        return date.toISOString().split('T')[0];
    },

    /**
     * Formatear tamaño de archivo
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    /**
     * Validar si un elemento existe en el DOM
     */
    elementExists(id) {
        return document.getElementById(id) !== null;
    },

    /**
     * Obtener elemento por ID de forma segura
     */
    getElement(id) {
        const element = document.getElementById(id);
        if (!element) {
            console.warn(`Elemento con ID '${id}' no encontrado`);
        }
        return element;
    },

    /**
     * Mostrar/ocultar elemento
     */
    toggleElement(id, show) {
        const element = this.getElement(id);
        if (element) {
            element.style.display = show ? 'block' : 'none';
        }
    },

    /**
     * Animar número en un elemento
     */
    animateNumber(elementId, targetNumber) {
        const element = this.getElement(elementId);
        if (!element) return;

        const startNumber = parseInt(element.textContent) || 0;
        const duration = 1000;
        const startTime = performance.now();

        const updateNumber = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const currentNumber = Math.round(startNumber + (targetNumber - startNumber) * progress);
            
            element.textContent = currentNumber.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            }
        };

        requestAnimationFrame(updateNumber);
    }
};

/**
 * ============================================================================
 * GESTIÓN DE NOTIFICACIONES
 * ============================================================================
 */

const NotificationManager = {
    /**
     * Mostrar notificación toast
     */
    show(message, type = 'info') {
        const bgClass = {
            'error': 'bg-danger',
            'success': 'bg-success',
            'warning': 'bg-warning',
            'info': 'bg-info'
        }[type] || 'bg-info';

        const toastHtml = `
            <div class="toast align-items-center text-white ${bgClass} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        container.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = container.lastElementChild;
        const toast = new bootstrap.Toast(toastElement);
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    },

    /**
     * Mostrar error en formato alert
     */
    showError(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const mainContainer = document.querySelector('.main-container');
        const pageHeader = document.querySelector('.page-header');
        if (mainContainer && pageHeader) {
            mainContainer.insertBefore(alertDiv, pageHeader.nextSibling);
        }

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
};

/**
 * ============================================================================
 * GESTIÓN DE FECHAS Y FILTROS
 * ============================================================================
 */

const DateManager = {
    /**
     * Establecer fechas por defecto (últimos 30 días)
     */
    setDefaultDates() {
        const fechaFin = Utils.getElement('fechaFin');
        const fechaInicio = Utils.getElement('fechaInicio');
        
        if (fechaFin) fechaFin.value = Utils.getTodayString();
        if (fechaInicio) fechaInicio.value = Utils.getDaysAgo(30);
    },

    /**
     * Validar rango de fechas
     */
    validateDateRange() {
        const fechaInicio = Utils.getElement('fechaInicio')?.value;
        const fechaFin = Utils.getElement('fechaFin')?.value;

        if (fechaInicio && fechaFin) {
            if (new Date(fechaInicio) > new Date(fechaFin)) {
                NotificationManager.show('La fecha de inicio no puede ser mayor que la fecha de fin', 'error');
                return false;
            }

            // Verificar que el rango no sea mayor a 1 año
            const diffTime = Math.abs(new Date(fechaFin) - new Date(fechaInicio));
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 365) {
                NotificationManager.show('El rango de fechas no puede ser mayor a 1 año', 'error');
                return false;
            }
        }

        return true;
    },

    /**
     * Establecer fecha máxima como hoy
     */
    setMaxDateToToday() {
        const today = Utils.getTodayString();
        const fechaInicio = Utils.getElement('fechaInicio');
        const fechaFin = Utils.getElement('fechaFin');
        
        if (fechaInicio) fechaInicio.max = today;
        if (fechaFin) fechaFin.max = today;
    },

    /**
     * Limpiar fechas
     */
    clearDates() {
        const fechaInicio = Utils.getElement('fechaInicio');
        const fechaFin = Utils.getElement('fechaFin');
        
        if (fechaInicio) fechaInicio.value = '';
        if (fechaFin) fechaFin.value = '';
        
        ButtonManager.clearActiveQuickButtons();
        DashboardManager.applyFilters();
    }
};

/**
 * ============================================================================
 * GESTIÓN DE BOTONES RÁPIDOS
 * ============================================================================
 */

const ButtonManager = {
    /**
     * Establecer rangos rápidos de fechas
     */
    setQuickRange(range) {
        const today = new Date();
        let startDate, endDate;

        switch (range) {
            case 'today':
                startDate = endDate = today;
                break;
            case 'yesterday':
                startDate = endDate = new Date(today);
                startDate.setDate(today.getDate() - 1);
                endDate.setDate(today.getDate() - 1);
                break;
            case 'thisWeek':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - today.getDay());
                endDate = today;
                break;
            case 'lastWeek':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - today.getDay() - 7);
                endDate = new Date(today);
                endDate.setDate(today.getDate() - today.getDay() - 1);
                break;
            case 'thisMonth':
                startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                endDate = today;
                break;
            case 'lastMonth':
                startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            case 'last30Days':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 30);
                endDate = today;
                break;
            case 'last90Days':
                startDate = new Date(today);
                startDate.setDate(today.getDate() - 90);
                endDate = today;
                break;
            default:
                return;
        }

        const fechaInicio = Utils.getElement('fechaInicio');
        const fechaFin = Utils.getElement('fechaFin');
        
        if (fechaInicio) fechaInicio.value = startDate.toISOString().split('T')[0];
        if (fechaFin) fechaFin.value = endDate.toISOString().split('T')[0];

        this.setActiveQuickButton(range);
        DashboardManager.applyFilters();
    },

    /**
     * Marcar botón rápido como activo
     */
    setActiveQuickButton(range) {
        this.clearActiveQuickButtons();
        const buttons = document.querySelectorAll('.quick-date-btn');
        buttons.forEach(btn => {
            if (btn.getAttribute('onclick')?.includes(range)) {
                btn.classList.add('active');
            }
        });
    },

    /**
     * Limpiar botones rápidos activos
     */
    clearActiveQuickButtons() {
        document.querySelectorAll('.quick-date-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    }
};

/**
 * ============================================================================
 * GESTIÓN DEL DASHBOARD Y DATOS
 * ============================================================================
 */

const DashboardManager = {
    /**
     * Cargar datos del dashboard
     */
    async loadDashboardData() {
        this.showLoading(true);
        ChartManager.clearAllCharts();

        try {
            const response = await fetch('includes/dashboard_data.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Dashboard data loaded:', data);

            if (data.success) {
                this.updateStats(data.stats);
                ChartManager.updateAllCharts(data.charts);
                this.updateInfoPanel();
            } else {
                throw new Error(data.message || 'Error al cargar los datos');
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            NotificationManager.showError('Error de conexión al cargar los datos: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    },

    /**
     * Cargar datos filtrados
     */
    async loadFilteredData() {
        this.showLoading(true);
        ChartManager.clearAllCharts();

        try {
            const params = new URLSearchParams(AppState.currentFilters);
            const url = `includes/dashboard_data.php?${params}`;
            
            console.log('Loading filtered data with URL:', url);
            console.log('Current filters:', AppState.currentFilters);

            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Filtered data loaded:', data);

            if (data.success) {
                this.updateStats(data.stats);
                ChartManager.updateAllCharts(data.charts);
                this.updateInfoPanel();
            } else {
                throw new Error(data.message || 'Error al cargar los datos filtrados');
            }
        } catch (error) {
            console.error('Error:', error);
            NotificationManager.showError('Error al cargar los datos filtrados: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    },

    /**
     * Aplicar filtros
     */
    applyFilters() {
        if (!DateManager.validateDateRange()) {
            return;
        }

        const filtros = {
            fecha_inicio: Utils.getElement('fechaInicio')?.value || '',
            fecha_fin: Utils.getElement('fechaFin')?.value || '',
            area: Utils.getElement('filtroArea')?.value || ''
        };

        AppState.currentFilters = filtros;
        this.loadFilteredData();
    },

    /**
     * Actualizar estadísticas en las tarjetas
     */
    updateStats(stats) {
        console.log('Updating stats:', stats);

        const safeStats = {
            total: parseInt(stats.total) || 0,
            con_hallazgos: parseInt(stats.con_hallazgos) || 0,
            retrabajo: parseInt(stats.retrabajo) || 0,
            cuarentena: parseInt(stats.cuarentena) || 0
        };

        Utils.animateNumber('totalRegistros', safeStats.total);
        Utils.animateNumber('registrosConHallazgos', safeStats.con_hallazgos);
        Utils.animateNumber('registrosRetrabajo', safeStats.retrabajo);
        Utils.animateNumber('registrosCuarentena', safeStats.cuarentena);
    },

    /**
     * Actualizar panel de información
     */
    updateInfoPanel() {
        const now = new Date();
        const ultimaActualizacion = Utils.getElement('ultimaActualizacion');
        if (ultimaActualizacion) {
            ultimaActualizacion.textContent = now.toLocaleString('es-ES');
        }

        let filtrosTexto = 'Mostrando todos los registros';
        const filtrosActivos = [];

        if (AppState.currentFilters.fecha_inicio && AppState.currentFilters.fecha_fin) {
            filtrosActivos.push(`Fechas: ${AppState.currentFilters.fecha_inicio} al ${AppState.currentFilters.fecha_fin}`);
        } else if (AppState.currentFilters.fecha_inicio) {
            filtrosActivos.push(`Desde: ${AppState.currentFilters.fecha_inicio}`);
        } else if (AppState.currentFilters.fecha_fin) {
            filtrosActivos.push(`Hasta: ${AppState.currentFilters.fecha_fin}`);
        }

        if (AppState.currentFilters.area) {
            filtrosActivos.push(`Área: ${AppState.currentFilters.area}`);
        }

        if (filtrosActivos.length > 0) {
            filtrosTexto = `Filtros aplicados: ${filtrosActivos.join(', ')}`;
        }

        const filtrosAplicados = Utils.getElement('filtrosAplicados');
        if (filtrosAplicados) {
            filtrosAplicados.textContent = filtrosTexto;
        }
    },

    /**
     * Mostrar/ocultar indicador de carga
     */
    showLoading(show) {
        AppState.isLoading = show;
        Utils.toggleElement('loadingInfo', show);
        Utils.toggleElement('infoContent', !show);
    },

    /**
     * Refrescar datos
     */
    refreshData() {
        console.log('Refreshing dashboard data...');
        if (Object.keys(AppState.currentFilters).length > 0) {
            this.loadFilteredData();
        } else {
            this.loadDashboardData();
        }
        
        // También refrescar hallazgos si la sección existe
        if (Utils.elementExists('tablaRegistrosActivosBody')) {
            HallazgosManager.loadData();
        }
    }
};

/**
 * ============================================================================
 * GESTIÓN DE GRÁFICOS
 * ============================================================================
 */

const ChartManager = {
    /**
     * Limpiar todos los gráficos existentes
     */
    clearAllCharts() {
        Object.keys(AppState.charts).forEach(key => {
            if (AppState.charts[key]) {
                AppState.charts[key].destroy();
                AppState.charts[key] = null;
            }
        });

        const chartContainers = [
            'chartAreas', 'chartModelos', 'chartUsuarios', 
            'chartNoParte', 'chartDefectos'
        ];

        chartContainers.forEach(chartId => {
            const container = Utils.getElement(chartId)?.parentElement;
            if (container) {
                const loadingElement = container.querySelector('.chart-loading');
                if (loadingElement) {
                    loadingElement.remove();
                }
                
                container.innerHTML = `
                    <canvas id="${chartId}"></canvas>
                    <div class="chart-loading">
                        <div class="spinner"></div>
                        <p>Cargando...</p>
                    </div>
                `;
            }
        });
    },

    /**
     * Actualizar todos los gráficos
     */
    updateAllCharts(charts) {
        if (charts.areas?.labels?.length > 0) {
            AppState.charts.area = this.updateOrCreateChart('chartAreas', charts.areas, 'Áreas con Más Hallazgos', 'doughnut');
        } else {
            this.showEmptyChart('chartAreas', 'No hay datos de áreas disponibles');
        }

        if (charts.modelos?.labels?.length > 0) {
            AppState.charts.modelo = this.updateOrCreateChart('chartModelos', charts.modelos, 'Modelos con Más Hallazgos', 'bar');
        } else {
            this.showEmptyChart('chartModelos', 'No hay datos de modelos disponibles');
        }

        if (charts.usuarios?.labels?.length > 0) {
            AppState.charts.usuario = this.updateOrCreateChart('chartUsuarios', charts.usuarios, 'Usuarios con Más Hallazgos', 'pie');
        } else {
            this.showEmptyChart('chartUsuarios', 'No hay datos de usuarios disponibles');
        }

        if (charts.no_parte?.labels?.length > 0) {
            AppState.charts.noParte = this.updateOrCreateChart('chartNoParte', charts.no_parte, 'No. de Parte con Más Hallazgos', 'bar');
        } else {
            this.showEmptyChart('chartNoParte', 'No hay datos de no. de parte disponibles');
        }

        if (charts.defectos?.labels?.length > 0) {
            AppState.charts.defectos = this.updateOrCreateChart('chartDefectos', charts.defectos, 'Defectos Más Reportados', 'horizontalBar');
        } else {
            this.showEmptyChart('chartDefectos', 'No hay datos de defectos disponibles');
        }

        // Cargar gráfica de scrap
        this.loadScrapChart();
    },

    /**
     * Actualizar o crear un gráfico
     */
    updateOrCreateChart(canvasId, data, title, type, customConfig = null) {
        const canvas = Utils.getElement(canvasId);
        if (!canvas) return null;

        const ctx = canvas.getContext('2d');
        const container = canvas.parentElement;

        // Remover indicador de carga
        const loadingElement = container.querySelector('.chart-loading');
        if (loadingElement) {
            loadingElement.remove();
        }

        // Si hay configuración personalizada, usarla
        if (customConfig) {
            return new Chart(ctx, customConfig);
        }

        // Configuración por defecto
        const chartConfig = {
            type: type === 'horizontalBar' ? 'bar' : type,
            data: {
                labels: data.labels,
                datasets: [{
                    label: title,
                    data: data.data,
                    backgroundColor: AppConfig.CHART_COLORS.slice(0, data.labels.length),
                    borderColor: AppConfig.CHART_COLORS.slice(0, data.labels.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: type === 'pie' || type === 'doughnut',
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + (type === 'pie' || type === 'doughnut' ? ' (' + ((context.parsed / data.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1) + '%)' : '');
                            }
                        }
                    }
                },
                scales: type !== 'pie' && type !== 'doughnut' ? {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                } : undefined,
                indexAxis: type === 'horizontalBar' ? 'y' : undefined
            }
        };

        return new Chart(ctx, chartConfig);
    },

    /**
     * Mostrar mensaje de gráfico vacío
     */
    showEmptyChart(canvasId, message) {
        const canvas = Utils.getElement(canvasId);
        if (!canvas) return;

        const container = canvas.parentElement;
        const loadingElement = container.querySelector('.chart-loading');
        if (loadingElement) {
            loadingElement.remove();
        }

        container.innerHTML = `
            <canvas id="${canvasId}"></canvas>
            <div class="chart-empty">
                <i class="fas fa-chart-pie"></i>
                <p>${message}</p>
            </div>
        `;
        container.style.position = 'relative';
    },

    /**
     * Cargar gráfica de dinero perdido en scrap
     */
    async loadScrapChart() {
        try {
            // Obtener filtros actuales del dashboard
            const filters = {
                fecha_inicio: Utils.getElement('fechaInicio')?.value || '',
                fecha_fin: Utils.getElement('fechaFin')?.value || '',
                area: Utils.getElement('filtroArea')?.value || '',
                tipo: 'mensual' // Por defecto mensual
            };

            const params = new URLSearchParams();
            Object.entries(filters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });

            const response = await fetch(`includes/scrap_data.php?${params.toString()}`);
            const result = await response.json();

            if (!result.success) {
                this.showEmptyChart('chartScrap', 'Error al cargar datos de scrap');
                return;
            }

            // Preparar datos para gráfica de líneas (evolución temporal)
            const temporalData = result.temporal || [];
            
            if (temporalData.length === 0) {
                this.showEmptyChart('chartScrap', 'No hay datos de scrap disponibles');
                return;
            }

            const chartData = {
                labels: temporalData.map(item => {
                    // Formatear etiquetas de fecha
                    if (item.periodo.includes('-')) {
                        const [year, month] = item.periodo.split('-');
                        const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                                          'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                        return `${monthNames[parseInt(month) - 1]} ${year}`;
                    }
                    return item.periodo;
                }),
                datasets: [
                    {
                        label: 'Dinero Perdido ($USD)',
                        data: temporalData.map(item => parseFloat(item.total_periodo) || 0),
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#dc3545',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Cantidad de Registros',
                        data: temporalData.map(item => parseInt(item.cantidad_registros) || 0),
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1',
                        pointBackgroundColor: '#ffc107',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            };

            const config = {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Dinero Perdido en Scrap - Evolución Temporal',
                            font: { size: 16, weight: 'bold' },
                            padding: 20
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    
                                    if (context.dataset.label === 'Dinero Perdido ($USD)') {
                                        label += '$' + Number(context.parsed.y).toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                    } else {
                                        label += context.parsed.y + ' registros';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Período'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Dinero Perdido ($USD)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '$' + Number(value).toLocaleString('en-US');
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Cantidad de Registros'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            };

            AppState.charts.scrap = this.updateOrCreateChart('chartScrap', chartData, 'Dinero Perdido en Scrap', 'line', config);

            // Mostrar resumen de estadísticas
            this.updateScrapStats(result.resumen);

        } catch (error) {
            console.error('Error al cargar gráfica de scrap:', error);
            this.showEmptyChart('chartScrap', 'Error al cargar datos de scrap');
        }
    },

    /**
     * Actualizar estadísticas de scrap
     */
    updateScrapStats(resumen) {
        // Si hay elementos para mostrar estadísticas adicionales, actualizarlos aquí
        console.log('Resumen de scrap:', resumen);
        
        // Ejemplo: si hay un elemento para mostrar el total perdido
        const totalPerdidoElement = Utils.getElement('totalDineroPerdido');
        if (totalPerdidoElement && resumen.total_perdido) {
            totalPerdidoElement.textContent = '$' + Number(resumen.total_perdido).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
};

/**
 * ============================================================================
 * GESTIÓN DE HALLAZGOS (TABLAS)
 * ============================================================================
 */

const HallazgosManager = {
    /**
     * Obtener filtros de tabla
     */
    getTableFilters() {
        return {
            fechaInicio: Utils.getElement('filtroTablaFechaInicio')?.value || '',
            fechaFin: Utils.getElement('filtroTablaFechaFin')?.value || '',
            area: Utils.getElement('filtroTablaArea')?.value || '',
            retrabajo: Utils.getElement('filtroTablaRetrabajo')?.value || '',
            modelo: Utils.getElement('filtroTablaModelo')?.value || ''
        };
    },

    /**
     * Validar fechas de tabla
     */
    validateTableDates() {
        const fechaInicio = Utils.getElement('filtroTablaFechaInicio')?.value;
        const fechaFin = Utils.getElement('filtroTablaFechaFin')?.value;

        if (fechaInicio && fechaFin) {
            if (new Date(fechaInicio) > new Date(fechaFin)) {
                NotificationManager.show('La fecha de inicio no puede ser mayor que la fecha de fin', 'error');
                return false;
            }
        }
        return true;
    },

    /**
     * Aplicar filtros de tabla
     */
    applyTableFilters() {
        console.log('Aplicando filtros de tabla...');
        
        if (!this.validateTableDates()) {
            return;
        }

        this.loadData();
        NotificationManager.show('Filtros aplicados correctamente', 'success');
    },

    /**
     * Limpiar filtros de tabla
     */
    clearTableFilters() {
        console.log('Limpiando filtros de tabla...');

        const elements = [
            'filtroTablaFechaInicio',
            'filtroTablaFechaFin', 
            'filtroTablaArea',
            'filtroTablaRetrabajo',
            'filtroTablaModelo'
        ];

        elements.forEach(id => {
            const element = Utils.getElement(id);
            if (element) {
                element.value = '';
            }
        });

        // Ocultar información de filtros aplicados
        const filtrosInfo = Utils.getElement('filtrosAplicadosInfo');
        if (filtrosInfo) {
            filtrosInfo.classList.add('d-none');
        }

        this.loadData();
        NotificationManager.show('Filtros limpiados', 'success');
    },

    /**
     * Cargar datos de hallazgos
     */
    async loadData() {
        const filtros = this.getTableFilters();
        console.log('Cargando hallazgos con filtros:', filtros);

        try {
            // Cargar datos activos e inactivos (todos excepto cuarentena)
            const activosResponse = await fetch('includes/hallazgos_data.php?' + new URLSearchParams({
                ...filtros,
                estado: 'no_cuarentena'
            }));

            if (activosResponse.ok) {
                const activosData = await activosResponse.json();
                if (activosData.success) {
                    AppState.hallazgosData = activosData.data;
                    this.displayHallazgos(activosData.data, 'tablaRegistrosActivosBody', 'activo');
                    this.updateActiveCount();
                }
            }

            // Cargar datos de cuarentena
            const cuarentenaResponse = await fetch('includes/hallazgos_data.php?' + new URLSearchParams({
                ...filtros,
                estado: 'cuarentena'
            }));

            console.log('Respuesta cuarentena:', cuarentenaResponse.status);

            if (cuarentenaResponse.ok) {
                const cuarentenaData = await cuarentenaResponse.json();
                console.log('Datos de cuarentena recibidos:', cuarentenaData);
                if (cuarentenaData.success) {
                    AppState.cuarentenaData = cuarentenaData.data;
                    console.log('AppState.cuarentenaData actualizado:', AppState.cuarentenaData);
                    this.displayHallazgos(cuarentenaData.data, 'tablaRegistrosCuarentenaBody', 'cuarentena');
                    this.updateQuarantineCount();
                } else {
                    console.error('Error en datos de cuarentena:', cuarentenaData.message);
                    AppState.cuarentenaData = [];
                    this.updateQuarantineCount();
                }
            } else {
                console.error('Error HTTP al cargar cuarentena:', cuarentenaResponse.status);
                AppState.cuarentenaData = [];
                this.updateQuarantineCount();
            }

            // Cargar datos de scrap
            const scrapResponse = await fetch('includes/scrap_table_data.php?' + new URLSearchParams(filtros));

            console.log('Respuesta scrap:', scrapResponse.status);

            if (scrapResponse.ok) {
                const scrapData = await scrapResponse.json();
                console.log('Datos de scrap recibidos:', scrapData);
                if (scrapData.success) {
                    AppState.scrapData = scrapData.data;
                    console.log('AppState.scrapData actualizado:', AppState.scrapData);
                    this.displayScrapData(scrapData.data, 'tablaRegistrosScrapBody');
                    this.updateScrapCount();
                } else {
                    console.error('Error en datos de scrap:', scrapData.message);
                    AppState.scrapData = [];
                    this.updateScrapCount();
                }
            } else {
                console.error('Error HTTP al cargar scrap:', scrapResponse.status);
                AppState.scrapData = [];
                this.updateScrapCount();
            }
        } catch (error) {
            console.error('Error cargando hallazgos:', error);
            NotificationManager.show('Error al cargar los hallazgos', 'error');
        }
    },

    /**
     * Mostrar hallazgos en tabla
     */
    displayHallazgos(data, tableBodyId, estado) {
        console.log(`Mostrando hallazgos en tabla: ${tableBodyId}, estado: ${estado}`);
        
        const tbody = Utils.getElement(tableBodyId);
        if (!tbody) {
            console.error(`Tabla ${tableBodyId} no encontrada`);
            return;
        }

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${estado === 'cuarentena' ? '10' : '11'}" class="text-center py-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No se encontraron registros</p>
                    </td>
                </tr>
            `;
            return;
        }

        const rows = data.map(hallazgo => {
            const baseColumns = `
                <td><strong>#${hallazgo.id}</strong></td>
                <td>${Utils.formatDate(hallazgo.fecha_creacion)}</td>
                <td>${hallazgo.area_ubicacion || 'N/A'}</td>
                <td>${hallazgo.modelo || 'N/A'}</td>
                <td>${hallazgo.no_parte || 'N/A'}</td>
                <td>${hallazgo.job_order || 'N/A'}</td>
                <td>${hallazgo.usuario_nombre || 'N/A'}</td>
            `;

            if (estado === 'activo') {
                return `
                    <tr>
                        ${baseColumns}
                        <td><span class="badge ${hallazgo.retrabajo === 'Si' ? 'bg-warning' : 'bg-success'}">${hallazgo.retrabajo || 'No'}</span></td>
                        <td>
                            <span class="badge bg-info defectos-clickeable" style="cursor: pointer;" onclick="HallazgosManager.verDefectos(${hallazgo.id})" title="Click para ver detalles de los defectos">
                                ${hallazgo.total_defectos || 0}
                            </span>
                        </td>
                        <td><span class="badge bg-secondary">${hallazgo.total_evidencias || 0}</span></td>
                        <td>
                            <div class="action-buttons">
                                ${this.getActionButtons(hallazgo)}
                                <button class="btn btn-sm btn-outline-info" onclick="HallazgosManager.verEvidencias(${hallazgo.id})" title="Ver evidencias">
                                    <i class="fas fa-images"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="HallazgosManager.verObservaciones(${hallazgo.id})" title="Ver observaciones">
                                    <i class="fas fa-comment-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                return `
                    <tr>
                        ${baseColumns}
                        <td>
                            <span class="badge bg-info defectos-clickeable" style="cursor: pointer;" onclick="HallazgosManager.verDefectos(${hallazgo.id})" title="Click para ver detalles de los defectos">
                                ${hallazgo.total_defectos || 0}
                            </span>
                        </td>
                        <td><span class="badge bg-secondary">${hallazgo.total_evidencias || 0}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-success" onclick="HallazgosManager.cambiarEstado(${hallazgo.id}, 'activo')" title="Activar hallazgo">
                                    <i class="fas fa-check"></i> Activar
                                </button>
                                <button class="btn btn-sm btn-danger btn-scrap" 
                                        data-hallazgo-id="${hallazgo.id}" 
                                        data-modelo="${(hallazgo.modelo || '').replace(/"/g, '&quot;').replace(/'/g, '&#x27;')}" 
                                        data-no-parte="${(hallazgo.no_parte || '').replace(/"/g, '&quot;').replace(/'/g, '&#x27;')}"
                                        title="Enviar a scrap">
                                    <i class="fas fa-trash"></i> Scrap
                                </button>
                                <button class="btn btn-sm btn-outline-info" onclick="HallazgosManager.verEvidencias(${hallazgo.id})" title="Ver evidencias">
                                    <i class="fas fa-images"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="HallazgosManager.verObservaciones(${hallazgo.id})" title="Ver observaciones">
                                    <i class="fas fa-comment-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }).join('');

        tbody.innerHTML = rows;
        console.log(`Tabla ${tableBodyId} actualizada con ${data.length} registros`);
    },

    /**
     * Mostrar datos de scrap en tabla
     */
    displayScrapData(data, tableBodyId) {
        console.log(`Mostrando datos de scrap en tabla: ${tableBodyId}`);
        
        const tbody = Utils.getElement(tableBodyId);
        if (!tbody) {
            console.error(`Tabla ${tableBodyId} no encontrada`);
            return;
        }

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No se encontraron registros de scrap</p>
                    </td>
                </tr>
            `;
            return;
        }

        const rows = data.map(scrap => {
            return `
                <tr>
                    <td><strong>#${scrap.id}</strong></td>
                    <td>${Utils.formatDate(scrap.fecha_creacion)}</td>
                    <td>${scrap.area_ubicacion || 'N/A'}</td>
                    <td>${scrap.modelo || 'N/A'}</td>
                    <td>${scrap.no_parte || 'N/A'}</td>
                    <td>${scrap.job_order || 'N/A'}</td>
                    <td>${scrap.usuario_nombre || 'N/A'}</td>
                    <td>
                        <span class="badge bg-info defectos-clickeable" style="cursor: pointer;" onclick="HallazgosManager.verDefectos(${scrap.id})" title="Click para ver detalles de los defectos">
                            ${scrap.total_defectos || 0}
                        </span>
                    </td>
                    <td><span class="badge bg-secondary">${scrap.total_evidencias || 0}</span></td>
                    <td>${scrap.fecha_scrap ? Utils.formatDate(scrap.fecha_scrap) : 'N/A'}</td>
                    <td><span class="badge bg-danger">$${scrap.valor_scrap || '0.00'}</span></td>
                </tr>
            `;
        }).join('');

        tbody.innerHTML = rows;
        console.log(`Tabla ${tableBodyId} actualizada con ${data.length} registros de scrap`);
    },

    /**
     * Obtener botones de acción según estado del retrabajo
     */
    getActionButtons(item) {
        const buttons = [];
        
        // Si retrabajo es "No", mostrar botones para retrabajo, cuarentena y scrap
        if (item.retrabajo === 'No') {
            buttons.push(`
                <button class="btn btn-sm btn-warning" onclick="HallazgosManager.cambiarRetrabajoEstado(${item.id}, 'Si')" title="Marcar como retrabajo">
                    <i class="fas fa-redo"></i> Retrabajo
                </button>
                <button class="btn btn-sm btn-danger" onclick="HallazgosManager.cambiarEstado(${item.id}, 'cuarentena')" title="Enviar a cuarentena">
                    <i class="fas fa-exclamation-triangle"></i> Cuarentena
                </button>
                <button class="btn btn-sm btn-dark" onclick="HallazgosManager.mostrarModalScrap(${item.id})" title="Enviar a scrap">
                    <i class="fas fa-trash"></i> Scrap
                </button>
            `);
        }
        
        // Si retrabajo es "Si", mostrar botones para cuarentena, scrap y para quitar retrabajo
        if (item.retrabajo === 'Si') {
            buttons.push(`
                <button class="btn btn-sm btn-danger" onclick="HallazgosManager.cambiarEstado(${item.id}, 'cuarentena')" title="Enviar a cuarentena">
                    <i class="fas fa-exclamation-triangle"></i> Cuarentena
                </button>
                <button class="btn btn-sm btn-dark" onclick="HallazgosManager.mostrarModalScrap(${item.id})" title="Enviar a scrap">
                    <i class="fas fa-trash"></i> Scrap
                </button>
                <button class="btn btn-sm btn-success" onclick="HallazgosManager.cambiarRetrabajoEstado(${item.id}, 'No')" title="Quitar retrabajo">
                    <i class="fas fa-check"></i> Quitar Retrabajo
                </button>
            `);
        }
        
        return buttons.join('');
    },

    /**
     * Cambiar estado de un hallazgo
     */
    cambiarEstado(id, nuevoEstado) {
        const mensajes = {
            'cuarentena': '¿Enviar este hallazgo a cuarentena?',
            'activo': '¿Reactivar este hallazgo?',
            'scrap': '¿Marcar este hallazgo como scrap? Esta acción no se puede deshacer.'
        };

        const modal = new bootstrap.Modal(Utils.getElement('confirmModal'));
        const confirmMessage = Utils.getElement('confirmMessage');
        const confirmButton = Utils.getElement('confirmButton');

        if (confirmMessage) {
            confirmMessage.textContent = mensajes[nuevoEstado];
        }

        if (confirmButton) {
            confirmButton.onclick = () => {
                this.ejecutarCambioEstado(id, nuevoEstado);
                modal.hide();
            };
        }

        modal.show();
    },

    /**
     * Cambiar estado de retrabajo de un hallazgo
     */
    cambiarRetrabajoEstado(id, nuevoRetrabajo) {
        const mensajes = {
            'Si': '¿Marcar este hallazgo como retrabajo?',
            'No': '¿Quitar el estado de retrabajo de este hallazgo?'
        };

        const modal = new bootstrap.Modal(Utils.getElement('confirmModal'));
        const confirmMessage = Utils.getElement('confirmMessage');
        const confirmButton = Utils.getElement('confirmButton');

        if (confirmMessage) {
            confirmMessage.textContent = mensajes[nuevoRetrabajo];
        }

        if (confirmButton) {
            confirmButton.onclick = () => {
                this.ejecutarCambioRetrabajo(id, nuevoRetrabajo);
                modal.hide();
            };
        }

        modal.show();
    },

    /**
     * Ejecutar cambio de retrabajo en el servidor
     */
    async ejecutarCambioRetrabajo(id, nuevoRetrabajo) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('retrabajo', nuevoRetrabajo);

            const response = await fetch('includes/cambiar_retrabajo.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                NotificationManager.show('Estado de retrabajo actualizado correctamente', 'success');
                this.loadData(); // Recargar datos
            } else {
                NotificationManager.show('Error al actualizar el retrabajo: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            NotificationManager.show('Error de conexión al actualizar el retrabajo', 'error');
        }
    },

    /**
     * Ejecutar cambio de estado en el servidor
     */
    async ejecutarCambioEstado(id, nuevoEstado) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('estado', nuevoEstado);

            const response = await fetch('includes/cambiar_estado.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                NotificationManager.show('Estado actualizado correctamente', 'success');
                this.loadData(); // Recargar datos
            } else {
                NotificationManager.show('Error al actualizar el estado: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            NotificationManager.show('Error de conexión al actualizar el estado', 'error');
        }
    },

    /**
     * Ver evidencias de un hallazgo
     */
    async verEvidencias(hallazgoId) {
        const modal = new bootstrap.Modal(Utils.getElement('evidenciaModal'));
        const modalBody = Utils.getElement('evidenciaModalBody');
        const hallazgoIdSpan = Utils.getElement('evidenciaHallazgoId');

        if (modalBody) {
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando evidencias...</p>
                </div>
            `;
        }

        if (hallazgoIdSpan) {
            hallazgoIdSpan.textContent = hallazgoId;
        }

        modal.show();

        try {
            console.log(`🔍 Cargando evidencias para hallazgo ID: ${hallazgoId}`);
            const response = await fetch(`includes/evidencias_data.php?hallazgo_id=${hallazgoId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('📊 Datos de evidencias recibidos:', data);

            if (data.success && modalBody) {
                if (data.evidencias.length === 0) {
                    // Mostrar información de debug si no hay evidencias
                    const debugInfo = data.debug ? `
                        <div class="alert alert-info mt-3">
                            <h6>ℹ️ Información de diagnóstico:</h6>
                            <ul class="mb-0 small">
                                <li>Directorio uploads existe: ${data.debug.uploads_dir_exists ? '✅' : '❌'}</li>
                                <li>Directorio legible: ${data.debug.uploads_dir_readable ? '✅' : '❌'}</li>
                                <li>Archivos en uploads: ${data.debug.files_in_uploads}</li>
                                <li>Patrones buscados: ${data.debug.pattern_searched.join(', ')}</li>
                                <li>Total encontradas: ${data.debug.total_encontradas}</li>
                                <li>Imágenes filtradas: ${data.debug.imagenes_filtradas}</li>
                            </ul>
                        </div>
                    ` : '';
                    
                    modalBody.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay evidencias disponibles para este hallazgo</p>
                            <small class="text-muted">Hallazgo ID: ${hallazgoId}</small>
                            ${debugInfo}
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Se encontraron ${data.evidencias.length} evidencia(s) para este hallazgo
                        </div>
                        <div class="row">
                            ${data.evidencias.map((evidencia, index) => `
                                <div class="col-md-4 mb-3">
                                    <div class="evidencia-item">
                                        <div class="position-relative">
                                            <img src="uploads/${evidencia.archivo}" 
                                                 class="img-fluid rounded shadow-sm" 
                                                 alt="Evidencia ${index + 1}"
                                                 style="cursor: pointer; max-height: 200px; width: 100%; object-fit: cover;"
                                                 onclick="HallazgosManager.ampliarImagen('uploads/${evidencia.archivo}')"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <div class="text-center p-3 border rounded" style="display: none;">
                                                <i class="fas fa-image fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Error al cargar imagen</p>
                                                <small class="text-muted">${evidencia.archivo}</small>
                                            </div>
                                        </div>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                📅 ${Utils.formatDate(evidencia.fecha_subida)}
                                                ${evidencia.size ? `<br>📁 ${Utils.formatFileSize(evidencia.size)}` : ''}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        ${data.debug ? `
                            <div class="alert alert-secondary mt-3">
                                <details>
                                    <summary>🔧 Información técnica</summary>
                                    <ul class="mb-0 small mt-2">
                                        <li>Directorio: ${data.debug.uploads_dir_path}</li>
                                        <li>Directorio existe: ${data.debug.uploads_dir_exists ? '✅' : '❌'}</li>
                                        <li>Total archivos encontrados: ${data.debug.total_encontradas}</li>
                                        <li>Imágenes válidas: ${data.debug.imagenes_filtradas}</li>
                                    </ul>
                                </details>
                            </div>
                        ` : ''}
                    `;
                }
            } else {
                if (modalBody) {
                    modalBody.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <p class="text-warning">Error al cargar las evidencias</p>
                            <p class="text-muted">${data.message || 'Error desconocido'}</p>
                            ${data.debug ? `
                                <div class="alert alert-warning mt-3">
                                    <h6>🔧 Información de diagnóstico:</h6>
                                    <pre class="small">${JSON.stringify(data.debug, null, 2)}</pre>
                                </div>
                            ` : ''}
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('❌ Error cargando evidencias:', error);
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <p class="text-danger">Error de conexión</p>
                        <p class="text-muted">No se pudo conectar al servidor para cargar las evidencias</p>
                        <div class="alert alert-danger mt-3">
                            <h6>❌ Detalles del error:</h6>
                            <p class="mb-0"><strong>Tipo:</strong> ${error.name}</p>
                            <p class="mb-0"><strong>Mensaje:</strong> ${error.message}</p>
                            <p class="mb-0"><strong>URL intentada:</strong> includes/evidencias_data.php?hallazgo_id=${hallazgoId}</p>
                        </div>
                    </div>
                `;
            }
        }
    },

    /**
     * Ampliar imagen en modal
     */
    ampliarImagen(src) {
        const modalId = 'imagenAmpliada';
        const modalAnterior = Utils.getElement(modalId);
        if (modalAnterior) {
            modalAnterior.remove();
        }

        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Evidencia</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${src}" class="img-fluid" alt="Evidencia ampliada">
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(Utils.getElement(modalId));
        modal.show();

        Utils.getElement(modalId).addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    },

    /**
     * Ver observaciones de un hallazgo
     */
    async verObservaciones(hallazgoId) {
        const modal = new bootstrap.Modal(Utils.getElement('observacionesModal'));
        const modalBody = Utils.getElement('observacionesModalBody');
        const hallazgoIdSpan = Utils.getElement('observacionesHallazgoId');

        if (modalBody) {
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando observaciones...</p>
                </div>
            `;
        }

        if (hallazgoIdSpan) {
            hallazgoIdSpan.textContent = hallazgoId;
        }

        modal.show();

        try {
            const response = await fetch(`includes/observaciones_data.php?hallazgo_id=${hallazgoId}`);
            const data = await response.json();

            if (data.success && modalBody) {
                const hallazgoInfo = data.hallazgo_info;
                
                if (!hallazgoInfo.observaciones || hallazgoInfo.observaciones.trim() === '') {
                    modalBody.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Sin observaciones</h5>
                            <p class="text-muted">Este hallazgo no tiene observaciones registradas</p>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `
                        <div class="observaciones-content">
                            <div class="card border-0 bg-light">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Información del Hallazgo
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-2"><strong>ID:</strong> #${hallazgoInfo.id}</p>
                                            <p class="mb-2"><strong>Fecha:</strong> ${Utils.formatDate(hallazgoInfo.fecha_creacion)}</p>
                                            <p class="mb-2"><strong>Área:</strong> ${hallazgoInfo.area_ubicacion || 'N/A'}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-2"><strong>Modelo:</strong> ${hallazgoInfo.modelo || 'N/A'}</p>
                                            <p class="mb-2"><strong>No. Parte:</strong> ${hallazgoInfo.no_parte || 'N/A'}</p>
                                            <p class="mb-2"><strong>Usuario:</strong> ${hallazgoInfo.usuario_nombre || 'N/A'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="card border-0">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-comment-alt me-2"></i>
                                            Observaciones
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="observaciones-text p-3 bg-light rounded">
                                            ${hallazgoInfo.observaciones.replace(/\n/g, '<br>')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } else {
                if (modalBody) {
                    modalBody.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <p class="text-warning">Error al cargar las observaciones: ${data.message || 'Error desconocido'}</p>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <p class="text-danger">Error de conexión</p>
                    </div>
                `;
            }
        }
    },

    /**
     * Ver defectos de un hallazgo
     */
    async verDefectos(hallazgoId) {
        const modal = new bootstrap.Modal(Utils.getElement('defectosModal'));
        const modalBody = Utils.getElement('defectosModalBody');
        const hallazgoIdSpan = Utils.getElement('defectosHallazgoId');

        if (modalBody) {
            modalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando defectos...</p>
                </div>
            `;
        }

        if (hallazgoIdSpan) {
            hallazgoIdSpan.textContent = hallazgoId;
        }

        modal.show();

        try {
            const response = await fetch(`includes/defectos_data.php?hallazgo_id=${hallazgoId}`);
            const data = await response.json();

            if (data.success && modalBody) {
                if (data.defectos.length === 0) {
                    modalBody.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success">Sin defectos reportados</h5>
                            <p class="text-muted">Este hallazgo no tiene defectos registrados</p>
                        </div>
                    `;
                } else {
                    const hallazgoInfo = data.hallazgo_info;
                    modalBody.innerHTML = `
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Modelo:</strong> ${hallazgoInfo.modelo || 'N/A'}
                                </div>
                                <div class="col-md-4">
                                    <strong>No. Parte:</strong> ${hallazgoInfo.no_parte || 'N/A'}
                                </div>
                                <div class="col-md-4">
                                    <strong>Área:</strong> ${hallazgoInfo.area_ubicacion || 'N/A'}
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h6 class="mb-3">
                            <i class="fas fa-list me-2"></i>
                            Defectos encontrados (${data.count})
                        </h6>
                        <div class="defectos-list">
                            ${data.defectos.map((defecto, index) => `
                                <div class="defecto-item mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="defecto-content flex-grow-1">
                                            <span class="badge bg-warning text-dark me-2">#${index + 1}</span>
                                            <strong class="defecto-texto">${defecto.defecto}</strong>
                                        </div>
                                        <div class="defecto-fecha text-muted small">
                                            <i class="fas fa-clock me-1"></i>
                                            ${Utils.formatDate(defecto.fecha_creacion)}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Total de defectos: <strong>${data.count}</strong>
                            </small>
                        </div>
                    `;
                }
            } else {
                if (modalBody) {
                    modalBody.innerHTML = `
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <p class="text-warning">Error al cargar los defectos: ${data.message || 'Error desconocido'}</p>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Error:', error);
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <p class="text-danger">Error de conexión</p>
                    </div>
                `;
            }
        }
    },

    /**
     * Actualizar contador de registros activos
     */
    updateActiveCount() {
        const count = AppState.hallazgosData ? AppState.hallazgosData.length : 0;
        const badge = Utils.getElement('contadorActivos');
        console.log('Actualizando contador activos:', count, 'Data:', AppState.hallazgosData);
        if (badge) {
            badge.textContent = count;
        } else {
            console.error('No se encontró el elemento contadorActivos');
        }
    },

    /**
     * Actualizar contador de registros en cuarentena
     */
    updateQuarantineCount() {
        const count = AppState.cuarentenaData ? AppState.cuarentenaData.length : 0;
        const badge = Utils.getElement('contadorCuarentena');
        console.log('Actualizando contador cuarentena:', count, 'Data:', AppState.cuarentenaData);
        if (badge) {
            badge.textContent = count;
        } else {
            console.error('No se encontró el elemento contadorCuarentena');
        }
    },

    /**
     * Actualizar contador de registros de scrap
     */
    updateScrapCount() {
        const count = AppState.scrapData ? AppState.scrapData.length : 0;
        const badge = Utils.getElement('contadorScrap');
        console.log('Actualizando contador scrap:', count, 'Data:', AppState.scrapData);
        if (badge) {
            badge.textContent = count;
        } else {
            console.error('No se encontró el elemento contadorScrap');
        }
    },

    /**
     * Inicializar filtros de tabla
     */
    initializeTableFilters() {
        console.log('Inicializando filtros de tabla...');

        const filterElements = [
            'filtroTablaFechaInicio',
            'filtroTablaFechaFin',
            'filtroTablaArea',
            'filtroTablaRetrabajo',
            'filtroTablaModelo'
        ];

        filterElements.forEach(id => {
            const element = Utils.getElement(id);
            if (element) {
                const eventType = element.tagName === 'INPUT' ? 'input' : 'change';
                element.addEventListener(eventType, () => this.applyTableFilters());
            }
        });
    },

    /**
     * Exportar tabla a CSV
     */
    exportarTabla(tipo) {
        console.log(`Exportando tabla: ${tipo}`);
        
        let data, tipoReporte, columnas;
        
        if (tipo === 'activos') {
            data = AppState.hallazgosData;
            tipoReporte = 'Registros Activos';
            columnas = ['Fecha', 'Área', 'Est.', 'No. Parte', 'Modelo', 'Job Order', 'Defectos'];
        } else if (tipo === 'cuarentena') {
            data = AppState.cuarentenaData;
            tipoReporte = 'Registros en Cuarentena';
            columnas = ['Fecha', 'Área', 'Est.', 'No. Parte', 'Modelo', 'Job Order', 'Defectos'];
        } else if (tipo === 'scrap') {
            data = AppState.scrapData;
            tipoReporte = 'Registros en Scrap';
            columnas = ['Fecha', 'Área', 'Est.', 'No. Parte', 'Modelo', 'Job Order', 'Defectos', 'Fecha Scrap', 'Valor Scrap'];
        }
        
        if (!data || data.length === 0) {
            NotificationManager.show('No hay datos para exportar', 'warning');
            return;
        }

        // Función para extraer solo el número de la estación
        const extraerNumeroEstacion = (estacion) => {
            if (!estacion) return 'N/A';
            const match = estacion.match(/\d+/);
            return match ? match[0] : estacion;
        };

        // Crear metadatos del reporte
        const fechaReporte = new Date().toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Crear encabezado del reporte
        const reportHeader = [
            [`REPORTE DE HALLAZGOS DE CALIDAD`],
            [`Tipo: ${tipoReporte}`],
            [`Fecha de generación: ${fechaReporte}`],
            [`Total de registros: ${data.length}`],
            [''], // Línea vacía
            columnas
        ];

        // Crear filas de datos
        const rows = data.map(item => {
            const baseRow = [
                Utils.formatDate(item.fecha_creacion),
                item.area_ubicacion || 'N/A',
                extraerNumeroEstacion(item.estacion),
                item.no_parte || 'N/A',
                item.modelo || 'N/A',
                item.job_order || 'N/A',
                item.total_defectos || 0
            ];

            if (tipo === 'scrap') {
                baseRow.push(
                    item.fecha_scrap ? Utils.formatDate(item.fecha_scrap) : 'N/A',
                    `$${item.valor_scrap || '0.00'}`
                );
            }

            return baseRow;
        });

        // Agregar línea de resumen al final
        const totalDefectos = data.reduce((sum, item) => sum + (item.total_defectos || 0), 0);
        const resumenFooter = [
            [''], // Línea vacía
            ['RESUMEN:'],
            [`Total de registros: ${data.length}`],
            [`Total de defectos: ${totalDefectos}`],
            [`Promedio de defectos por registro: ${(totalDefectos / data.length).toFixed(2)}`]
        ];

        // Para scrap, agregar el total de dinero perdido
        if (tipo === 'scrap') {
            const totalDineroPerdido = data.reduce((sum, item) => sum + (parseFloat(item.valor_scrap) || 0), 0);
            resumenFooter.push([`Total dinero perdido en scrap: $${totalDineroPerdido.toFixed(2)}`]);
        }

        // Combinar todo el contenido
        const csvContent = [...reportHeader, ...rows, ...resumenFooter]
            .map(row => row.map(cell => `"${cell}"`).join(','))
            .join('\n');

        // Crear nombre de archivo más descriptivo
        const fechaArchivo = new Date().toISOString().split('T')[0];
        const nombreArchivo = `Reporte_Hallazgos_${tipo}_${fechaArchivo}.csv`;

        // Descargar archivo
        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', nombreArchivo);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        NotificationManager.show(`Reporte de ${tipo} exportado correctamente`, 'success');
    },

    /**
     * Mostrar modal de scrap
     */
    async mostrarModalScrap(hallazgoId) {
        console.log('Abriendo modal de scrap para hallazgo:', hallazgoId);
        
        try {
            // Obtener datos completos del hallazgo
            const response = await fetch(`includes/hallazgos_data.php?hallazgo_id=${hallazgoId}`);
            const result = await response.json();
            
            let hallazgoData = null;
            if (result.success && result.data && result.data.length > 0) {
                hallazgoData = result.data[0];
            }
            
            // Llenar campos del modal con datos del hallazgo
            const scrapHallazgoId = Utils.getElement('scrapHallazgoId');
            const scrapModelo = Utils.getElement('scrapModelo');
            const scrapNoParte = Utils.getElement('scrapNoParte');
            const scrapNoEnsamble = Utils.getElement('scrapNoEnsamble');
            const scrapPrecio = Utils.getElement('scrapPrecio');
            const scrapObservaciones = Utils.getElement('scrapObservaciones');
            
            if (scrapHallazgoId) scrapHallazgoId.value = hallazgoId;
            if (scrapModelo) {
                scrapModelo.value = hallazgoData?.modelo || '';
                scrapModelo.readOnly = false;
                scrapModelo.style.backgroundColor = '';
                scrapModelo.required = true;
            }
            if (scrapNoParte) {
                scrapNoParte.value = hallazgoData?.no_parte || '';
                scrapNoParte.readOnly = false;
                scrapNoParte.style.backgroundColor = '';
                scrapNoParte.required = true;
            }
            if (scrapNoEnsamble) {
                scrapNoEnsamble.value = hallazgoData?.no_ensamble || '';
                scrapNoEnsamble.readOnly = false; // El usuario debe llenar este campo
                scrapNoEnsamble.style.backgroundColor = '';
            }
            if (scrapPrecio) {
                scrapPrecio.value = '';
                scrapPrecio.readOnly = false;
                scrapPrecio.style.backgroundColor = '';
                scrapPrecio.focus();
            }
            if (scrapObservaciones) {
                scrapObservaciones.value = '';
                scrapObservaciones.readOnly = false;
                scrapObservaciones.style.backgroundColor = '';
            }
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('scrapModal'));
            modal.show();
            
            // Configurar evento del botón confirmar
            const btnConfirmar = Utils.getElement('confirmarScrap');
            if (btnConfirmar) {
                // Remover listeners anteriores
                btnConfirmar.replaceWith(btnConfirmar.cloneNode(true));
                const nuevoBtn = Utils.getElement('confirmarScrap');
                nuevoBtn.addEventListener('click', () => this.procesarScrap());
            }
            
        } catch (error) {
            console.error('Error al obtener datos del hallazgo:', error);
            // Continuar con valores por defecto
            const scrapHallazgoId = Utils.getElement('scrapHallazgoId');
            const scrapModelo = Utils.getElement('scrapModelo');
            const scrapNoParte = Utils.getElement('scrapNoParte');
            const scrapNoEnsamble = Utils.getElement('scrapNoEnsamble');
            const scrapPrecio = Utils.getElement('scrapPrecio');
            const scrapObservaciones = Utils.getElement('scrapObservaciones');
            
            if (scrapHallazgoId) scrapHallazgoId.value = hallazgoId;
            if (scrapModelo) {
                scrapModelo.value = modelo;
                scrapModelo.readOnly = true;
                scrapModelo.style.backgroundColor = '#f8f9fa';
            }
            if (scrapNoParte) {
                scrapNoParte.value = noParte;
                scrapNoParte.readOnly = true;
                scrapNoParte.style.backgroundColor = '#f8f9fa';
            }
            if (scrapNoEnsamble) {
                scrapNoEnsamble.value = '';
                scrapNoEnsamble.readOnly = false;
                scrapNoEnsamble.style.backgroundColor = '';
            }
            if (scrapPrecio) {
                scrapPrecio.value = '';
                scrapPrecio.focus();
            }
            if (scrapObservaciones) scrapObservaciones.value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('scrapModal'));
            modal.show();
            
            const btnConfirmar = Utils.getElement('confirmarScrap');
            if (btnConfirmar) {
                btnConfirmar.replaceWith(btnConfirmar.cloneNode(true));
                const nuevoBtn = Utils.getElement('confirmarScrap');
                nuevoBtn.addEventListener('click', () => this.procesarScrap());
            }
        }
    },

    /**
     * Procesar envío a scrap
     */
    async procesarScrap() {
        try {
            const scrapHallazgoId = Utils.getElement('scrapHallazgoId');
            const scrapModelo = Utils.getElement('scrapModelo');
            const scrapNoParte = Utils.getElement('scrapNoParte');
            const scrapNoEnsamble = Utils.getElement('scrapNoEnsamble');
            const scrapPrecio = Utils.getElement('scrapPrecio');
            const scrapObservaciones = Utils.getElement('scrapObservaciones');
            
            const formData = {
                hallazgo_id: scrapHallazgoId?.value || '',
                modelo: scrapModelo?.value || '',
                no_parte: scrapNoParte?.value || '',
                no_ensamble: scrapNoEnsamble?.value || '',
                precio: parseFloat(scrapPrecio?.value || '0'),
                observaciones: scrapObservaciones?.value || ''
            };

            // Validar campos requeridos
            if (!formData.modelo || !formData.no_parte || !formData.no_ensamble || !formData.precio) {
                NotificationManager.show('Por favor complete todos los campos requeridos', 'error');
                return;
            }

            if (formData.precio <= 0) {
                NotificationManager.show('El precio debe ser mayor a 0', 'error');
                return;
            }

            // Mostrar loading
            const btnConfirmar = Utils.getElement('confirmarScrap');
            const originalText = btnConfirmar.innerHTML;
            btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';
            btnConfirmar.disabled = true;

            const response = await fetch('includes/procesar_scrap.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                NotificationManager.show('Registro enviado a scrap exitosamente', 'success');
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('scrapModal'));
                modal.hide();
                
                // Actualizar datos
                await this.loadData();
                await DashboardManager.loadDashboardData();
                
            } else {
                NotificationManager.show('Error: ' + result.message, 'error');
            }

        } catch (error) {
            console.error('Error al procesar scrap:', error);
            NotificationManager.show('Error al procesar el scrap', 'error');
        } finally {
            // Restaurar botón
            const btnConfirmar = Utils.getElement('confirmarScrap');
            if (btnConfirmar) {
                btnConfirmar.innerHTML = '<i class="fas fa-trash me-1"></i> Confirmar Scrap';
                btnConfirmar.disabled = false;
            }
        }
    }
};

/**
 * ============================================================================
 * INICIALIZACIÓN Y EVENTOS PRINCIPALES
 * ============================================================================
 */

const AppInitializer = {
    /**
     * Inicializar la aplicación
     */
    init() {
        console.log('Inicializando Admin Dashboard...');

        // Configurar fechas por defecto
        DateManager.setDefaultDates();
        DateManager.setMaxDateToToday();

        // Cargar datos del dashboard
        DashboardManager.loadDashboardData();

        // Configurar auto-refresh
        setInterval(() => DashboardManager.refreshData(), AppConfig.AUTO_REFRESH_INTERVAL);

        // Configurar eventos de validación de fechas
        const fechaInicio = Utils.getElement('fechaInicio');
        const fechaFin = Utils.getElement('fechaFin');
        
        if (fechaInicio) {
            fechaInicio.addEventListener('change', DateManager.validateDateRange);
        }
        if (fechaFin) {
            fechaFin.addEventListener('change', DateManager.validateDateRange);
        }

        // Inicializar sección de hallazgos si existe
        if (Utils.elementExists('tablaRegistrosActivosBody')) {
            console.log('Tabla de hallazgos encontrada, inicializando...');
            HallazgosManager.initializeTableFilters();
            HallazgosManager.loadData();
        } else {
            console.log('Tabla de hallazgos no encontrada');
        }

        // Configurar event listeners para botones de scrap
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-scrap')) {
                e.preventDefault();
                const hallazgoId = e.target.dataset.hallazgoId;
                
                HallazgosManager.mostrarModalScrap(parseInt(hallazgoId));
            }
        });

        console.log('Admin Dashboard inicializado correctamente');
    }
};

/**
 * ============================================================================
 * FUNCIONES GLOBALES (PARA COMPATIBILIDAD CON HTML)
 * ============================================================================
 */

// Funciones expuestas globalmente para el HTML
window.setQuickRange = (range) => ButtonManager.setQuickRange(range);
window.clearDates = () => DateManager.clearDates();
window.aplicarFiltros = () => DashboardManager.applyFilters();
window.aplicarFiltrosTabla = () => HallazgosManager.applyTableFilters();
window.limpiarFiltros = () => HallazgosManager.clearTableFilters();
window.refreshData = () => DashboardManager.refreshData();

// Alias para el HallazgosManager (para acceso desde HTML)
window.HallazgosManager = HallazgosManager;

/**
 * ============================================================================
 * FUNCIONES GLOBALES PARA ACCESO DESDE HTML
 * ============================================================================
 */

// Funciones globales para el HallazgosManager
function aplicarFiltros() {
    DashboardManager.applyFilters();
}

function setQuickRange(range) {
    ButtonManager.setQuickRange(range);
}

function clearDates() {
    DateManager.clearDates();
}

function aplicarFiltrosTabla() {
    HallazgosManager.applyTableFilters();
}

function limpiarFiltros() {
    HallazgosManager.clearTableFilters();
}

function exportarTabla(tipo) {
    HallazgosManager.exportarTabla(tipo);
}

function refreshData() {
    DashboardManager.refreshData();
}

// Funciones globales específicas para hallazgos
window.HallazgosManager = HallazgosManager;

/**
 * ============================================================================
 * INICIO DE LA APLICACIÓN
 * ============================================================================
 */

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    AppInitializer.init();
});

console.log('Admin Dashboard Script v2.0 - Cargado correctamente');
