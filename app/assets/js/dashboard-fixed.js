/**
 * SISTEMA DE HALLAZGOS - DASHBOARD MÓVIL
 * Versión corregida con toda la funcionalidad
 */

// ===== CONFIGURACIÓN GLOBAL =====
const AppConfig = {
    MAX_FILE_SIZE: 50 * 1024 * 1024, // 50MB por archivo (aumentado)
    ALLOWED_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    MAX_FILES: 10,
    SUBMIT_ENDPOINT: 'guardar_hallazgo_multiple.php',
    VIBRATION_ENABLED: 'vibrate' in navigator,
    DEBUG_MODE: false, // Asegurar que debug esté OFF
    // Configuración específica para Android con archivos grandes
    ANDROID_MAX_FILE_SIZE: 10 * 1024 * 1024, // 10MB por archivo en Android
    ANDROID_MAX_TOTAL_SIZE: 100 * 1024 * 1024, // 100MB total para 10 fotos de 10MB
    ANDROID_COMPRESSION_THRESHOLD: 5 * 1024 * 1024, // Comprimir si es mayor a 5MB
    ANDROID_TARGET_SIZE: 3 * 1024 * 1024 // Objetivo de compresión: 3MB por foto
};

// ===== DATOS ESTÁTICOS =====
const AppData = {
    estacionesPorArea: {
        'Plasma': 3,
        'Prensas': 3,
        'Sierras': 4,
        'Beam welder': 2,
        'Fresadora': 1,
        'Roladora': 1,
        'Vulcanizadora': 1,
        'soldadura': 17,
        'ejes': 1,
        'Diseño': 1
    },

    defectosPorArea: {
        'Plasma': [
            'Mal corte por plasma',
            'Error de placa',
            'Error de programa',
            'Perforación de tubo fuera de especificación',
            'Daño por retrabajo de otra pieza',
            'Placa o pieza pandeada',
            'Otros'
        ],
        'Prensas': [
            'Pieza cerrada',
            'Pieza muy abierta',
            'Pieza con grados de mas (se doblo de más)',
            'Pieza con grado de menos (falto doblez)',
            'Se doblo al revés',
            'Las medidas no coinciden contra dibujo',
            'Pieza pandeada',
            'Daño por retrabajo de otra pieza',
            'Daño en pieza al doblar',
            'Otros'
        ],
        'Beam welder': [
            'cordón cargado hacia el webbing',
            'cordón cargado hacia el flange',
            'barrenos fuera de especificación',
            'altura de webbing fuera de tolerancia',
            'webbing mal cortado',
            'se ensamblo materia prima no especificada',
            'unión de flange mal soldada',
            'exceso de poros y cráter',
            'Otros'
        ],
        'Roladora': [
            'placa mal rolada',
            'Otros'
        ],
        'Sierras': [
            'Medida de tubo fuera de especificación',
            'Falta corte de ángulo',
            'Corte de ángulo fuera de especificación',
            'Se corto numero de parte con material no especificado',
            'Se daño por retrabajo de otro material',
            'Otros'
        ],
        'Fresadora': [
            'Perforación de tubo fuera de especificación',
            'Otros'
        ],
        'Vulcanizadora': [
            'Llanta dañada',
            'Rin dañado',
            'Eje dañado',
            'Otros'
        ],
        'soldadura': [
            'Traslape',
            'Socavación',
            'Grietas',
            'Porosidad',
            'Crater',
            'puntas sobrantes de soldadura',
            'chisporroteo',
            'Otros'
        ],
        'ejes': [
            'Llanta dañada',
            'Rin dañado',
            'Eje dañado',
            'Otros'
        ],
        'Diseño': [
            'CAMBIO DE DISEÑO',
            'CORRECCION DE DIBUJO',
            'Otros'
        ]
    },

    ensambleModelo: {
        "4-100-00194": "62.5T 9'W LB NECK W/ FLIP",
        "4-100-00188": "9'W LB LEVELING BOX",
        "4-100-00196": "LB, 62.5T, 9'W, SUSPENSION FRAME ASSY",
        "4-100-00195": "LB, 62.5T, 9'W, 30' WELL",
        "4-100-00012": "55T LB LEVELING BOX",
        "4-100-00007": "55T LB BED, 26' WELL",
        "4-100-00008": "55T LB 9'1\" SUSPENSION FRAME, 8'6\"W",
        "4-100-00014": "55T LB NECK",
        "4-100-00200": "LDDT, BED ASSY",
        "4-200-00416": "LDDT, FRAME",
        "4-200-00018": "32' ED, DRAFT ARM FRAME ASSY",
        "4-200-00031": "28' ED, DRAFT ARM FRAME ASSY",
        "4-200-00051": "36' ED, DRAFT ARM FRAME ASSY",
        "4-200-00110": "SS, DRAFT ARM A-FRAME ASSY",
        "4-100-00358": "SS, 40' X 96SW, ROLLED BODY ASSY",
        "4-200-00408": "SS, 96\" SW TAILGATE ASSY",
        "4-100-00314": "ED, 28' X 48\"SW ROLLED BODY ASSY",
        "4-100-00322": "ED, 32' X 48\"SW ROLLED BODY ASSY",
        "4-200-00328": "ED, 48\"SW TAILGATE",
        "4-200-00412": "BD, GATE ASSY",
        "4-200-00384": "BD, HOPPER ASSY",
        "4-200-00372": "BD, DS WALL ASSY",
        "4-200-00373": "BD, PS WALL ASSY",
        "4-200-00008": "ED/SS 5TH WHEEL ASSY",
        "4-200-00385": "BD, 5TH WHEEL PLATE ASSY",
        "4-200-00371": "BD, AIR SUSPENSION AND ATTACHMENT ASSY",
        "4-200-00370": "BD, SPRING SUSPENSION AND ATTACHMENT ASSY",
        "4-100-00144": "SD/LB, SUSPENSION, DS ARM & HANGER W/ BUSHING ASSY",
        "4-100-00145": "SD/LB, SUSPENSION, PS ARM & HANGER W/ BUSHING ASSY",
        "4-200-00314": "LB, SUSPENSION WISHBONE, DS ARM",
        "4-200-00315": "LB, SUSPENSION WISHBONE, PS ARM",
        "4-100-00151": "FB/BD, SUSPENSION, PS ARM & HANGER W/ BUSHING ASSY",
        "4-100-00152": "FB/BD, SUSPENSION, DS ARM & HANGER W/ BUSHING ASSY",
        "4-200-00071": "55T LB MID AXLE WHEEL COVE",
        "4-200-00106": "55T LB ANGLED RA COVER PLATE",
        "4-200-00069": "55T LB PS ANGLE WHEEL COVER",
        "4-200-00070": "55T LB DS ANGLE WHEEL COVER",
        "4-200-00411": "ED, HYD. CYL. BOLTED PIVOT 28' & 36'",
        "4-200-00407": "BD, FRONT WING BRACING ASSY",
        "4-200-00409": "BD, REAR WING BRACING ASSY",
        "4-200-00410": "ED/SS, SUSPENSION, CENTERPOINT SPRING"
    }
};

// ===== UTILIDADES =====
class Utils {
    static showAlert(message) {
        alert(message);
    }

