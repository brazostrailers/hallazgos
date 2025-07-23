<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    // En modo de desarrollo o testing, permitir acceso con usuario temporal
    $is_development = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '192.168') !== false);
    
    if ($is_development && (isset($_GET['test']) || isset($_GET['demo']))) {
        // Crear usuario temporal para testing
        $_SESSION['usuario'] = [
            'id' => 3, // Samuel
            'nombre' => 'Usuario Demo',
            'correo' => 'demo@test.com',
            'rol' => 'calidad'
        ];
        $demo_mode = true;
    } else {
        header('Location: index.php');
        exit;
    }
}

$user_name = $_SESSION['usuario']['nombre'] ?? 'Usuario';
$user_id = $_SESSION['usuario']['id'] ?? 1;
$demo_mode = $demo_mode ?? false;

// Debug: Mostrar datos de sesión en comentario HTML
/*
DEBUG SESSION:
User ID: <?php echo $user_id; ?>
User Name: <?php echo $user_name; ?>
Demo Mode: <?php echo $demo_mode ? 'SI' : 'NO'; ?>
Session Data: <?php echo print_r($_SESSION['usuario'] ?? 'No session', true); ?>
*/
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>🤖 Registrar Hallazgo - Android Optimized</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  
  <!-- Optimizaciones específicas para Android -->
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="format-detection" content="telephone=no">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="Sistema Android de registro de hallazgos de calidad">
  
  <!-- PWA y tema para Android -->
  <meta name="theme-color" content="#667eea">
  <meta name="apple-mobile-web-app-capable" content="no">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Usar desde Android">
  
  <link rel="icon" type="image/png" href="assets/img/logo.jpg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/dashboard.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Guardando hallazgo...</div>
  </div>

  <!-- Success Overlay -->
  <div class="success-overlay" id="successOverlay">
    <div class="success-icon">✅</div>
    <div class="success-text">¡Hallazgo registrado exitosamente!</div>
    <div class="success-subtext">El registro se ha guardado correctamente</div>
  </div>

  <div class="container py-3">
    <!-- Header -->
    <div class="app-header">
      <img src="assets/img/logo.jpg" alt="Logo" class="app-logo">
      <h1 class="app-title">Sistema de Hallazgos</h1>
      <p class="app-subtitle">Registro de calidad móvil - Bienvenido, <?php echo htmlspecialchars($user_name); ?></p>
      <div class="header-actions">
        <button type="button" id="testAndroidBtn" class="btn btn-warning me-2" onclick="testAndroidConnection()" style="display: none;">
          🤖 Test Conexión
        </button>
        <button type="button" id="testConfigBtn" class="btn btn-secondary me-2" onclick="testAndroidConfig()" style="display: none;">
          🔧 Test Config
        </button>
        <button type="button" id="testFormBtn" class="btn btn-info me-2" onclick="testAndroidFormSubmit()" style="display: none;">
          🧪 Test Form
        </button>
        <button type="button" id="testFilesBtn" class="btn btn-success me-2" onclick="testAndroidFormWithFiles()" style="display: none;">
          📁 Test Files
        </button>
        <button type="button" id="traducirBtn" class="btn translate-btn">
          🌐 <span id="translateText">Traducir al Inglés</span>
        </button>
        <button type="button" id="logoutBtn" class="btn btn-outline-danger ms-2" onclick="cerrarSesion()">
          <i class="fas fa-sign-out-alt me-1"></i>🚪 Cerrar Sesión
        </button>
      </div>
    </div>

    <!-- Formulario Principal -->
    <div class="card">
      <div class="card-body">
        <form id="form-hallazgo" method="post" enctype="multipart/form-data" novalidate>
          
          <!-- Campo oculto para ID de usuario -->
          <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($user_id); ?>">
          
          <!-- Fecha -->
          <div class="form-section">
            <label for="fecha" class="form-label">📅 <span data-translate="date">Fecha</span></label>
            <input type="date" class="form-control" name="fecha" id="fecha" required>
            <div class="invalid-feedback"><span data-translate="selectDate">Por favor selecciona una fecha</span></div>
          </div>

          <!-- Área -->
          <div class="form-section">
            <label for="area" class="form-label">🏭 <span data-translate="area">Área</span></label>
            <select class="form-select" name="area" id="area" required>
              <option value="" data-translate="selectArea">Selecciona un área</option>
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
            <div class="invalid-feedback"><span data-translate="selectAreaError">Por favor selecciona un área</span></div>
          </div>

          <!-- Estación -->
          <div class="form-section">
            <label for="estacion" class="form-label">🔧 <span data-translate="station">Estación</span></label>
            <select class="form-select" name="estacion" id="estacion" required>
              <option value="" data-translate="selectStation">Selecciona una estación</option>
            </select>
            <div class="invalid-feedback"><span data-translate="selectStationError">Por favor selecciona una estación</span></div>
          </div>

          <!-- Número de Ensamble -->
          <div class="form-section">
            <label for="no_ensamble" class="form-label">🔢 <span data-translate="assemblyNumber">Número de Ensamble</span></label>
            <input class="form-control" name="no_ensamble" id="no_ensamble" list="ensambles" required autocomplete="off" data-translate-placeholder="enterAssemblyNumber">
            <datalist id="ensambles"></datalist>
            <div class="invalid-feedback"><span data-translate="enterAssemblyNumberError">Por favor ingresa el número de ensamble</span></div>
          </div>

          <!-- Modelo -->
          <div class="form-section">
            <label for="modelo" class="form-label">🚗 <span data-translate="model">Modelo</span></label>
            <input type="text" class="form-control" name="modelo" id="modelo" required data-translate-placeholder="autoFill">
            <div class="invalid-feedback"><span data-translate="selectModelError">Por favor selecciona un modelo</span></div>
          </div>

          <!-- Job Order -->
          <div class="form-section">
            <label for="job_order" class="form-label">📋 <span data-translate="jobOrder">Job Order</span></label>
            <input type="text" class="form-control" name="job_order" id="job_order" maxlength="100" required autocomplete="off" data-translate-placeholder="enterJobOrder">
            <div class="invalid-feedback"><span data-translate="enterJobOrderError">Por favor ingresa el Job Order</span></div>
          </div>

          <!-- Número de Parte -->
          <div class="form-section">
            <label for="no_parte" class="form-label">🔩 <span data-translate="partNumber">Número de Parte</span> (<span data-translate="optional">Opcional</span>)</label>
            <input type="text" class="form-control" name="no_parte" id="no_parte" maxlength="100" autocomplete="off" data-translate-placeholder="optional">
          </div>

          <!-- Defectos Múltiples -->
          <div class="form-section">
            <label class="form-label">⚠️ <span data-translate="defects">Defectos</span></label>
            <div class="multi-select-container">
              <div class="multi-select-display" id="defectDisplay">
                <span class="multi-select-placeholder" data-translate="selectDefects">Selecciona uno o más defectos</span>
              </div>
              <div class="multi-select-dropdown" id="defectDropdown">
                <!-- Las opciones se llenarán dinámicamente -->
              </div>
            </div>
            <div class="invalid-feedback" id="defectError" style="display: none;"><span data-translate="selectDefectsError">Por favor selecciona al menos un defecto</span></div>
          </div>

          <!-- Observaciones -->
          <div class="form-section">
            <label for="observaciones" class="form-label">📝 <span data-translate="observations">Observaciones</span></label>
            <textarea class="form-control" name="observaciones" id="observaciones" rows="4" required data-translate-placeholder="describeDetails"></textarea>
            <div class="invalid-feedback"><span data-translate="addObservationsError">Por favor agrega observaciones</span></div>
          </div>

          <!-- Retrabajo -->
          <div class="form-section">
            <label for="retrabajo" class="form-label">🔄 <span data-translate="rework">Retrabajo</span></label>
            <select class="form-select" id="retrabajo" name="retrabajo" required>
              <option value="" data-translate="select">Selecciona</option>
              <option value="Si" data-translate="yes">Sí</option>
              <option value="No" data-translate="no">No</option>
            </select>
            <div class="invalid-feedback"><span data-translate="selectReworkError">Por favor selecciona si es retrabajo</span></div>
          </div>

          <!-- Subida de Archivos Mejorada -->
          <div class="form-section">
            <label class="form-label">📸 <span data-translate="photos">Fotos</span> (<span data-translate="evidence">Evidencias</span>)</label>
            <div class="file-upload-area" id="fileUploadArea">
              <div class="file-upload-icon">📷</div>
              <div class="file-upload-text" data-translate="tapToAddPhotos">Toca para agregar más fotos</div>
              <div class="file-upload-hint" data-translate="photoLimits">Hasta 10 fotos • Máximo 15MB cada una • Las fotos se van agregando</div>
              <input type="file" name="evidencias[]" id="evidencias" accept="image/*" multiple required class="file-input-hidden">
            </div>
            <div class="invalid-feedback" id="fileError" style="display: none;"><span data-translate="addPhotoError">Por favor agrega al menos una foto</span></div>
            
            <!-- Contador de archivos -->
            <div class="file-counter" id="fileCounter" style="display: none;">
              <span id="fileCountText">0 archivos seleccionados</span>
            </div>
            
            <!-- Preview de imágenes -->
            <div class="image-preview-container" id="imagePreview"></div>
          </div>

          <!-- Botón Submit -->
          <button type="submit" class="btn btn-primary" id="submitBtn">
            📤 <span data-translate="submitFinding">Registrar Hallazgo</span>
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Mostrar botones de prueba solo en Android
    if (/Android/.test(navigator.userAgent)) {
      document.getElementById('testAndroidBtn').style.display = 'inline-block';
      document.getElementById('testConfigBtn').style.display = 'inline-block';
      document.getElementById('testFormBtn').style.display = 'inline-block';
      document.getElementById('testFilesBtn').style.display = 'inline-block';
    }
    
    // Función para alternar modo debug
    function toggleDebugMode() {
      if (typeof AppConfig !== 'undefined') {
        AppConfig.DEBUG_MODE = !AppConfig.DEBUG_MODE;
        document.getElementById('debugStatus').textContent = AppConfig.DEBUG_MODE ? 'ON' : 'OFF';
        document.getElementById('toggleDebugBtn').className = AppConfig.DEBUG_MODE ? 
          'btn btn-danger' : 'btn btn-info';
        
        console.log('Modo debug:', AppConfig.DEBUG_MODE ? 'ACTIVADO' : 'DESACTIVADO');
        alert(`Modo debug ${AppConfig.DEBUG_MODE ? 'ACTIVADO' : 'DESACTIVADO'}\n\n` +
              `Endpoint: ${AppConfig.DEBUG_MODE ? 'test_form.php' : 'guardar_hallazgo_multiple.php'}`);
      }
    }

    // Función para cerrar sesión
    function cerrarSesion() {
      if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = 'logout.php';
      }
    }
  </script>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/dashboard-fixed.js"></script>
</body>
</html>