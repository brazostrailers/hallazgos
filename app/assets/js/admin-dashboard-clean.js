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
        estacionesScrap: null,
        trend: null
    },
    chartModes: {
        scrap: 'mensual',      // 'mensual' | 'semanal'
        estaciones: 'mensual', // date window for estaciones chart
        partes: 'mensual'      // date window for partes chart
    },
    isLoading: false
};

// Registrar plugin de datalabels si está disponible
try {
    if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }
} catch (e) {
    // Ignorar si no está disponible; se mostrará sin etiquetas
}

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

    /** Obtener rango de semana actual (Lunes a Domingo) en YYYY-MM-DD */
    getCurrentWeekRange() {
        const now = new Date();
        const day = now.getDay(); // 0=Dom,1=Lun,...
        const diffToMonday = (day === 0 ? -6 : 1) - day; // mover a lunes
        const monday = new Date(now);
        monday.setDate(now.getDate() + diffToMonday);
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        const toStr = (d) => d.toISOString().split('T')[0];
        return { start: toStr(monday), end: toStr(sunday) };
    },

    /** Formatea fecha local a YYYY-MM-DD (sin efectos de zona horaria) */
    toLocalYMD(d) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    },

    /** Obtener rango del mes actual (1 al último día) en YYYY-MM-DD */
    getCurrentMonthRange() {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        return { start: this.toLocalYMD(start), end: this.toLocalYMD(end) };
    },

    /** Obtener rango de un mes específico YYYY-MM en YYYY-MM-DD */
    getMonthRange(yyyyMm) {
        if (!yyyyMm || !/^\d{4}-\d{2}$/.test(yyyyMm)) return null;
        const [y, m] = yyyyMm.split('-').map(n => parseInt(n, 10));
        const start = new Date(y, m - 1, 1);
        const end = new Date(y, m, 0);
        return { start: this.toLocalYMD(start), end: this.toLocalYMD(end) };
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
            cuarentena: parseInt(stats.cuarentena) || 0,
            total_piezas: parseInt(stats.total_piezas) || 0,
            piezas_defectuosas: parseInt(stats.piezas_defectuosas) || 0,
            piezas_retrabajo: parseInt(stats.piezas_retrabajo) || 0,
            piezas_cuarentena: parseInt(stats.piezas_cuarentena) || 0
        };

        // Actualizar contadores principales
        Utils.animateNumber('totalRegistros', safeStats.total);
        Utils.animateNumber('registrosConHallazgos', safeStats.con_hallazgos);
        Utils.animateNumber('registrosRetrabajo', safeStats.retrabajo);
        Utils.animateNumber('registrosCuarentena', safeStats.cuarentena);

        // Actualizar contadores de piezas
        Utils.animateNumber('totalPiezas', safeStats.total_piezas);
        Utils.animateNumber('piezasDefectuosas', safeStats.piezas_defectuosas);
        Utils.animateNumber('piezasRetrabajo', safeStats.piezas_retrabajo);
        Utils.animateNumber('piezasCuarentena', safeStats.piezas_cuarentena);

        // Calcular métricas adicionales
        this.updateAdditionalMetrics(safeStats);
    },

    /**
     * Actualizar métricas adicionales calculadas
     */
    updateAdditionalMetrics(stats) {
        // Porcentaje de piezas defectuosas
        const porcentajeDefectuosas = stats.total_piezas > 0 ? 
            Math.round((stats.piezas_defectuosas / stats.total_piezas) * 100) : 0;
        
        Utils.animateNumber('porcentajeDefectuosas', porcentajeDefectuosas);
        
        // Actualizar barra de progreso
        const progressBar = Utils.getElement('progressDefectuosas');
        if (progressBar) {
            setTimeout(() => {
                progressBar.style.width = porcentajeDefectuosas + '%';
                progressBar.setAttribute('aria-valuenow', porcentajeDefectuosas);
            }, 500);
        }

        // Promedio de piezas por hallazgo
        const promedioPiezas = stats.con_hallazgos > 0 ? 
            (stats.piezas_defectuosas / stats.con_hallazgos).toFixed(1) : 0;
        
        const promedioElement = Utils.getElement('promedioPiezas');
        if (promedioElement) {
            promedioElement.textContent = promedioPiezas;
        }

        // Piezas sin defectos (eficiencia)
        const piezasOK = stats.total_piezas - stats.piezas_defectuosas;
        const porcentajeOK = stats.total_piezas > 0 ? 
            Math.round((piezasOK / stats.total_piezas) * 100) : 0;

        Utils.animateNumber('piezasOK', piezasOK);
        Utils.animateNumber('porcentajeOK', porcentajeOK);

        // Tendencia (se calculará cuando tengamos datos de tendencia)
        this.updateTrendMetric();
    },

    /**
     * Actualizar métrica de tendencia
     */
    updateTrendMetric() {
        // Por ahora, mostrar un placeholder
        // Esta función se actualizará cuando tengamos datos de tendencia
        const tendenciaValor = Utils.getElement('tendenciaValor');
        const tendenciaTexto = Utils.getElement('tendenciaTexto');
        const tendenciaIcon = Utils.getElement('tendenciaIcon');

        if (tendenciaValor && tendenciaTexto && tendenciaIcon) {
            tendenciaValor.textContent = '7d';
            tendenciaTexto.innerHTML = '<i class="fas fa-chart-line me-1"></i><span class="text-info">Últimos 7 días</span>';
            tendenciaIcon.className = 'fas fa-chart-line';
        }
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
        // Gráficos híbridos (hallazgos + piezas)
        if (charts.areas?.labels?.length > 0) {
            AppState.charts.area = this.createHybridChart('chartAreas', charts.areas, 'Piezas Afectadas por Área', 'doughnut');
        } else {
            this.showEmptyChart('chartAreas', 'No hay datos de áreas disponibles');
        }

        if (charts.modelos?.labels?.length > 0) {
            AppState.charts.modelo = this.createHybridChart('chartModelos', charts.modelos, 'Impacto por Modelo', 'bar');
        } else {
            this.showEmptyChart('chartModelos', 'No hay datos de modelos disponibles');
        }

        if (charts.usuarios?.labels?.length > 0) {
            AppState.charts.usuario = this.createHybridChart('chartUsuarios', charts.usuarios, 'Eficiencia por Usuario', 'pie');
        } else {
            this.showEmptyChart('chartUsuarios', 'No hay datos de usuarios disponibles');
        }

        if (charts.no_parte?.labels?.length > 0) {
            AppState.charts.noParte = this.createHybridChart('chartNoParte', charts.no_parte, 'Impacto por No. Parte', 'bar');
        } else {
            this.showEmptyChart('chartNoParte', 'No hay datos de no. de parte disponibles');
        }

        if (charts.defectos?.labels?.length > 0) {
            AppState.charts.defectos = this.createHybridChart('chartDefectos', charts.defectos, 'Impacto por Defectos', 'bar', true);
        } else {
            this.showEmptyChart('chartDefectos', 'No hay datos de defectos disponibles');
        }

        // Cargar gráfica de scrap
        this.loadScrapChart();
        
        // Cargar gráfica de estaciones con scrap
        this.loadEstacionesScrapChart();
        
    // Cargar gráfica de partes con más scrap
    this.loadPartesScrapChart();
    // Cargar observaciones top en scrap
    this.loadScrapObservacionesChart();
    },

    /**
     * Crear gráfico híbrido que muestra tanto hallazgos como piezas
     */
    createHybridChart(canvasId, data, title, chartType, isHorizontal = false) {
        const ctx = Utils.getElement(canvasId);
        if (!ctx) {
            console.warn(`Canvas ${canvasId} no encontrado`);
            return null;
        }

        // Destruir gráfico existente si existe
        if (AppState.charts[canvasId]) {
            AppState.charts[canvasId].destroy();
        }

        // Determinar si mostrar ambas métricas o solo piezas según el tipo de gráfico
        const showBothMetrics = chartType === 'bar' || isHorizontal;
        
        let datasets;
        if (showBothMetrics && data.piezas) {
        // Gráfico con dos datasets (registros + piezas)
            datasets = [
                {
            label: 'Registros',
                    data: data.data || [],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Piezas Afectadas',
                    data: data.piezas || [],
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ];
        } else {
            // Gráfico simple mostrando solo piezas para doughnut y pie
            datasets = [{
                label: 'Piezas Afectadas',
                data: data.piezas || data.data || [],
                backgroundColor: this.generateColors(data.labels?.length || 0),
                borderWidth: 1
            }];
        }

        // Plugin para texto al centro (solo doughnut)
        const centerTextPlugin = (chartType === 'doughnut') ? {
            id: `centerText_${canvasId}`,
            afterDraw(c) {
                const ds0 = c.config.data?.datasets?.[0];
                if (!ds0) return;
                const total = (ds0.data || []).reduce((a, b) => a + (Number(b) || 0), 0);
                const { ctx } = c;
                const x = c.chartArea.left + (c.chartArea.right - c.chartArea.left) / 2;
                const y = c.chartArea.top + (c.chartArea.bottom - c.chartArea.top) / 2;
                ctx.save();
                ctx.fillStyle = '#111';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = '600 14px Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial';
                ctx.fillText(`${Number(total).toLocaleString('es-MX')} piezas`, x, y);
                ctx.restore();
            }
        } : null;

        const config = {
            type: chartType,
            data: {
                labels: data.labels || [],
                datasets: datasets
            },
            plugins: centerTextPlugin ? [centerTextPlugin] : [],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: isHorizontal ? 'y' : 'x',
                layout: {
                    padding: {
                        top: 32,   // espacio extra para que no choquen labels con el título
                        right: 10,
                        bottom: 10,
                        left: 10
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: chartType === 'doughnut' || chartType === 'pie' ? 'bottom' : 'top',
                        labels: { usePointStyle: true, boxWidth: 10 }
                    },
                    title: {
                        display: true,
                        text: title,
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    datalabels: (chartType === 'doughnut' || chartType === 'pie') ? {
                        color: '#212529',
                        font: { weight: 'bold' },
                        anchor: 'outer',
                        align: 'outer',
                        offset: 12,
                        clamp: true,
                        clip: false,
                        formatter: (value, ctx) => {
                            const ds = ctx.chart.data.datasets[ctx.datasetIndex];
                            const total = (ds.data || []).reduce((a, b) => a + (Number(b) || 0), 0);
                            const pct = total ? (value / total) * 100 : 0;
                            const val = Number(value);
                            const valFmt = val >= 1000 ? `${(val/1000).toFixed(2)} mil` : `${val}`;
                            return `${valFmt} (${pct.toFixed(2)}%)`;
                        }
                    } : undefined,
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const val = Number(context.parsed) || 0;
                                const ds = context.dataset.data;
                                const total = (ds || []).reduce((a, b) => a + (Number(b) || 0), 0);
                                const pct = total ? ((val / total) * 100).toFixed(2) : '0.00';
                                return `${context.label}: ${val.toLocaleString('es-MX')} piezas (${pct}%)`;
                            },
                            afterLabel: (context) => {
                                const index = context.dataIndex;
                                if (data.promedio && data.promedio[index]) {
                                    return `Promedio: ${data.promedio[index]} piezas/hallazgo`;
                                }
                                return '';
                            }
                        }
                    }
                },
                cutout: chartType === 'doughnut' ? '60%' : undefined,
                scales: chartType === 'bar' ? {
                    [isHorizontal ? 'x' : 'y']: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                } : undefined
            }
        };

    const chart = new Chart(ctx, config);
        AppState.charts[canvasId] = chart;
        
        // Remover indicador de carga
        this.hideChartLoading(canvasId);
        
        return chart;
    },

    /**
     * Generar colores para gráficos
     */
    generateColors(count) {
        const colors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 205, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)',
            'rgba(83, 102, 255, 0.7)',
            'rgba(255, 99, 255, 0.7)',
            'rgba(99, 255, 132, 0.7)'
        ];
        
        return Array(count).fill().map((_, i) => colors[i % colors.length]);
    },

    /**
     * Ocultar indicador de carga de un gráfico específico
     */
    hideChartLoading(canvasId) {
        const canvas = Utils.getElement(canvasId);
        if (canvas) {
            const container = canvas.parentElement;
            const loadingElement = container.querySelector('.chart-loading');
            if (loadingElement) {
                loadingElement.remove();
            }
        }
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
        // Remover overlays de vacío si quedaron de una carga previa
        const emptyOverlay = container.querySelector('.chart-empty');
        if (emptyOverlay) {
            emptyOverlay.remove();
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
            // Construir parámetros desde AppState o inputs si existen
            const params = new URLSearchParams();
            const tipo = 'mensual'; // siempre mensual para la gráfica principal
            params.set('tipo', tipo);

            const fi = AppState.currentFilters.fecha_inicio || Utils.getElement('fechaInicio')?.value || '';
            const ff = AppState.currentFilters.fecha_fin || Utils.getElement('fechaFin')?.value || '';
            const area = AppState.currentFilters.area || Utils.getElement('filtroArea')?.value || '';
            if (fi) params.set('fecha_inicio', fi);
            if (ff) params.set('fecha_fin', ff);
            if (area) params.set('area', area);

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

            // Limpiar overlays previos si existen
            const scrapCanvas = Utils.getElement('chartScrap');
            if (scrapCanvas && scrapCanvas.parentElement) {
                const empty = scrapCanvas.parentElement.querySelector('.chart-empty');
                if (empty) empty.remove();
                const loading = scrapCanvas.parentElement.querySelector('.chart-loading');
                if (loading) loading.remove();
            }

            const chartData = {
                labels: temporalData.map(item => {
                    // Solo usar formato mes abreviado cuando viene como YYYY-MM
                    const periodo = String(item.periodo || '');
                    if (/^\d{4}-\d{2}$/.test(periodo)) {
                        const [year, month] = periodo.split('-');
                        const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                                          'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                        return `${monthNames[Math.max(0, Math.min(11, parseInt(month, 10) - 1))]} ${year}`;
                    }
                    // En semanal o formatos distintos, usar la etiqueta tal cual
                    return periodo;
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
     * Gráfica semanal de dinero perdido en scrap (con selector de mes propio)
     */
    async loadScrapWeeklyChart() {
        try {
            const monthEl = Utils.getElement('scrapWeekMonth');
            const yyyyMm = monthEl?.value;
            if (!yyyyMm) {
                this.showEmptyChart('chartScrapWeekly', 'Selecciona un mes para ver sus semanas');
                return;
            }
            const range = Utils.getMonthRange(yyyyMm);
            if (!range) {
                this.showEmptyChart('chartScrapWeekly', 'Mes inválido');
                return;
            }

            const params = new URLSearchParams();
            params.set('tipo', 'semanal');
            params.set('fecha_inicio', range.start);
            params.set('fecha_fin', range.end);

            const response = await fetch(`includes/scrap_data.php?${params.toString()}`);
            if (!response.ok) {
                this.showEmptyChart('chartScrapWeekly', `Error del servidor (${response.status})`);
                return;
            }
            let result;
            try {
                result = await response.json();
            } catch (e) {
                this.showEmptyChart('chartScrapWeekly', 'Respuesta inválida del servidor');
                return;
            }
            if (!result.success) {
                const msg = result.error || result.message || 'Error al cargar datos de scrap';
                this.showEmptyChart('chartScrapWeekly', msg);
                return;
            }

            const data = result.temporal || [];
            if (!data.length) {
                this.showEmptyChart('chartScrapWeekly', 'No hay datos de scrap para ese mes');
                return;
            }

            const canvas = Utils.getElement('chartScrapWeekly');
            if (!canvas) return;
            const parent = canvas.parentElement;
            parent?.querySelector('.chart-empty')?.remove();
            parent?.querySelector('.chart-loading')?.remove();

            if (AppState.charts.scrapWeekly) {
                AppState.charts.scrapWeekly.destroy();
            }

            const chartData = {
                labels: data.map(it => String(it.periodo || '')),
                datasets: [
                    {
                        label: 'Dinero Perdido ($USD)',
                        data: data.map(it => parseFloat(it.total_periodo) || 0),
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220,53,69,0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Cantidad de Registros',
                        data: data.map(it => parseInt(it.cantidad_registros) || 0),
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255,193,7,0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1'
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
                        legend: { display: true, position: 'top' },
                        title: { display: true, text: 'Dinero Perdido por Semana', font: { size: 16, weight: 'bold' } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (v) => '$' + Number(v).toLocaleString('en-US') },
                            title: { display: true, text: 'Dinero Perdido (USD)' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { stepSize: 1 },
                            title: { display: true, text: 'Cantidad de Registros' }
                        }
                    }
                }
            };

            const ctx = canvas.getContext('2d');
            AppState.charts.scrapWeekly = new Chart(ctx, config);

        } catch (e) {
            console.error('Error semanal scrap:', e);
            this.showEmptyChart('chartScrapWeekly', 'Error al cargar datos de scrap');
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
    },

    /**
     * Cargar gráfica de estaciones con más dinero perdido por scrap
     */
    async loadEstacionesScrapChart() {
        try {
            const params = new URLSearchParams();
            // Rango por mes (selector propio)
            const monthEl = Utils.getElement('estacionesMonth');
            const range = monthEl && monthEl.value ? Utils.getMonthRange(monthEl.value) : Utils.getCurrentMonthRange();
            if (!range) {
                this.showEmptyChart('chartEstacionesScrap', 'Selecciona un mes');
                return;
            }
            params.set('fecha_inicio', range.start);
            params.set('fecha_fin', range.end);

            const url = `includes/scrap_estaciones_data.php?${params.toString()}`;

            const response = await fetch(url);
            
            if (!response.ok) {
                this.showEmptyChart('chartEstacionesScrap', 'Error al cargar datos de estaciones');
                return;
            }

            const result = await response.json();
            
            if (!result.success) {
                this.showEmptyChart('chartEstacionesScrap', result.message || 'Error al cargar datos');
                return;
            }

            if (!result.data || !result.data.labels || result.data.labels.length === 0) {
                this.showEmptyChart('chartEstacionesScrap', 'No hay datos de estaciones con scrap disponibles');
                return;
            }

            const chartData = result.data;

            // Configuración para Chart.js v3+ con barras horizontales
            const chartConfig = {
                type: 'bar',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y', // Esto hace que sea horizontal
                    elements: {
                        bar: {
                            borderWidth: 1,
                            borderRadius: 6,
                            barThickness: 20,      // más delgado para que quepan más etiquetas en 300px
                            maxBarThickness: 26
                        }
                    },
                    layout: {
                        padding: { left: 12, right: 12 }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Estaciones con Más Dinero Perdido por Scrap',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        },
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed.x;
                                    const estacion = context.label;
                                    return `${estacion}: $${value.toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('en-US', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            },
                            title: {
                                display: true,
                                text: 'Dinero Perdido (USD)'
                            }
                        },
                        y: {
                            ticks: {
                                autoSkip: false,     // mostrar todas las etiquetas (evita que se oculte "Prensas")
                                maxTicksLimit: 20,   // por seguridad, permitir hasta 20 por si hay menos espacio
                                font: { size: 11 }
                            },
                            title: {
                                display: true,
                                text: 'Estaciones'
                            }
                        }
                    }
                }
            };

            // Crear la gráfica directamente en lugar de usar updateOrCreateChart
            const canvas = Utils.getElement('chartEstacionesScrap');
            if (canvas) {
                const ctx = canvas.getContext('2d');
                // Limpiar overlays previos si existen
                const parent = canvas.parentElement;
                if (parent) {
                    const empty = parent.querySelector('.chart-empty');
                    if (empty) empty.remove();
                    const loading = parent.querySelector('.chart-loading');
                    if (loading) loading.remove();
                }
                
                // Destruir gráfica anterior si existe
                if (AppState.charts.estacionesScrap) {
                    AppState.charts.estacionesScrap.destroy();
                }
                
                // Crear nueva gráfica
                AppState.charts.estacionesScrap = new Chart(ctx, chartConfig);
            } else {
                console.error('No se encontró el canvas chartEstacionesScrap');
            }

            // Actualizar estadísticas de estaciones
            this.updateEstacionesScrapStats(result.resumen, result.resumen_areas);

        } catch (error) {
            console.error('Error al cargar gráfica de estaciones con scrap:', error);
            this.showEmptyChart('chartEstacionesScrap', 'Error al cargar datos de estaciones');
        }
    },

    /**
     * Cargar gráfica de números de parte con más scrap (con área)
     */
    async loadPartesScrapChart() {
        try {
            const params = new URLSearchParams();
            // Rango por mes (selector propio)
            const monthEl = Utils.getElement('partesMonth');
            const range = monthEl && monthEl.value ? Utils.getMonthRange(monthEl.value) : Utils.getCurrentMonthRange();
            if (!range) {
                this.showEmptyChart('chartPartesScrap', 'Selecciona un mes');
                return;
            }
            params.set('fecha_inicio', range.start);
            params.set('fecha_fin', range.end);

            const res = await fetch(`includes/scrap_partes_data.php?${params.toString()}`);
            if (!res.ok) {
                this.showEmptyChart('chartPartesScrap', 'Error al cargar datos de partes');
                return;
            }
            const result = await res.json();
            if (!result.success || !result.data?.labels?.length) {
                this.showEmptyChart('chartPartesScrap', 'No hay datos de partes con scrap');
                return;
            }

            const canvas = Utils.getElement('chartPartesScrap');
            if (canvas) {
                const parent = canvas.parentElement;
                const empty = parent?.querySelector('.chart-empty');
                if (empty) empty.remove();
                const loading = parent?.querySelector('.chart-loading');
                if (loading) loading.remove();

                // Destruir si existe
                if (AppState.charts.partesScrap) {
                    AppState.charts.partesScrap.destroy();
                }

                const ctx = canvas.getContext('2d');
                AppState.charts.partesScrap = new Chart(ctx, {
                    type: 'bar',
                    data: result.data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        elements: {
                            bar: {
                                borderWidth: 1,
                                borderRadius: 6,
                                barThickness: 28,
                                maxBarThickness: 34
                            }
                        },
                        layout: { padding: { right: 28 } },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Números de Parte con Mayor Scrap',
                                font: { size: 16, weight: 'bold' }
                            },
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ` $${Number(ctx.parsed.x).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (v) => '$' + Number(v).toLocaleString('en-US')
                                },
                                title: { display: true, text: 'Dinero Perdido (USD)' }
                            },
                            y: {
                                title: { display: true, text: 'No. de Parte (Área)' },
                                ticks: {
                                    autoSkip: false,
                                    font: { size: 11 }
                                }
                            }
                        }
                    }
                });
            }

            // Resumen
            const totalEl = Utils.getElement('totalPartesPerdido');
            if (totalEl && result.resumen?.total_perdido_general !== undefined) {
                totalEl.textContent = '$' + Number(result.resumen.total_perdido_general).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

        } catch (e) {
            console.error('Error al cargar partes scrap:', e);
            this.showEmptyChart('chartPartesScrap', 'Error al cargar datos de partes');
        }
    },

    /**
     * Cargar gráfica de números de parte por cantidad de piezas en scrap
     */
    async loadPartesPiezasScrapChart() {
        try {
            const params = new URLSearchParams();
            const monthEl = Utils.getElement('partesPiezasMonth');
            const range = monthEl && monthEl.value ? Utils.getMonthRange(monthEl.value) : Utils.getCurrentMonthRange();
            if (!range) {
                this.showEmptyChart('chartPartesPiezasScrap', 'Selecciona un mes');
                return;
            }
            params.set('fecha_inicio', range.start);
            params.set('fecha_fin', range.end);

            const res = await fetch(`includes/scrap_partes_piezas_data.php?${params.toString()}`);
            if (!res.ok) {
                this.showEmptyChart('chartPartesPiezasScrap', 'Error al cargar datos');
                return;
            }
            const result = await res.json();
            if (!result.success || !result.data?.labels?.length) {
                this.showEmptyChart('chartPartesPiezasScrap', 'No hay datos');
                const totalEl = Utils.getElement('totalPartesPiezas');
                if (totalEl) totalEl.textContent = '0 piezas';
                return;
            }

            const canvas = Utils.getElement('chartPartesPiezasScrap');
            if (canvas) {
                const parent = canvas.parentElement;
                const empty = parent?.querySelector('.chart-empty');
                if (empty) empty.remove();
                const loading = parent?.querySelector('.chart-loading');
                if (loading) loading.remove();

                if (AppState.charts.partesPiezasScrap) AppState.charts.partesPiezasScrap.destroy();

                const ctx = canvas.getContext('2d');
                AppState.charts.partesPiezasScrap = new Chart(ctx, {
                    type: 'bar',
                    data: result.data,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        elements: {
                            bar: {
                                borderWidth: 1,
                                borderRadius: 6,
                                barThickness: 28,
                                maxBarThickness: 34
                            }
                        },
                        layout: { padding: { right: 28 } },
                        plugins: {
                            title: { 
                                display: true, 
                                text: 'Números de Parte por Cantidad de Piezas en Scrap', 
                                font: { size: 16, weight: 'bold' }
                            },
                            subtitle: {
                                display: true,
                                text: () => {
                                    const total = result?.resumen?.total_piezas_general ?? 0;
                                    return `Total: ${Number(total).toLocaleString('es-MX')} piezas`;
                                },
                                color: '#6c757d',
                                font: { size: 12 }
                            },
                            legend: { display: false },
                            datalabels: {
                                anchor: 'end',
                                align: 'right',
                                color: '#212529',
                                font: { weight: 'bold' },
                                offset: 6,
                                formatter: (value) => `${Number(value).toLocaleString('es-MX')}`,
                                clip: false,
                                clamp: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ` ${Number(ctx.parsed.x).toLocaleString('es-MX')} piezas`
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { callback: (v) => Number(v).toLocaleString('es-MX') + ' piezas' },
                                title: { display: true, text: 'Piezas' }
                            },
                            y: {
                                title: { display: true, text: 'No. de Parte (Área)' },
                                ticks: {
                                    autoSkip: false,
                                    font: { size: 11 }
                                }
                            }
                        }
                    }
                });
            }

            const totalEl = Utils.getElement('totalPartesPiezas');
            if (totalEl && result.resumen?.total_piezas_general !== undefined) {
                totalEl.textContent = `${Number(result.resumen.total_piezas_general).toLocaleString('es-MX')} piezas`;
            }

        } catch (e) {
            console.error('Error al cargar partes por piezas:', e);
            this.showEmptyChart('chartPartesPiezasScrap', 'Error al cargar datos');
        }
    },

    /**
     * Cargar gráfica de observaciones más frecuentes en registros de scrap (Top 5)
     */
    async loadScrapObservacionesChart() {
        try {
            const params = new URLSearchParams();
            const monthEl = Utils.getElement('obsMonth');
            const range = monthEl && monthEl.value ? Utils.getMonthRange(monthEl.value) : Utils.getCurrentMonthRange();
            if (!range) {
                this.showEmptyChart('chartObsScrap', 'Selecciona un mes');
                return;
            }
            params.set('fecha_inicio', range.start);
            params.set('fecha_fin', range.end);

            const res = await fetch(`includes/scrap_observaciones_data.php?${params.toString()}`);
            if (!res.ok) {
                this.showEmptyChart('chartObsScrap', 'Error al cargar observaciones');
                return;
            }
            const result = await res.json();
            if (!result.success || !result.data?.labels?.length) {
                this.showEmptyChart('chartObsScrap', 'No hay observaciones registradas');
                const totalEl = Utils.getElement('totalObsScrap');
                if (totalEl) totalEl.textContent = '0';
                return;
            }

            const canvas = Utils.getElement('chartObsScrap');
            if (!canvas) return;
            const parent = canvas.parentElement;
            parent?.querySelector('.chart-empty')?.remove();
            parent?.querySelector('.chart-loading')?.remove();

            if (AppState.charts.obsScrap) {
                AppState.charts.obsScrap.destroy();
            }

            const ctx = canvas.getContext('2d');
            AppState.charts.obsScrap = new Chart(ctx, {
                type: 'bar',
                data: result.data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    elements: { bar: { borderRadius: 6, barThickness: 26, maxBarThickness: 32 } },
                    layout: { padding: { right: 24 } },
                    plugins: {
                        title: { display: true, text: 'Observaciones más frecuentes (Top 5)', font: { size: 16, weight: 'bold' } },
                        legend: { display: false },
                        tooltip: { callbacks: { label: (ctx) => ` ${Number(ctx.parsed.x).toLocaleString('es-MX')} piezas` } }
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { stepSize: 1, callback: (v) => Number(v).toLocaleString('es-MX') }, title: { display: true, text: 'Piezas' } },
                        y: { ticks: { autoSkip: false, font: { size: 11 } }, title: { display: true, text: 'Observación' } }
                    }
                }
            });

            const totalEl = Utils.getElement('totalObsScrap');
            if (totalEl && result.resumen?.total_piezas !== undefined) {
                totalEl.textContent = `${Number(result.resumen.total_piezas).toLocaleString('es-MX')} piezas`;
            }
        } catch (e) {
            console.error('Error al cargar observaciones de scrap:', e);
            this.showEmptyChart('chartObsScrap', 'Error al cargar observaciones');
        }
    },

    /**
     * Actualizar estadísticas de estaciones con scrap
     */
    updateEstacionesScrapStats(resumen, resumenAreas) {
        // Actualizar total perdido en estaciones
        const totalEstacionesPerdido = Utils.getElement('totalEstacionesPerdido');
        if (totalEstacionesPerdido && resumen.total_perdido_general) {
            totalEstacionesPerdido.textContent = '$' + Number(resumen.total_perdido_general).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Actualizar resumen por áreas
        const resumenAreasContainer = Utils.getElement('resumenAreasScrap');
        if (resumenAreasContainer && resumenAreas && resumenAreas.length > 0) {
            let html = '';
            
            resumenAreas.forEach(area => {
                const porcentaje = resumen.total_perdido_general > 0 ? 
                    (area.total_perdido / resumen.total_perdido_general * 100).toFixed(1) : 0;
                
                html += `
                    <div class="col-md-3 mb-2">
                        <div class="card border-left-danger shadow-sm h-100">
                            <div class="card-body p-3">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            ${area.area}
                                        </div>
                                        <div class="h6 mb-0 font-weight-bold text-gray-800">
                                            $${Number(area.total_perdido).toLocaleString('en-US', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            })}
                                        </div>
                                        <div class="text-xs text-muted">
                                            ${area.total_estaciones} estación(es) • ${porcentaje}%
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-industry fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            resumenAreasContainer.innerHTML = html;
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
            // Cargar datos activos e inactivos (excluye cuarentena, scrap y cerradas)
            const activosResponse = await fetch('includes/hallazgos_data.php?' + new URLSearchParams({
                ...filtros,
                estado: 'activo,inactivo'
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
                    <td colspan="${estado === 'cuarentena' ? '11' : '12'}" class="text-center py-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No se encontraron registros</p>
                    </td>
                </tr>
            `;
            return;
        }

        // Calcular offset por si en el futuro hay paginación
        const offset = 0;

        const rows = data.map((hallazgo, idx) => {
            const numero = offset + idx + 1;
            const baseColumns = `
                <td><strong>${numero}</strong></td>
                <td>${Utils.formatDate(hallazgo.fecha_creacion)}</td>
                <td>${hallazgo.area_ubicacion || 'N/A'}</td>
                <td>${hallazgo.modelo || 'N/A'}</td>
                <td>${hallazgo.no_parte || 'N/A'}</td>
                <td>${hallazgo.job_order || 'N/A'}</td>
                <td>${hallazgo.usuario_nombre || 'N/A'}</td>
                <td><span class="badge bg-primary">${hallazgo.cantidad_piezas || 0}</span></td>
            `;

            if (estado === 'activo') {
                return `
                    <tr data-id="${hallazgo.id}">
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
                    <tr data-id="${hallazgo.id}">
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
                    <td colspan="12" class="text-center py-4">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No se encontraron registros de scrap</p>
                    </td>
                </tr>
            `;
            return;
        }

        const offset = 0;
        const rows = data.map((scrap, idx) => {
            const numero = offset + idx + 1;
            return `
                <tr data-id="${scrap.id}">
                    <td><strong>${numero}</strong></td>
                    <td>${Utils.formatDate(scrap.fecha_creacion)}</td>
                    <td>${scrap.area_ubicacion || 'N/A'}</td>
                    <td>${scrap.modelo || 'N/A'}</td>
                    <td>${scrap.no_parte || 'N/A'}</td>
                    <td>${scrap.job_order || 'N/A'}</td>
                    <td>${scrap.usuario_nombre || 'N/A'}</td>
                    <td><span class="badge bg-primary">${scrap.cantidad_piezas || 0}</span></td>
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
        
        // Si retrabajo es "No", mostrar botones para retrabajo, cuarentena, scrap y cerrar
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
                <button class="btn btn-sm btn-primary" onclick="HallazgosManager.mostrarModalCerrar(${item.id})" title="Cerrar hallazgo">
                    <i class="fas fa-lock"></i> Cerrar
                </button>
            `);
        }
        
        // Si retrabajo es "Si", mostrar botones para cuarentena, scrap, cerrar y quitar retrabajo
        if (item.retrabajo === 'Si') {
            buttons.push(`
                <button class="btn btn-sm btn-danger" onclick="HallazgosManager.cambiarEstado(${item.id}, 'cuarentena')" title="Enviar a cuarentena">
                    <i class="fas fa-exclamation-triangle"></i> Cuarentena
                </button>
                <button class="btn btn-sm btn-dark" onclick="HallazgosManager.mostrarModalScrap(${item.id})" title="Enviar a scrap">
                    <i class="fas fa-trash"></i> Scrap
                </button>
                <button class="btn btn-sm btn-primary" onclick="HallazgosManager.mostrarModalCerrar(${item.id})" title="Cerrar hallazgo">
                    <i class="fas fa-lock"></i> Cerrar
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

    /** Mostrar modal para cerrar hallazgo */
    mostrarModalCerrar(hallazgoId) {
        const modalEl = Utils.getElement('cerrarModal');
        const modal = new bootstrap.Modal(modalEl);
        const idSpan = Utils.getElement('cerrarHallazgoId');
        const fechaInput = Utils.getElement('cerrarFecha');
        const solucionInput = Utils.getElement('cerrarSolucion');
        const btn = Utils.getElement('confirmarCierre');

        if (idSpan) idSpan.textContent = hallazgoId;
        if (fechaInput) {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const local = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
            fechaInput.value = local;
        }
        if (solucionInput) solucionInput.value = '';

        if (btn) {
            btn.replaceWith(btn.cloneNode(true));
            const newBtn = Utils.getElement('confirmarCierre');
            newBtn.addEventListener('click', () => this.confirmarCerrar(hallazgoId));
        }

        modal.show();
    },

    /** Confirmar cierre y enviar al backend */
    async confirmarCerrar(hallazgoId) {
        try {
            const fecha = Utils.getElement('cerrarFecha')?.value || '';
            const solucion = Utils.getElement('cerrarSolucion')?.value || '';
            const form = new FormData();
            form.append('id', hallazgoId);
            form.append('fecha_cierre', fecha);
            form.append('solucion', solucion);

            const btn = Utils.getElement('confirmarCierre');
            const original = btn?.innerHTML;
            if (btn) {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Cerrando...';
                btn.disabled = true;
            }

            const resp = await fetch('includes/cerrar_hallazgo.php', { method: 'POST', body: form });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'Error al cerrar');

            NotificationManager.show('Hallazgo cerrado correctamente', 'success');
            const modal = bootstrap.Modal.getInstance(Utils.getElement('cerrarModal'));
            modal?.hide();
            await this.loadData();
            await DashboardManager.loadDashboardData();
        } catch (e) {
            console.error(e);
            NotificationManager.show('No se pudo cerrar el hallazgo: ' + e.message, 'error');
        } finally {
            const btn = Utils.getElement('confirmarCierre');
            if (btn) { btn.innerHTML = '<i class="fas fa-lock me-1"></i> Cerrar'; btn.disabled = false; }
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
            columnas = ['Fecha', 'Área', 'Est.', 'No. Parte', 'Modelo', 'Job Order', 'Cantidad', 'Defectos'];
        } else if (tipo === 'cuarentena') {
            data = AppState.cuarentenaData;
            tipoReporte = 'Registros en Cuarentena';
            columnas = ['Fecha', 'Área', 'Est.', 'No. Parte', 'Modelo', 'Job Order', 'Cantidad', 'Defectos'];
        } else if (tipo === 'scrap') {
            data = AppState.scrapData;
            tipoReporte = 'Registros en Scrap';
            columnas = ['Fecha', 'Área', 'Est.', 'No. Parte', 'Modelo', 'Job Order', 'Cantidad', 'Defectos', 'Fecha Scrap', 'Valor Scrap'];
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
            [`REPORTE DE REGISTROS DE CALIDAD`],
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
                item.cantidad_piezas || 0,
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
        const totalPiezas = data.reduce((sum, item) => sum + (item.cantidad_piezas || 0), 0);
        const resumenFooter = [
            [''], // Línea vacía
            ['RESUMEN:'],
            [`Total de registros: ${data.length}`],
            [`Total de piezas: ${totalPiezas}`],
            [`Total de defectos: ${totalDefectos}`],
            [`Promedio de piezas por registro: ${(totalPiezas / data.length).toFixed(2)}`],
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
    const nombreArchivo = `Reporte_Registros_${tipo}_${fechaArchivo}.csv`;

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
            const scrapFecha = Utils.getElement('scrapFecha');
            
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
            }
            if (scrapFecha) {
                // Si el hallazgo ya tiene fecha_scrap en algún dato relacionado, úsala; si no, hoy
                const today = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                const defaultDate = `${today.getFullYear()}-${pad(today.getMonth()+1)}-${pad(today.getDate())}`;
                // Intentar parsear fecha_scrap si viene en hallazgoData
                let setDate = defaultDate;
                if (hallazgoData?.fecha_scrap) {
                    const d = new Date(hallazgoData.fecha_scrap);
                    if (!isNaN(d)) {
                        setDate = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
                    }
                }
                scrapFecha.value = setDate;
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
            const scrapFecha = Utils.getElement('scrapFecha');
            
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
            if (scrapFecha) {
                const today = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                scrapFecha.value = `${today.getFullYear()}-${pad(today.getMonth()+1)}-${pad(today.getDate())}`;
            }
            
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
            const scrapFecha = Utils.getElement('scrapFecha');
            
            // Normalizador simple en cliente para asegurar formato ISO
            const normalizeDate = (val) => {
                if (!val) return '';
                const s = String(val).trim();
                // DD/MM/YYYY -> YYYY-MM-DD
                const m1 = s.match(/^\d{2}\/\d{2}\/\d{4}$/);
                if (m1) {
                    const [d, m, y] = s.split('/');
                    return `${y}-${m}-${d}`;
                }
                // YYYY-MM-DD o YYYY-MM
                const m2 = s.match(/^\d{4}-(\d{2})(-(\d{2}))?$/);
                if (m2) return s;
                // Solo YYYY -> YYYY-01-01
                const m3 = s.match(/^\d{4}$/);
                if (m3) return `${s}-01-01`;
                return s; // dejar tal cual; backend vuelve a normalizar
            };

            const formData = {
                hallazgo_id: scrapHallazgoId?.value || '',
                modelo: scrapModelo?.value || '',
                no_parte: scrapNoParte?.value || '',
                no_ensamble: scrapNoEnsamble?.value || '',
                precio: parseFloat(scrapPrecio?.value || '0'),
                observaciones: scrapObservaciones?.value || '',
                fecha_scrap: normalizeDate(scrapFecha?.value || '')
            };

            // Validar campos requeridos
            if (!formData.modelo || !formData.no_parte || !formData.no_ensamble || !formData.precio) {
                NotificationManager.show('Por favor complete todos los campos requeridos', 'error');
                return;
            }

            // Validar observación seleccionada
            if (!formData.observaciones) {
                NotificationManager.show('Selecciona la observación del motivo de scrap', 'error');
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

        // Configurar fechas por defecto (si existen inputs)
        DateManager.setDefaultDates();
        DateManager.setMaxDateToToday();

        // Detectar si estamos en la página de dashboard (no en scrap_dashboard)
        const isDashboardPage = Utils.elementExists('chartAreas') || Utils.elementExists('tablaRegistrosActivosBody');

        // Solo cargar y auto-refrescar el dashboard si estamos en la página correspondiente
        if (isDashboardPage) {
            DashboardManager.loadDashboardData();
            // Auto-refresh desactivado a petición del usuario. Antes:
            // setInterval(() => DashboardManager.refreshData(), AppConfig.AUTO_REFRESH_INTERVAL);
        }

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