    static vibrate(pattern = [100]) {
        if (AppConfig.VIBRATION_ENABLED) {
            navigator.vibrate(pattern);
        }
    }

    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// ===== GESTOR DE ELEMENTOS DOM =====
class DOMManager {
    constructor() {
        this.elements = {
            form: document.getElementById('form-hallazgo'),
            loadingOverlay: document.getElementById('loadingOverlay'),
            successOverlay: document.getElementById('successOverlay'),
            submitBtn: document.getElementById('submitBtn'),
            fileInput: document.getElementById('evidencias'),
            fileUploadArea: document.getElementById('fileUploadArea'),
            fileCounter: document.getElementById('fileCounter'),
            fileCountText: document.getElementById('fileCountText'),
            imagePreview: document.getElementById('imagePreview'),
            defectDisplay: document.getElementById('defectDisplay'),
            defectDropdown: document.getElementById('defectDropdown'),
            defectError: document.getElementById('defectError'),
            fileError: document.getElementById('fileError'),
            translateBtn: document.getElementById('traducirBtn'),
            translateText: document.getElementById('translateText'),
            fecha: document.getElementById('fecha'),
            area: document.getElementById('area'),
            estacion: document.getElementById('estacion'),
            noEnsamble: document.getElementById('no_ensamble'),
            modelo: document.getElementById('modelo'),
            jobOrder: document.getElementById('job_order'),
            noParte: document.getElementById('no_parte'),
            cantidad_piezas: document.getElementById('cantidad_piezas'),
            observaciones: document.getElementById('observaciones'),
            retrabajo: document.getElementById('retrabajo'),
            ensambles: document.getElementById('ensambles')
        };
    }

    get(elementName) {
        return this.elements[elementName];
    }

    setLoading(show) {
        if (show) {
            this.elements.loadingOverlay?.classList.add('active');
        } else {
            this.elements.loadingOverlay?.classList.remove('active');
        }
    }

    showSuccess() {
        this.elements.successOverlay?.classList.add('active');
        setTimeout(() => {
            this.elements.successOverlay?.classList.remove('active');
        }, 3000);
    }
}

// ===== GESTOR DE ARCHIVOS =====
class FileManager {
    constructor() {
        this.files = [];
        this.dom = new DOMManager();
    }

    addFiles(fileList) {
        for (let file of fileList) {
            if (this.validateFile(file) && this.files.length < AppConfig.MAX_FILES) {
                this.files.push(file);
            }
        }
        this.updateUI();
    }

    validateFile(file) {
        if (!AppConfig.ALLOWED_TYPES.includes(file.type)) {
            Utils.showAlert(`Tipo de archivo no permitido: ${file.type}`);
            return false;
        }
        if (file.size > AppConfig.MAX_FILE_SIZE) {
            Utils.showAlert(`Archivo muy grande: ${Utils.formatFileSize(file.size)}`);
            return false;
        }
        return true;
    }

    removeFile(index) {
        this.files.splice(index, 1);
        this.updateUI();
    }

    updateUI() {
        const counter = this.dom.get('fileCounter');
        const countText = this.dom.get('fileCountText');
        const preview = this.dom.get('imagePreview');

        if (this.files.length > 0) {
            counter.style.display = 'block';
            countText.textContent = `${this.files.length} archivo${this.files.length > 1 ? 's' : ''} seleccionado${this.files.length > 1 ? 's' : ''}`;
            
            preview.innerHTML = '';
            this.files.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'image-preview-item';
                div.innerHTML = `
                    <div class="image-info">
                        <div class="image-name">${file.name}</div>
                        <div class="image-size">${Utils.formatFileSize(file.size)}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger remove-btn" onclick="window.fileManager.removeFile(${index})">
                        ✕
                    </button>
                `;
                preview.appendChild(div);
            });
        } else {
            counter.style.display = 'none';
            preview.innerHTML = '';
        }
    }

    getFiles() {
        return this.files;
    }

    hasFiles() {
        return this.files.length > 0;
    }

    handleFileSelection(fileList) {
        this.addFiles(fileList);
    }
}

// ===== GESTOR DE DEFECTOS =====
class DefectManager {
    constructor() {
        this.selectedDefects = [];
        this.dom = new DOMManager();
        this.isDropdownOpen = false;
        this.initializeDropdown();
    }

    initializeDropdown() {
        const display = this.dom.get('defectDisplay');
        const dropdown = this.dom.get('defectDropdown');
        
        if (display && dropdown) {
            // Click en el display para abrir/cerrar dropdown
            display.addEventListener('click', () => {
                this.toggleDropdown();
            });
            
            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', (e) => {
                if (!display.contains(e.target) && !dropdown.contains(e.target)) {
                    this.closeDropdown();
                }
            });
        }
    }

    updateOptions(defects) {
        const dropdown = this.dom.get('defectDropdown');
        if (!dropdown) {
            console.error('❌ No se encontró defectDropdown');
            return;
        }

        console.log('🔧 Actualizando opciones de defectos:', defects);
        
        // Limpiar dropdown
        dropdown.innerHTML = '';
        
        if (defects.length === 0) {
            dropdown.innerHTML = '<div class="dropdown-item disabled">No hay defectos disponibles para esta área</div>';
            return;
        }
        
        // Agregar opciones
        defects.forEach(defect => {
            const option = document.createElement('div');
            option.className = 'multi-select-option';
            option.textContent = defect;
            
            option.addEventListener('click', (e) => {
                e.stopPropagation();
                this.addDefect(defect);
                this.closeDropdown();
            });
            
            dropdown.appendChild(option);
        });
    }

    toggleDropdown() {
        const dropdown = this.dom.get('defectDropdown');
        if (!dropdown) return;
        
        if (this.isDropdownOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }

    openDropdown() {
        const dropdown = this.dom.get('defectDropdown');
        const display = this.dom.get('defectDisplay');
        if (!dropdown || !display) return;
        
        // Usar los estilos CSS existentes
        dropdown.style.display = 'block';
        display.classList.add('active');
        
        this.isDropdownOpen = true;
    }

    closeDropdown() {
        const dropdown = this.dom.get('defectDropdown');
        const display = this.dom.get('defectDisplay');
        if (!dropdown || !display) return;
        
        dropdown.style.display = 'none';
        display.classList.remove('active');
        this.isDropdownOpen = false;
    }

    addDefect(defect) {
        if (!this.selectedDefects.includes(defect)) {
            this.selectedDefects.push(defect);
            this.updateDisplay();
            Utils.vibrate([50]);
            console.log('✅ Defecto agregado:', defect);
        }
    }

    removeDefect(defect) {
        this.selectedDefects = this.selectedDefects.filter(d => d !== defect);
        this.updateDisplay();
        console.log('🗑️ Defecto removido:', defect);
    }

    updateDisplay() {
        const display = this.dom.get('defectDisplay');
        if (!display) return;

        if (this.selectedDefects.length > 0) {
            display.innerHTML = this.selectedDefects.map(defect => `
                <span class="badge bg-danger me-2 mb-2">
                    ${defect}
                    <button type="button" class="btn-close btn-close-white ms-2" onclick="window.defectManager.removeDefect('${defect.replace(/'/g, "\\'")}')"></button>
                </span>
            `).join('');
        } else {
            display.innerHTML = '<span class="multi-select-placeholder" style="color: #6c757d;">Selecciona uno o más defectos</span>';
        }
    }

    getSelectedDefects() {
        return this.selectedDefects;
    }

    hasSelectedDefects() {
        return this.selectedDefects.length > 0;
    }

    reset() {
        this.selectedDefects = [];
        this.updateDisplay();
    }
}

// ===== GESTOR DEL FORMULARIO =====
class FormManager {
    constructor() {
        this.dom = new DOMManager();
        this.isSubmitting = false;
        this.deviceInfo = null;
        this.initialize();
    }

    initialize() {
        this.setCurrentDate();
        this.populateEnsambles();
        this.initializeEvents();
        this.detectDevice();
        
        // Debug para verificar inicialización
        console.log('🤖 FormManager inicializado:');
        console.log('- Fecha automática:', this.dom.get('fecha')?.value);
        console.log('- Números de ensamble cargados:', Object.keys(AppData.ensambleModelo).length);
        console.log('- Ejemplo de números: 4-200-00410, 4-100-00322, etc.');
        console.log('- Áreas disponibles:', Object.keys(AppData.defectosPorArea).length);
        console.log('- Device info:', this.deviceInfo);
    }

    setCurrentDate() {
        const fechaInput = this.dom.get('fecha');
        if (fechaInput) {
            const today = new Date().toISOString().split('T')[0];
            fechaInput.value = today;
        }
    }

    populateEnsambles() {
        const datalist = this.dom.get('ensambles');
        if (datalist) {
            datalist.innerHTML = '';
            Object.keys(AppData.ensambleModelo).forEach(num => {
                const option = document.createElement('option');
                option.value = num;
                datalist.appendChild(option);
            });
        }
    }

    initializeEvents() {
        // Eventos de área y estación
        this.dom.get('area')?.addEventListener('change', (e) => this.handleAreaChange(e.target.value));
        this.dom.get('estacion')?.addEventListener('change', (e) => this.handleStationChange(e.target.value));
        this.dom.get('noEnsamble')?.addEventListener('input', (e) => this.handleAssemblyChange(e.target.value));
        
        // Evento principal de envío del formulario
        this.dom.get('form')?.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Fallback para dispositivos móviles
        const submitBtn = this.dom.get('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                submitEvent.submitter = submitBtn;
                this.dom.get('form')?.dispatchEvent(submitEvent);
            });
        }
    }

    detectDevice() {
        const userAgent = navigator.userAgent;
        // Detectar Android, iPad, iPhone, Samsung, tablet, etc.
        const isAndroid = /Android/.test(userAgent);
        const isSamsungTablet = /Samsung|SM-T|SM-|GT-|Tab|Tablet|Galaxy S7/i.test(userAgent);
        const isTablet = /Tablet|iPad|SM-T|GT-|Tab/i.test(userAgent);
        const isMobile = /Android|iPad|iPhone|Samsung|SM-T|SM-|GT-|Tab|Tablet|Galaxy S7/i.test(userAgent);

        if (!isMobile) {
            console.warn('⚠️ Esta aplicación está optimizada para dispositivos móviles');
            this.showNonAndroidWarning();
        }

        // Configuraciones específicas para tabletas - Reducir límites para evitar problemas de memoria
        if (isTablet || isSamsungTablet) {
            console.log('📱 Detectada tableta - Configurando límites optimizados');
            AppConfig.MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB por archivo (más pequeño para tabletas)
            AppConfig.MAX_FILES = 16; // Permitir 16 archivos como en celular
            AppConfig.ANDROID_MAX_FILE_SIZE = 5 * 1024 * 1024;
            AppConfig.ANDROID_MAX_TOTAL_SIZE = 80 * 1024 * 1024; // 80MB total
            AppConfig.ANDROID_COMPRESSION_THRESHOLD = 3 * 1024 * 1024; // Comprimir más agresivamente
            AppConfig.ANDROID_TARGET_SIZE = 1.5 * 1024 * 1024; // Target más pequeño
            this.tabletMode = true;
        } else if (isAndroid) {
            console.log('📱 Detectado celular Android - Configurando límites estándar');
            AppConfig.MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB por archivo
            AppConfig.MAX_FILES = 16; // 16 archivos
            AppConfig.ANDROID_MAX_FILE_SIZE = 10 * 1024 * 1024;
            AppConfig.ANDROID_MAX_TOTAL_SIZE = 160 * 1024 * 1024; // 160MB total
            AppConfig.ANDROID_COMPRESSION_THRESHOLD = 5 * 1024 * 1024;
            AppConfig.ANDROID_TARGET_SIZE = 3 * 1024 * 1024;
            this.tabletMode = false;
        }

        console.log('Dispositivo detectado:', {
            isAndroid,
            isSamsungTablet,
            isTablet,
            isMobile,
            tabletMode: this.tabletMode,
            userAgent: userAgent.substring(0, 100),
            maxFileSize: AppConfig.MAX_FILE_SIZE,
            maxFiles: AppConfig.MAX_FILES
        });

        this.deviceInfo = { isMobile, isAndroid, isSamsungTablet, isTablet, tabletMode: this.tabletMode };

        // Configuraciones específicas para Android y tabletas Samsung
        if (isAndroid || isSamsungTablet) {
            this.configureForAndroid();
        }
    }

    showNonAndroidWarning() {
        // Solo mostrar en desktop, no en iOS
        if (!/iPhone|iPad|iPod/.test(navigator.userAgent)) {
            setTimeout(() => {
                alert('📱 Esta aplicación está optimizada para dispositivos Android.\n\n' +
                      'Para la mejor experiencia, úsala desde un teléfono o tablet Android.');
            }, 2000);
        }
    }

    configureForAndroid() {
        console.log('🤖 Configurando optimizaciones para Android/Tabletas');
        
        // Timeout ajustado según el tipo de dispositivo
        if (this.tabletMode) {
            this.androidTimeout = 30000; // 30 segundos para tabletas (menos que celulares)
            console.log('📱 Modo tableta: timeout reducido a 30s');
        } else {
            this.androidTimeout = 45000; // 45 segundos para celulares
            console.log('📱 Modo celular: timeout estándar 45s');
        }
        
        // Configurar eventos táctiles optimizados
        this.setupAndroidTouchEvents();
        
        // Configurar orientación
        this.setupOrientationHandler();
        
        // Verificar conectividad de red en Android
        this.setupAndroidNetworkChecks();
        
        // Configuraciones específicas para tabletas
        if (this.tabletMode) {
            this.setupTabletOptimizations();
        }
    }

    setupTabletOptimizations() {
        console.log('📱 Aplicando optimizaciones específicas para tabletas');
        
        // Limpiar memoria más frecuentemente en tabletas
        setInterval(() => {
            if (window.gc && typeof window.gc === 'function') {
                try {
                    window.gc();
                } catch(e) {
                    // Silent fail - gc() no siempre está disponible
                }
            }
            
            // Forzar limpieza de URLs de objetos no utilizadas
            if (this.objectURLs) {
                this.objectURLs.forEach(url => {
                    try {
                        URL.revokeObjectURL(url);
                    } catch(e) {
                        // Silent fail
                    }
                });
                this.objectURLs = [];
            }
        }, 10000); // Cada 10 segundos
        
        this.objectURLs = [];
        
        // Configurar para procesar archivos uno a la vez en tabletas
        this.tabletFileProcessingMode = true;
        
        console.log('✅ Optimizaciones de tableta configuradas');
    }

    setupAndroidNetworkChecks() {
        // Verificar conectividad cuando la app gana foco
        window.addEventListener('focus', () => {
            this.checkAndroidConnectivity();
        });
        
        // Verificar conectividad cuando cambia el estado de la red
        if ('onLine' in navigator) {
            window.addEventListener('online', () => {
                console.log('🌐 Android: Conexión a internet restaurada');
            });
            
            window.addEventListener('offline', () => {
                console.log('🚫 Android: Sin conexión a internet');
                Utils.showAlert('📵 Sin conexión a internet. Verifica tu WiFi o datos móviles.');
            });
        }
    }

    async checkAndroidConnectivity() {
        try {
            // Hacer una petición simple para verificar conectividad
            const response = await fetch(window.location.origin + '/index.php', { 
                method: 'HEAD',
                cache: 'no-cache',
                timeout: 5000
            });
            console.log('🌐 Android: Conectividad verificada');
            return true;
        } catch (error) {
            console.warn('⚠️ Android: Problema de conectividad detectado');
            return false;
        }
    }

    setupAndroidTouchEvents() {
        // Mejorar la respuesta táctil en Android SIN bloquear scroll
        document.addEventListener('touchstart', function() {}, { passive: true });
        
        // NO prevenir zoom - solo en gestos específicos de formulario
        const formInputs = document.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                // Prevenir zoom solo en inputs cuando están enfocados
                document.querySelector('meta[name=viewport]').setAttribute('content', 
                    'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
            });
            
            input.addEventListener('blur', function() {
                // Restaurar zoom después del input
                document.querySelector('meta[name=viewport]').setAttribute('content', 
                    'width=device-width, initial-scale=1.0, user-scalable=yes');
            });
        });
    }

    setupOrientationHandler() {
        // Manejar cambios de orientación en Android
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                // Reajustar elementos después del cambio de orientación
                this.adjustLayoutForOrientation();
            }, 100);
        });
    }

    adjustLayoutForOrientation() {
        // Ajustes específicos para orientación
        const isLandscape = window.orientation === 90 || window.orientation === -90;
        console.log('Orientación:', isLandscape ? 'Horizontal' : 'Vertical');
    }

    handleAreaChange(area) {
        this.updateStations(area);
        this.updateDefectsForArea(area);
    }

    updateStations(area) {
        const estacionSelect = this.dom.get('estacion');
        if (!estacionSelect) return;

        estacionSelect.innerHTML = '<option value="">Selecciona una estación</option>';
        
        if (AppData.estacionesPorArea[area]) {
            const numEstaciones = AppData.estacionesPorArea[area];
            for (let i = 1; i <= numEstaciones; i++) {
                const option = document.createElement('option');
                option.value = `Estación ${i}`;
                option.textContent = `Estación ${i}`;
                estacionSelect.appendChild(option);
            }
        }
    }

    updateDefectsForArea(area) {
        console.log('🔧 Actualizando defectos para área:', area);
        window.defectManager.reset();
        if (AppData.defectosPorArea[area]) {
            console.log('- Defectos encontrados:', AppData.defectosPorArea[area].length);
            window.defectManager.updateOptions(AppData.defectosPorArea[area]);
        } else {
            console.log('- No se encontraron defectos para esta área');
            window.defectManager.updateOptions([]);
        }
    }

    handleStationChange(station) {
        const retrabajoSelect = this.dom.get('retrabajo');
        if (!retrabajoSelect) return;

        if (station.toLowerCase().includes('soldadura')) {
            retrabajoSelect.value = 'Si';
            retrabajoSelect.disabled = true;
        } else {
            retrabajoSelect.value = '';
            retrabajoSelect.disabled = false;
        }
    }

    handleAssemblyChange(value) {
        const modeloInput = this.dom.get('modelo');
        if (!modeloInput) return;

        const trimmedValue = value.trim();
        console.log('🔢 Cambio en número de ensamble:', trimmedValue);
        
        if (AppData.ensambleModelo[trimmedValue]) {
            const modelo = AppData.ensambleModelo[trimmedValue];
            modeloInput.value = modelo;
            console.log('- Modelo encontrado:', modelo);
        } else {
            modeloInput.value = '';
            console.log('- No se encontró modelo para este número');
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        e.stopPropagation();

        if (this.isSubmitting) return;
        if (!this.validateForm()) return;

        // Verificar conectividad en Android/Tableta antes de enviar
        if (this.deviceInfo?.isMobile) {
            if (!navigator.onLine) {
                Utils.showAlert('📵 Sin conexión a internet. Verifica tu WiFi o datos móviles antes de continuar.');
                return;
            }
            
            console.log(`🤖 ${this.tabletMode ? 'Tableta' : 'Móvil'}: Verificando conectividad antes del envío...`);
            const isConnected = await this.checkAndroidConnectivity();
            if (!isConnected) {
                Utils.showAlert('🌐 Problema de conectividad detectado. Verifica tu conexión e intenta nuevamente.');
                return;
            }
        }

        this.isSubmitting = true;
        this.dom.setLoading(true);

        try {
            // Usar el mismo enfoque exitoso del botón test
            const form = document.getElementById('form-hallazgo');
            const formData = new FormData(form);
            
            // Agregar los defectos seleccionados  
            const selectedDefects = window.defectManager.getSelectedDefects();
            selectedDefects.forEach(defecto => {
                formData.append('defectos[]', defecto);
            });
            
            // Procesar archivos usando la misma lógica exitosa del test
            const files = window.fileManager.getFiles();
            
            if (files.length > 0) {
                console.log(`📱 ${this.tabletMode ? 'Tableta' : 'Celular'}: Procesando ${files.length} archivos con lógica del test exitoso...`);
                
                let processedSize = 0;
                
                // Comprimir y agregar archivos exactamente como el test exitoso
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    let processedFile = file;
                    
                    // Usar la misma lógica de compresión del test exitoso (5MB threshold, 4MB target)
                    if (file.type.startsWith('image/') && file.size > 5 * 1024 * 1024) {
                        console.log(`🔄 Comprimiendo ${file.name} con método del test exitoso...`);
                        try {
                            processedFile = await this.compressImageForDevice(file, 4 * 1024 * 1024);
                            const reduction = ((file.size - processedFile.size) / file.size * 100).toFixed(1);
                            console.log(`✅ ${file.name}: ${Utils.formatFileSize(file.size)} → ${Utils.formatFileSize(processedFile.size)} (${reduction}% reducción)`);
                        } catch (error) {
                            console.warn(`⚠️ Error comprimiendo ${file.name}:`, error.message);
                            processedFile = file; // Usar original si falla compresión
                        }
                    }
                    
                    processedSize += processedFile.size;
                    formData.append('evidencias[]', processedFile);
                    console.log(`📁 Archivo ${i + 1}: ${processedFile.name} (${Utils.formatFileSize(processedFile.size)})`);
                }
                
                console.log(`📤 Total procesado con lógica del test: ${Utils.formatFileSize(processedSize)}`);
            } else {
                // No hay archivos seleccionados; continuar con el envío sin evidencias
                console.log('ℹ️ Envío sin fotos de evidencia (opcional).');
            }
            
            // Usar exactamente los mismos headers del test exitoso pero SIN modo test
            const startTime = Date.now();
            const response = await fetch(AppConfig.SUBMIT_ENDPOINT, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Android-App': 'HallazgosQuality'
                }
            });
            
            const endTime = Date.now();
            const duration = ((endTime - startTime) / 1000).toFixed(1);
            
            console.log(`📱 Response status: ${response.status}`);
            console.log(`⏱️ Tiempo de envío: ${duration} segundos`);

            if (!response.ok) {
                // Capturar el texto de la respuesta para debug
                const errorText = await response.text();
                console.error('❌ Error response text:', errorText.substring(0, 500));
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Capturar la respuesta como texto primero para debug
            const responseText = await response.text();
            console.log('📱 Raw response text:', responseText.substring(0, 200));
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('❌ Error parseando JSON:', jsonError.message);
                console.error('❌ Response que causó el error:', responseText.substring(0, 1000));
                throw new Error(`Error parseando respuesta del servidor. Respuesta: ${responseText.substring(0, 200)}`);
            }

            if (result.success) {
                this.handleSubmitSuccess(result);
            } else {
                Utils.showAlert(result.message || 'Error al guardar el hallazgo');
            }
        } catch (error) {
            console.error('❌ Error en Android:', error);
            console.error('❌ Stack trace:', error.stack);
            console.error('❌ Error name:', error.name);
            console.error('❌ Error constructor:', error.constructor.name);
            
            let errorMessage = 'Error de conexión en dispositivo Android.';
            
            // Análisis más específico del error basado en los tests exitosos
            if (error.message.includes('muy grande')) {
                // Error de tamaño de archivo
                errorMessage = `📁 ${error.message}`;
            } else if (error.name === 'AbortError' || error.message.includes('timeout')) {
                errorMessage = '⏱️ La conexión tardó mucho tiempo.\n\n💡 Esto es común en Android con archivos grandes.\n\n🔧 Soluciones:\n• Usa fotos más pequeñas (menos de 5MB)\n• Intenta con 1-2 fotos por vez\n• Verifica que tengas buena señal\n• Cambia de WiFi a datos móviles o viceversa';
            } else if (error.name === 'TypeError' && (error.message.includes('fetch') || error.message.includes('Failed to fetch'))) {
                errorMessage = '� Error de red específico de Android.\n\n� Diagnóstico:\n• Los tests básicos funcionan ✅\n• Pero el envío con archivos falla ❌\n• Problema común en Android con FormData\n\n🔧 Soluciones inmediatas:\n• Reduce el tamaño de las fotos a menos de 2MB\n• Intenta con solo 1 foto primero\n• Usa Chrome si estás en otro navegador\n• Reinicia la app del navegador\n• Cambia de WiFi a datos móviles\n\n💡 Causa probable: Límites de memoria o red del navegador Android con archivos grandes.';
            } else if (error.message.includes('HTTP error')) {
                const statusMatch = error.message.match(/status: (\d+)/);
                const status = statusMatch ? statusMatch[1] : 'desconocido';
                
                if (status === '500') {
                    errorMessage = `� Error del servidor (HTTP ${status}).\n\n💡 Error interno del servidor.\n\n🔧 Soluciones:\n• Intenta nuevamente en unos minutos\n• Reduce el número de archivos\n• Contacta al administrador si persiste`;
                } else if (status === '413' || status === '502' || status === '503' || status === '408') {
                    errorMessage = `🔧 Error del servidor (HTTP ${status}).\n\n💡 Significado:\n• 413: Archivo muy grande para el servidor\n• 500: Error interno del servidor\n• 502/503: Servidor sobrecargado\n• 408: Timeout del servidor\n\n🔧 Soluciones:\n• Reduce el tamaño de las fotos\n• Intenta con menos fotos\n• Espera unos minutos y reintenta`;
                } else {
                    errorMessage = `🚨 Error del servidor (${status})\n\n💻 Error interno del sistema.\n\n🔧 Soluciones:\n• Intenta nuevamente en unos minutos\n• Reduce el número de archivos\n• Contacta al administrador si persiste`;
                }
            } else if (error.message.includes('NetworkError') || error.message.includes('network')) {
                errorMessage = '🔌 Error de red específico de Android.\n\n💡 Los tests básicos pasan pero el formulario completo falla.\n\nEsto indica:\n• Problema con archivos grandes en Android\n• Límites del navegador móvil\n• Configuración de red restrictiva\n\n🔧 Soluciones probadas:\n• Fotos muy pequeñas (menos de 1MB)\n• Solo 1 foto por vez\n• Comprobar permisos de la app\n• Limpiar caché del navegador\n• Usar Chrome en modo incógnito';
            } else if (error.message.includes('JSON') || error.message.includes('parse')) {
                errorMessage = '� Error de respuesta del servidor.\n\n💡 El archivo se envió pero la respuesta está mal formateada.\n\n🔧 Puede que el hallazgo se haya guardado exitosamente.\n\nVerifica en el sistema si aparece registrado.';
            } else {
                // Error genérico con más información de contexto
                errorMessage = `🤖 Error específico de Android: ${error.message}\n\n� Contexto:\n• Tests básicos: ✅ Funcionan\n• Envío completo: ❌ Falla\n• Error tipo: ${error.name || 'Desconocido'}\n\n💡 Esto sugiere que el problema es específicamente con el envío de archivos en Android.\n\n🔧 Intenta:\n• Fotos MUY pequeñas (menos de 1MB)\n• Solo 1 foto por envío\n• Cambiar de navegador a Chrome\n• Reiniciar el navegador completamente\n• Conectarte a otra red WiFi`;
            }
            
            Utils.showAlert(errorMessage);
        } finally {
            this.isSubmitting = false;
            this.dom.setLoading(false);
        }
    }

    // Método para comprimir imágenes automáticamente en Android/Tabletas - Versión mejorada
    async compressImageForDevice(file, targetSizeBytes = AppConfig.ANDROID_TARGET_SIZE) {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = function() {
                // Calcular nuevas dimensiones basadas en el tamaño objetivo y tipo de dispositivo
                let { width, height } = img;
                const originalSize = file.size;
                
                // Calcular factor de reducción basado en el tamaño del archivo
                let reductionFactor = Math.sqrt(targetSizeBytes / originalSize);
                
                // Límites de dimensiones ajustados para tabletas
                const isTabletMode = window.formManager?.tabletMode;
                const maxDimension = isTabletMode ? 1600 : 2048; // Menor para tabletas
                const minDimension = isTabletMode ? 600 : 800;   // Menor para tabletas
                
                // Aplicar reducción
                let newWidth = Math.floor(width * reductionFactor);
                let newHeight = Math.floor(height * reductionFactor);
                
                // Asegurar que no exceda el máximo
                if (newWidth > maxDimension || newHeight > maxDimension) {
                    if (newWidth > newHeight) {
                        newHeight = (newHeight * maxDimension) / newWidth;
                        newWidth = maxDimension;
                    } else {
                        newWidth = (newWidth * maxDimension) / newHeight;
                        newHeight = maxDimension;
                    }
                }
                
                // Asegurar que no sea demasiado pequeño
                if (newWidth < minDimension && newHeight < minDimension) {
                    if (newWidth > newHeight) {
                        newHeight = (newHeight * minDimension) / newWidth;
                        newWidth = minDimension;
                    } else {
                        newWidth = (newWidth * minDimension) / newHeight;
                        newHeight = minDimension;
                    }
                }
                
                canvas.width = newWidth;
                canvas.height = newHeight;
                
                // Mejorar calidad de renderizado
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                
                // Dibujar imagen redimensionada
                ctx.drawImage(img, 0, 0, newWidth, newHeight);
                
                // Función para intentar diferentes calidades - más agresiva para tabletas
                const initialQuality = isTabletMode ? 0.6 : 0.8;
                const minQuality = isTabletMode ? 0.2 : 0.3;
                
                const tryCompress = (quality = initialQuality) => {
                    canvas.toBlob((blob) => {
                        if (blob) {
                            const compressedFile = new File([blob], file.name, {
                                type: file.type,
                                lastModified: Date.now()
                            });
                            
                            console.log(`🎯 Compresión ${isTabletMode ? 'tableta' : 'celular'}: ${Utils.formatFileSize(originalSize)} → ${Utils.formatFileSize(blob.size)} (calidad: ${Math.round(quality * 100)}%)`);
                            
                            // Si aún es muy grande y podemos reducir más la calidad
                            if (blob.size > targetSizeBytes && quality > minQuality) {
                                console.log(`📉 Archivo aún grande para ${isTabletMode ? 'tableta' : 'celular'}, reduciendo calidad...`);
                                tryCompress(quality - 0.1);
                            } else {
                                resolve(compressedFile);
                            }
                        } else {
                            reject(new Error('Error en compresión'));
                        }
                    }, file.type, quality);
                };
                
                // Iniciar compresión
                tryCompress();
            };
            
            img.onerror = () => reject(new Error('No se pudo procesar la imagen'));
            img.src = URL.createObjectURL(file);
        });
    }

    fetchWithTimeoutAndRetry(url, options, timeout = 30000) {
        return new Promise((resolve, reject) => {
            const controller = new AbortController();
            const signal = controller.signal;
            
            const timeoutId = setTimeout(() => {
                controller.abort();
                reject(new Error('Request timeout - La conexión tardó demasiado'));
            }, timeout);
            
            // Para Android, intentar múltiples veces con estrategia específica
            const attemptFetch = (attemptNumber = 1, maxAttempts = 2) => { // Solo 2 intentos para evitar problemas
                console.log(`🔄 Intento ${attemptNumber}/${maxAttempts} de envío Android`);
                
                fetch(url, { ...options, signal })
                    .then(response => {
                        clearTimeout(timeoutId);
                        console.log(`✅ Respuesta recibida en intento ${attemptNumber}: ${response.status}`);
                        resolve(response);
                    })
                    .catch(error => {
                        console.log(`❌ Intento ${attemptNumber} falló:`, error.name, error.message);
                        
                        // Solo reintentar en casos específicos y no en el último intento
                        if (attemptNumber < maxAttempts && 
                            (error.name === 'TypeError' || 
                             error.message.includes('Failed to fetch') ||
                             error.message.includes('NetworkError'))) {
                            
                            // Esperar antes del siguiente intento (más tiempo para Android)
                            const waitTime = 2000 * attemptNumber; // 2s, 4s
                            console.log(`⏳ Esperando ${waitTime}ms antes del siguiente intento...`);
                            
                            setTimeout(() => {
                                attemptFetch(attemptNumber + 1, maxAttempts);
                            }, waitTime);
                        } else {
                            clearTimeout(timeoutId);
                            // Agregar información específica del intento fallido
                            error.androidContext = {
                                attempt: attemptNumber,
                                maxAttempts: maxAttempts,
                                isLastAttempt: attemptNumber === maxAttempts
                            };
                            reject(error);
                        }
                    });
            };
            
            attemptFetch();
        });
    }

    fetchWithTimeout(url, options, timeout = 15000) {
        return new Promise((resolve, reject) => {
            const controller = new AbortController();
            const signal = controller.signal;
            
            const timeoutId = setTimeout(() => {
                controller.abort();
                reject(new Error('Request timeout'));
            }, timeout);
            
            // Para Android, intentar múltiples veces en caso de falla de red
            const attemptFetch = (attemptNumber = 1, maxAttempts = 3) => {
                fetch(url, { ...options, signal })
                    .then(response => {
                        clearTimeout(timeoutId);
                        resolve(response);
                    })
                    .catch(error => {
                        console.log(`🔄 Intento ${attemptNumber} falló:`, error.message);
                        
                        if (attemptNumber < maxAttempts && 
                            (error.name === 'TypeError' || error.message.includes('Failed to fetch'))) {
                            // Esperar un poco antes del siguiente intento
                            setTimeout(() => {
                                console.log(`🔄 Reintentando conexión... (${attemptNumber + 1}/${maxAttempts})`);
                                attemptFetch(attemptNumber + 1, maxAttempts);
                            }, 1000 * attemptNumber); // Esperar más tiempo en cada intento
                        } else {
                            clearTimeout(timeoutId);
                            reject(error);
                        }
                    });
            };
            
            attemptFetch();
        });
    }

    validateForm() {
        let isValid = true;

        if (!this.dom.get('form')?.checkValidity()) {
            this.dom.get('form')?.classList.add('was-validated');
            isValid = false;
        }

        // Validación específica para cantidad de piezas
        const cantidadPiezas = document.getElementById('cantidad_piezas');
        if (cantidadPiezas) {
            const valor = parseInt(cantidadPiezas.value);
            if (isNaN(valor) || valor < 1) {
                cantidadPiezas.classList.add('is-invalid');
                isValid = false;
            } else {
                cantidadPiezas.classList.remove('is-invalid');
            }
        }

        // Las fotos ahora son OPCIONALES - removida validación obligatoria
        this.dom.get('fileError').style.display = 'none';

        if (!window.defectManager.hasSelectedDefects()) {
            this.dom.get('defectError').style.display = 'block';
            this.dom.get('defectError').textContent = 'Por favor selecciona al menos un defecto';
            isValid = false;
        } else {
            this.dom.get('defectError').style.display = 'none';
        }

        return isValid;
    }

    buildFormData() {
        const formData = new FormData();

        // Obtener el id_usuario del campo oculto en el HTML
        const idUsuarioField = document.querySelector('input[name="id_usuario"]');
        const idUsuario = idUsuarioField ? idUsuarioField.value : '1';
        
        // Debug: Mostrar información del usuario
        console.log('🤖 DEBUG - Campo id_usuario:', {
            'field_exists': !!idUsuarioField,
            'field_value': idUsuarioField ? idUsuarioField.value : 'No existe',
            'final_id_usuario': idUsuario
        });
        
        formData.append('id_usuario', idUsuario);

        const fields = [
            { id: 'fecha', name: 'fecha' },
            { id: 'jobOrder', name: 'job_order' },
            { id: 'noEnsamble', name: 'no_ensamble' },
            { id: 'estacion', name: 'estacion' },
            { id: 'area', name: 'area' },
            { id: 'modelo', name: 'modelo' },
            { id: 'noParte', name: 'no_parte' },
            { id: 'cantidad_piezas', name: 'cantidad_piezas' },
            { id: 'observaciones', name: 'observaciones' },
            { id: 'retrabajo', name: 'retrabajo' }
        ];

        fields.forEach(field => {
            const element = this.dom.get(field.id);
            if (element) {
                const value = element.value || '';
                formData.append(field.name, value);
                console.log(`Campo ${field.name}: "${value}"`);
            } else {
                console.warn(`Elemento no encontrado: ${field.id}`);
                formData.append(field.name, '');
            }
        });

        const selectedDefects = window.defectManager.getSelectedDefects();
        if (selectedDefects.length > 0) {
            selectedDefects.forEach(defecto => {
                formData.append('defectos[]', defecto);
            });
        }

        const files = window.fileManager.getFiles();
        if (files.length > 0) {
            files.forEach((file, index) => {
                formData.append('evidencias[]', file);
                console.log(`Archivo ${index + 1}: ${file.name} (${file.size} bytes)`);
            });
        }

        return formData;
    }

    handleSubmitSuccess(result) {
        Utils.showAlert('¡Hallazgo registrado exitosamente!');
        this.dom.showSuccess();
        this.resetForm();
        Utils.vibrate([100, 50, 100]);
    }

    resetForm() {
        this.dom.get('form')?.reset();
        this.setCurrentDate();
        window.fileManager.files = [];
        window.fileManager.updateUI();
        window.defectManager.reset();
        this.dom.get('form')?.classList.remove('was-validated');
    }
}

// ===== FUNCIONES GLOBALES =====
window.testAndroidConnection = async function() {
    console.log('🤖 Probando conectividad Android básica...');
    
    try {
        const response = await fetch('test_android_connection.php', { 
            method: 'GET',
            headers: {
                'X-Android-App': 'HallazgosQuality',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        console.log('🤖 Resultado Android:', result);
        
        const deviceType = result.is_android ? '📱 Android' : '💻 Otro dispositivo';
        alert(`✅ Conectividad básica OK!\n\n` +
              `Dispositivo: ${deviceType}\n` +
              `Hora servidor: ${result.server_time}\n\n` +
              `Ahora prueba el envío de formulario con el botón "🧪 Test Form".`);
              
    } catch (error) {
        console.error('❌ Error Android:', error);
        alert(`❌ Error de conectividad: ${error.message}\n\n` +
              `💡 Sugerencias:\n` +
              `• Verifica tu conexión WiFi o datos móviles\n` +
              `• Reinicia el navegador\n` +
              `• Intenta cambiar de red`);
    }
};

window.testAndroidConfig = async function() {
    console.log('🔧 Probando configuración del servidor para archivos grandes...');
    
    try {
        const response = await fetch('test_android_config.php', { 
            method: 'GET',
            headers: {
                'X-Android-App': 'HallazgosQuality',
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const result = await response.json();
        console.log('🔧 Configuración del servidor:', result);
        
        const config = result.configuration;
        const analysis = result.capacity_analysis;
        
        let configMsg = `🔧 CONFIGURACIÓN DEL SERVIDOR\n\n`;
        configMsg += `📱 Dispositivo: ${result.is_android ? 'Android' : 'Otro'}\n`;
        configMsg += `🏛️ PHP: ${result.php_version}\n\n`;
        
        configMsg += `📏 Límites configurados:\n`;
        configMsg += `• Por archivo: ${config.upload_max_filesize}\n`;
        configMsg += `• Total POST: ${config.post_max_size}\n`;
        configMsg += `• Máx archivos: ${config.max_file_uploads}\n`;
        configMsg += `• Timeout: ${config.max_execution_time}s\n`;
        configMsg += `• Memoria: ${config.memory_limit}\n\n`;
        
        configMsg += `🎯 Capacidad para tu caso de uso:\n`;
        configMsg += `• Archivos esperados: ${analysis.expected_max_files}\n`;
        configMsg += `• Tamaño por archivo: ${analysis.expected_max_file_size}\n`;
        configMsg += `• Capacidad total: ${analysis.expected_total_capacity}\n\n`;
        
        if (analysis.can_handle_expected_load) {
            configMsg += `✅ El servidor PUEDE manejar 10 fotos de 5MB cada una!\n\n`;
            configMsg += `🎉 Configuración óptima para tu caso de uso.`;
        } else {
            configMsg += `❌ El servidor NO puede manejar la carga esperada.\n\n`;
            configMsg += `⚠️ Limitaciones encontradas:\n`;
            analysis.limitations.forEach(limitation => {
                configMsg += `• ${limitation}\n`;
            });
        }
        
        alert(configMsg);
        
    } catch (error) {
        console.error('❌ Error verificando configuración:', error);
        alert(`❌ Error verificando configuración: ${error.message}\n\n` +
              `Esto puede indicar problemas de configuración del servidor.`);
    }
};

window.testAndroidFormSubmit = async function() {
    console.log('🧪 Probando envío de formulario Android (sin guardar)...');
    
    try {
        // Crear FormData de prueba
        const testFormData = new FormData();
        testFormData.append('test_mode', 'true');
        testFormData.append('fecha', '2024-01-01');
        testFormData.append('area', 'Plasma');
        testFormData.append('estacion', 'Estación 1');
        testFormData.append('no_ensamble', '4-200-00410');
        testFormData.append('modelo', 'Test Model');
        testFormData.append('job_order', 'TEST123');
        testFormData.append('observaciones', 'Prueba desde Android');
        testFormData.append('retrabajo', 'No');
        testFormData.append('defectos[]', 'Otros');
        testFormData.append('id_usuario', '3');
        
        const response = await fetch('guardar_hallazgo_multiple.php', { 
            method: 'POST',
            body: testFormData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Android-App': 'HallazgosQuality',
                'X-Test-Mode': 'true'
            }
        });
        
        console.log('🧪 Response status:', response.status);
        console.log('🧪 Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('🧪 Resultado de prueba:', result);
        
        alert(`✅ Prueba de formulario exitosa!\n\n` +
              `Status: ${response.status}\n` +
              `Mensaje: ${result.message || 'OK'}\n` +
              `El envío real de formularios debería funcionar.`);
              
    } catch (error) {
        console.error('❌ Error en prueba de formulario:', error);
        alert(`❌ Error en prueba de formulario: ${error.message}\n\n` +
              `Este es el mismo error que tendrías al enviar el formulario real.\n` +
              `Posibles causas:\n` +
              `• Problema de configuración del servidor\n` +
              `• Headers bloqueados\n` +
              `• Problema específico de Android con POST requests`);
    }
};

window.testAndroidFormWithFiles = async function() {
    console.log('📁 Probando envío con archivos reales desde formulario actual...');
    
    try {
        // Verificar si hay archivos seleccionados
        const files = window.fileManager.getFiles();
        // Si no hay archivos, también permitimos la prueba sin adjuntos
        if (!files || files.length === 0) {
            alert('ℹ️ No seleccionaste fotos. Se realizará la prueba de envío SIN archivos.');
        }
        
        // Mostrar información sobre los archivos
        let totalSize = 0;
        let largeFiles = 0;
        let fileInfo = files.map((file, index) => {
            totalSize += file.size;
            if (file.size > 5 * 1024 * 1024) largeFiles++;
            return `${index + 1}. ${file.name} (${Utils.formatFileSize(file.size)})`;
        }).join('\n');
        
    const avgSize = files.length > 0 ? totalSize / files.length : 0;
        
        console.log(`📊 Análisis de archivos antes del test:`);
    console.log(`- Total archivos: ${files.length}`);
        console.log(`- Tamaño total: ${Utils.formatFileSize(totalSize)}`);
        console.log(`- Tamaño promedio: ${Utils.formatFileSize(avgSize)}`);
        console.log(`- Archivos > 5MB: ${largeFiles}`);
        
        // Confirmar test con archivos grandes
        if (totalSize > 50 * 1024 * 1024) {
            const confirmMsg = `🚨 TEST CON ARCHIVOS GRANDES\n\n` +
                              `Archivos: ${files.length}\n` +
                              `Tamaño total: ${Utils.formatFileSize(totalSize)}\n` +
                              `Archivos grandes (>5MB): ${largeFiles}\n\n` +
                              `⏱️ Esto puede tomar 30-60 segundos.\n` +
                              `¿Continuar con el test?`;
            
            if (!confirm(confirmMsg)) {
                alert('Test cancelado. Puedes probar con menos fotos o fotos más pequeñas.');
                return;
            }
        }
        
        // Crear FormData con datos del formulario actual + test_mode
        const form = document.getElementById('form-hallazgo');
        const formData = new FormData(form);
        
        // Forzar modo test
        formData.append('test_mode', 'true');
        
        // Agregar defectos seleccionados
        const selectedDefects = window.defectManager.getSelectedDefects();
        selectedDefects.forEach(defecto => {
            formData.append('defectos[]', defecto);
        });
        
        // Comprimir y agregar archivos
        console.log('🔄 Comprimiendo archivos grandes si es necesario...');
        let processedSize = 0;
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            let processedFile = file;
            
            // Comprimir si es mayor a 5MB
            if (file.type.startsWith('image/') && file.size > 5 * 1024 * 1024) {
                console.log(`🔄 Comprimiendo ${file.name}...`);
                try {
                    processedFile = await window.formManager.compressImageForAndroid(file, 4 * 1024 * 1024);
                    const reduction = ((file.size - processedFile.size) / file.size * 100).toFixed(1);
                    console.log(`✅ ${file.name}: ${Utils.formatFileSize(file.size)} → ${Utils.formatFileSize(processedFile.size)} (${reduction}% reducción)`);
                } catch (error) {
                    console.warn(`⚠️ No se pudo comprimir ${file.name}:`, error.message);
                }
            }
            
            processedSize += processedFile.size;
            formData.append('evidencias[]', processedFile);
            console.log(`📁 Test archivo ${i + 1}: ${processedFile.name} (${Utils.formatFileSize(processedFile.size)})`);
        }
        
        console.log(`📤 Enviando test con archivos (${Utils.formatFileSize(processedSize)} total)...`);
        
        const startTime = Date.now();
        
        const response = await fetch('guardar_hallazgo_multiple.php', { 
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Android-App': 'HallazgosQuality',
                'X-Test-Mode': 'true',
                'X-Test-With-Files': 'true'
            }
        });
        
        const endTime = Date.now();
        const duration = ((endTime - startTime) / 1000).toFixed(1);
        
        console.log('📁 Response status:', response.status);
        console.log(`⏱️ Tiempo de envío: ${duration} segundos`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('📁 Resultado test con archivos:', result);
        
        alert(`✅ TEST CON ARCHIVOS EXITOSO!\n\n` +
              `📊 Estadísticas:\n` +
              `• Archivos enviados: ${files.length}\n` +
              `• Tamaño original: ${Utils.formatFileSize(totalSize)}\n` +
              `• Tamaño final: ${Utils.formatFileSize(processedSize)}\n` +
              `• Tiempo de envío: ${duration} segundos\n` +
              `• Velocidad: ${Utils.formatFileSize(processedSize / duration)}/seg\n\n` +
              `🎉 El envío real de formularios funcionará perfectamente!\n\n` +
              `Mensaje servidor: ${result.message || 'OK'}`);
              
    } catch (error) {
        console.error('❌ Error en test con archivos:', error);
        
        let errorMsg = `❌ Test con archivos falló: ${error.message}\n\n`;
        
        if (error.message.includes('HTTP 413')) {
            errorMsg += `💡 Error 413: Archivos muy grandes para el servidor\n• Algunos archivos pueden estar cerca del límite\n• El servidor puede necesitar más configuración\n• Intenta con fotos de menor resolución`;
        } else if (error.message.includes('timeout') || error.message.includes('AbortError')) {
            errorMsg += `💡 Timeout: Los archivos tardaron mucho en subir\n• Normal con archivos grandes en Android\n• La compresión automática debería ayudar\n• Verifica tu conexión WiFi`;
        } else if (error.message.includes('Failed to fetch')) {
            errorMsg += `💡 Network Error: Problema de red con archivos grandes\n• La compresión redujo el tamaño pero aún hay problemas\n• Puede ser límite del navegador Android\n• Intenta con menos archivos (5 por vez)\n• Verifica que tengas buena señal`;
        } else if (error.message.includes('NetworkError')) {
            errorMsg += `💡 Error específico de Android con archivos grandes\n• Incluso después de compresión hay problemas\n• Intenta dividir en grupos más pequeños\n• Usa WiFi en lugar de datos móviles\n• Reinicia el navegador`;
        } else {
            errorMsg += `💡 Error inesperado con archivos grandes\n• La compresión automática funcionó\n• Pero hay otro problema en el envío\n• Intenta con menos archivos primero\n• Verifica la configuración del servidor`;
        }
        
        alert(errorMsg);
    }
};

window.testAndroidFormSubmit = async function() {
    console.log('� Probando envío de formulario Android (sin guardar)...');
    
    try {
        // Crear FormData de prueba
        const testFormData = new FormData();
        testFormData.append('test_mode', 'true');
        testFormData.append('fecha', '2024-01-01');
        testFormData.append('area', 'Plasma');
        testFormData.append('estacion', 'Estación 1');
        testFormData.append('no_ensamble', '4-200-00410');
        testFormData.append('modelo', 'Test Model');
        testFormData.append('job_order', 'TEST123');
        testFormData.append('observaciones', 'Prueba desde Android');
        testFormData.append('retrabajo', 'No');
        testFormData.append('defectos[]', 'Otros');
        testFormData.append('id_usuario', '3');
        
        const response = await fetch('guardar_hallazgo_multiple.php', { 
            method: 'POST',
            body: testFormData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Android-App': 'HallazgosQuality',
                'X-Test-Mode': 'true'
            }
        });
        
        console.log('🧪 Response status:', response.status);
        console.log('🧪 Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('� Resultado de prueba:', result);
        
        alert(`✅ Prueba de formulario exitosa!\n\n` +
              `Status: ${response.status}\n` +
              `Mensaje: ${result.message || 'OK'}\n` +
              `El envío real de formularios debería funcionar.`);
              
    } catch (error) {
        console.error('❌ Error en prueba de formulario:', error);
        alert(`❌ Error en prueba de formulario: ${error.message}\n\n` +
              `Este es el mismo error que tendrías al enviar el formulario real.\n` +
              `Posibles causas:\n` +
              `• Problema de configuración del servidor\n` +
              `• Headers bloqueados\n` +
              `• Problema específico de Android con POST requests`);
    }
};

window.toggleDebugMode = function() {
    AppConfig.DEBUG_MODE = !AppConfig.DEBUG_MODE;
    
    const statusEl = document.getElementById('debugStatus');
    const btnEl = document.getElementById('toggleDebugBtn');
    
    if (statusEl) statusEl.textContent = AppConfig.DEBUG_MODE ? 'ON' : 'OFF';
    if (btnEl) btnEl.className = AppConfig.DEBUG_MODE ? 'btn btn-danger' : 'btn btn-info';
    
    console.log('Debug mode:', AppConfig.DEBUG_MODE);
    alert(`Debug ${AppConfig.DEBUG_MODE ? 'ON' : 'OFF'}\nEndpoint: ${AppConfig.DEBUG_MODE ? 'test_form.php' : 'guardar_hallazgo_multiple.php'}`);
};

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', () => {
    // Crear instancias globales
    window.fileManager = new FileManager();
    window.defectManager = new DefectManager();
    window.formManager = new FormManager();
    
    // Configurar eventos de drag & drop
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('evidencias');
    
    if (fileUploadArea) {
        fileUploadArea.addEventListener('click', () => fileInput?.click());
    }
    
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            window.fileManager.handleFileSelection(e.target.files);
        });
    }
    
    // Funciones globales para compatibilidad
    window.removeFile = (index) => window.fileManager.removeFile(index);
    window.removeDefect = (defect) => window.defectManager.removeDefect(defect);
    
    console.log('🚀 Sistema de Hallazgos iniciado correctamente');
});
