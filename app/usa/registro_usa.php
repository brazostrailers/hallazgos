<?php
session_start();

// Verificar que el usuario esté autenticado y tenga rol de USA
if (
    !isset($_SESSION['usuario']) ||
    ($_SESSION['usuario']['rol'] !== 'usa' && $_SESSION['usuario']['rol'] !== 'encargadousa')
) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Special Finding</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      -webkit-font-smoothing: antialiased;
    }
    
    /* Optimización para móviles */
    .container {
      padding: 0 !important;
      max-width: 100% !important;
    }
    
    .custom-card {
      border-radius: 0;
      box-shadow: none;
      border: none;
      min-height: 100vh;
      margin: 0 !important;
      max-width: none !important;
    }
    
    .custom-header {
      background: linear-gradient(135deg, #003366 0%, #004488 100%);
      color: #fff;
      border-radius: 0;
      padding: 20px 16px;
      text-align: center;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .custom-header img {
      width: 50px;
      height: 50px;
      object-fit: contain;
      margin-bottom: 8px;
    }
    
    .custom-header h3 {
      font-size: 1.25rem;
      font-weight: 600;
      margin: 0;
    }
    
    .card-body {
      padding: 16px !important;
      background: #fff;
    }
    
    .divider {
      border-bottom: 1px solid #dee2e6;
      margin: 16px 0;
    }
    
    /* Formulario optimizado para móvil */
    .form-label {
      font-weight: 600;
      font-size: 0.95rem;
      color: #495057;
      margin-bottom: 6px;
    }
    
    .form-control, .form-select {
      font-size: 16px !important; /* Evita zoom en iOS */
      padding: 12px 16px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      transition: border-color 0.2s;
      -webkit-appearance: none;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #003366;
      box-shadow: 0 0 0 0.2rem rgba(0, 51, 102, 0.1);
    }
    
    .form-select {
      background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E");
    }
    
    textarea.form-control {
      resize: vertical;
      min-height: 100px;
    }
    
    /* Botones optimizados para táctil */
    .btn {
      font-size: 16px;
      padding: 14px 24px;
      border-radius: 8px;
      font-weight: 600;
      min-height: 48px;
      touch-action: manipulation;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #003366 0%, #004488 100%);
      border: none;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .btn-primary:hover, .btn-primary:active {
      background: linear-gradient(135deg, #002244 0%, #003366 100%);
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .btn-outline-secondary {
      border-radius: 20px;
      font-size: 0.85rem;
      padding: 8px 16px;
      border: 1px solid #6c757d;
      color: #6c757d;
      min-height: auto;
    }
    
    .btn-outline-secondary:hover {
      background: #6c757d;
      color: white;
    }
    
    /* Área de evidencia fotográfica */
    .file-input-container {
      position: relative;
      display: block;
    }
    
    /* Ocultar los inputs nativos; serán disparados por botones */
    .file-input-container input[type="file"] {
      position: absolute;
      left: -9999px;
      width: 1px;
      height: 1px;
      opacity: 0;
      pointer-events: none;
    }
    
    .file-input-label {
      display: block;
      padding: 20px;
      border: 2px dashed #007bff;
      border-radius: 8px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
      background: #f8f9fa;
    }
    
    .file-input-label:hover {
      border-color: #0056b3;
      background: #e3f2fd;
    }
    
    .file-input-label i {
      font-size: 2rem;
      color: #007bff;
      margin-bottom: 8px;
    }
    
    .preview-container {
      margin-top: 16px;
    }
    
    .preview-image {
      display: inline-block;
      margin: 8px;
      position: relative;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .preview-image img {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 8px;
    }
    
    .preview-image .remove-image {
      position: absolute;
      top: 4px;
      right: 4px;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      font-size: 12px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    /* Defectos adicionales */
    .defecto-adicional {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 16px;
      position: relative;
    }
    
    .defecto-adicional .remove-defecto {
      position: absolute;
      top: 8px;
      right: 8px;
      background: #dc3545;
      color: white;
      border: none;
      border-radius: 50%;
      width: 28px;
      height: 28px;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .defecto-counter {
      background: #007bff;
      color: white;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 8px;
      display: inline-block;
    }
    
    /* Espaciado entre campos */
    .mb-3 {
      margin-bottom: 20px !important;
    }
    
    /* Validación visual */
    .was-validated .form-control:invalid,
    .was-validated .form-select:invalid,
    .form-control.is-invalid,
    .form-select.is-invalid,
    .file-input-label.is-invalid {
      border-color: #dc3545;
    }
    
    .was-validated .form-control:valid,
    .was-validated .form-select:valid,
    .form-control.is-valid,
    .form-select.is-valid,
    .file-input-label.is-valid {
      border-color: #28a745;
    }
    
    .file-input-label.is-invalid {
      border-color: #dc3545 !important;
      background: #f8d7da;
    }
    
    .file-input-label.is-valid {
      border-color: #28a745 !important;
      background: #d1e7dd;
    }
    
    .invalid-feedback {
      display: block;
      font-size: 0.85rem;
      margin-top: 4px;
    }
    
    .is-invalid ~ .invalid-feedback {
      display: block;
    }
    
    /* Mejoras para accesibilidad táctil */
    @media (max-width: 576px) {
      .custom-header {
        padding: 16px 12px;
      }
      
      .card-body {
        padding: 12px !important;
      }
      
      .btn {
        width: 100%;
        margin-bottom: 8px;
      }
      
      .d-flex.justify-content-end {
        justify-content: center !important;
      }
    }
    
    /* Evitar zoom en campos de entrada en iOS Safari */
    @supports (-webkit-touch-callout: none) {
      .form-control,
      .form-select,
      textarea {
        font-size: 16px !important;
      }
    }
  </style>
</head>
<body>
  <div class="container py-0">
    <div class="card custom-card mx-auto">
      <div class="custom-header">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="user-info">
            <small style="opacity: 0.8;">👤 <?php echo htmlspecialchars($usuario['nombre']); ?></small>
          </div>
          <div>
            <a href="../logout.php" class="btn btn-outline-light btn-sm" style="font-size: 0.75rem; padding: 2px 8px;">
              🚪 Salir
            </a>
          </div>
        </div>
        <img src="/assets/img/logo.jpg" alt="Logo">
        <h3 class="mt-2 mb-0" id="title">New Special Finding</h3>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-end mb-3">
          <button type="button" id="translateBtn" class="btn btn-outline-secondary btn-sm">
            Español
          </button>
        </div>
        <form id="form-especial" method="post" enctype="multipart/form-data" novalidate>
          <div class="mb-3">
            <label for="fecha" class="form-label" id="lbl_fecha">Date</label>
            <input type="date" class="form-control" name="fecha" id="fecha" required>
            <div class="invalid-feedback" id="inv_fecha">Please select a date</div>
          </div>
          
          <div class="mb-3">
            <label for="job_order" class="form-label" id="lbl_job_order">Job Order</label>
            <input type="text" class="form-control" name="job_order" id="job_order" 
                   maxlength="100" required autocomplete="off" 
                   placeholder="Enter job order number">
            <div class="invalid-feedback" id="inv_job_order">Please enter the Job Order</div>
          </div>
          
          <div class="mb-3">
            <label for="warehouse" class="form-label" id="lbl_warehouse">Warehouse</label>
            <input type="text" class="form-control" name="warehouse" id="warehouse" 
                   maxlength="100" required autocomplete="off" 
                   placeholder="Enter warehouse location">
            <div class="invalid-feedback" id="inv_warehouse">Please enter the warehouse</div>
          </div>
          
          <div class="mb-3">
            <label for="noparte" class="form-label" id="lbl_noparte">Part Number (optional)</label>
            <input type="text" class="form-control" name="noparte" id="noparte" 
                   maxlength="100" autocomplete="off" 
                   placeholder="Enter part number if available">
            <div class="invalid-feedback" id="inv_noparte">Please enter the part number</div>
          </div>
          
          <div class="mb-3">
            <label for="defecto" class="form-label" id="lbl_defecto">Defect Type</label>
            <select class="form-select" name="defecto" id="defecto" required>
              <option value="">Select a defect type</option>
              <option value="Incorrect Account">Incorrect Account</option>
              <option value="Incorrect Measurement">Incorrect Measurement</option>
              <option value="Incorrect Stock Code">Incorrect Stock Code</option>
              <option value="Welding">Welding</option>
              <option value="Shipping Damage">Shipping Damage</option>
              <option value="Unsecured Cargo">Unsecured Cargo</option>
            </select>
            <div class="invalid-feedback" id="inv_defecto">Please select a defect type</div>
          </div>
          
          <!-- Botón para agregar más defectos -->
          <div class="mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm w-100" id="addDefectoBtn">
              ➕ Add Another Defect
            </button>
          </div>
          
          <!-- Contenedor para defectos adicionales -->
          <div id="defectosAdicionales"></div>
          
          <div class="mb-3">
            <label class="form-label" id="lbl_evidencia">Photographic Evidence</label>
            <div class="file-input-container">
              <!-- Botones de acción -->
              <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-outline-primary flex-fill" id="btn-take-photo">📷 Take photo</button>
                <button type="button" class="btn btn-outline-secondary flex-fill" id="btn-choose-gallery">🖼️ Gallery</button>
              </div>
              <!-- Inputs ocultos: cámara (una por toma) y galería (múltiples) -->
              <input type="file" id="evidencia_camera" accept="image/*" capture="environment">
              <input type="file" id="evidencia_gallery" accept="image/*" multiple>
              <label class="file-input-label" id="fileInputLabel">
                <div>📷</div>
                <div style="font-weight: 600; margin-bottom: 4px;" id="file_label_main">Tap to take photos or select images</div>
                <div style="font-size: 0.85rem; color: #6c757d;" id="file_label_sub">JPG, PNG or GIF (max 5MB each) - Multiple files allowed</div>
              </label>
              <small class="text-muted">Tip: On Android, the Camera button opens the camera directly.</small>
            </div>
            <div class="invalid-feedback" id="inv_evidencia">Please add at least one photo as evidence</div>
            <div class="preview-container" id="previewContainer">
              <!-- Las imágenes se mostrarán aquí -->
            </div>
          </div>
          
          <div class="mb-3">
            <label for="observaciones" class="form-label" id="lbl_observaciones">Observations</label>
            <textarea class="form-control" name="observaciones" id="observaciones" 
                      rows="4" required placeholder="Describe the finding in detail..."></textarea>
            <div class="invalid-feedback" id="inv_observaciones">Please add observations</div>
          </div>
          
          <div class="divider"></div>
          
          <button type="submit" class="btn btn-primary w-100" id="submitBtn" onclick="console.log('Button clicked directly');">
            📝 Register Finding
          </button>
        </form>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM Content Loaded - JavaScript initialized');
  
  // Traducciones
  const translations = {
    en: {
      title: "New Special Finding",
  translateBtn: "Español",
      lbl_fecha: "Date",
      lbl_job_order: "Job Order",
      lbl_warehouse: "Warehouse",
      lbl_noparte: "Part Number (optional)",
      lbl_defecto: "Defect Type",
      lbl_evidencia: "Photographic Evidence",
      lbl_observaciones: "Observations",
      inv_fecha: "Please select a date",
      inv_job_order: "Please enter the Job Order",
      inv_warehouse: "Please enter the warehouse",
      inv_noparte: "Please enter the part number",
      inv_defecto: "Please select a defect type",
      inv_evidencia: "Please add at least one photo as evidence",
      inv_observaciones: "Please add observations",
      submitBtn: "📝 Register Finding",
      addDefectoBtn: "➕ Add Another Defect",
      removeDefectoBtn: "✖",
      defectoLabel: "Defect Type",
      placeholder_job_order: "Enter job order number",
      placeholder_warehouse: "Enter warehouse location", 
      placeholder_noparte: "Enter part number if available",
      placeholder_observaciones: "Describe the finding in detail...",
      file_label_main: "Tap to take photos or select images",
      file_label_sub: "JPG, PNG or GIF (max 5MB each) - Multiple files allowed",
  take_photo_btn: "📷 Take photo",
  gallery_btn: "🖼️ Gallery",
      alert_file_too_large: "The file {filename} is too large. Maximum 5MB allowed.",
      alert_invalid_file: "{filename} is not a valid image file",
      alert_no_images: "Please select at least one image as evidence",
      alert_success: "Record saved successfully",
      alert_connection_error: "Connection error. Check your internet and try again.",
      loading_text: "⏳ Registering...",
      success_text: "✅ Registered!",
      logout_btn: "🚪 Logout",
      defect_options: [
        "Select a defect type",
        "Incorrect Account",
        "Incorrect Measurement",
        "Incorrect Stock Code",
        "Welding",
        "Shipping Damage",
        "Unsecured Cargo"
      ]
    },
    es: {
      title: "Nuevo Hallazgo Especial",
  translateBtn: "English",
      lbl_fecha: "Fecha",
      lbl_job_order: "Job Order",
      lbl_warehouse: "Almacén",
      lbl_noparte: "Número de Parte (opcional)",
      lbl_defecto: "Tipo de Defecto",
      lbl_evidencia: "Evidencia Fotográfica",
      lbl_observaciones: "Observaciones",
      inv_fecha: "Por favor selecciona una fecha",
      inv_job_order: "Por favor ingresa el Job Order",
      inv_warehouse: "Por favor ingresa el almacén",
      inv_noparte: "Por favor ingresa el número de parte",
      inv_defecto: "Por favor selecciona un tipo de defecto",
      inv_evidencia: "Por favor agrega al menos una foto como evidencia",
      inv_observaciones: "Por favor agrega observaciones",
      submitBtn: "📝 Registrar Hallazgo",
      addDefectoBtn: "➕ Agregar Otro Defecto",
      removeDefectoBtn: "✖",
      defectoLabel: "Tipo de Defecto",
      placeholder_job_order: "Ingresa el número de job order",
      placeholder_warehouse: "Ingresa la ubicación del almacén",
      placeholder_noparte: "Ingresa el número de parte si está disponible",
      placeholder_observaciones: "Describe el hallazgo en detalle...",
      file_label_main: "Toca para tomar fotos o seleccionar imágenes",
      file_label_sub: "JPG, PNG o GIF (máx 5MB cada una) - Múltiples archivos permitidos",
  take_photo_btn: "📷 Tomar foto",
  gallery_btn: "🖼️ Galería",
      alert_file_too_large: "El archivo {filename} es demasiado grande. Máximo 5MB permitido.",
      alert_invalid_file: "{filename} no es un archivo de imagen válido",
      alert_no_images: "Por favor selecciona al menos una imagen como evidencia",
      alert_success: "Registro guardado correctamente",
      alert_connection_error: "Error de conexión. Verifica tu internet e intenta de nuevo.",
      loading_text: "⏳ Registrando...",
      success_text: "✅ ¡Registrado!",
      logout_btn: "🚪 Salir",
      defect_options: [
        "Selecciona un tipo de defecto",
        "Cuenta incorrecta",
        "Medición incorrecta",
        "Código de stock incorrecto",
        "Soldadura",
        "Daño por envío",
        "Carga no asegurada"
      ]
    }
  };
  let currentLang = "en";

  function translateForm() {
    const t = translations[currentLang];
    
    // Textos principales
    document.getElementById("title").innerText = t.title;
  document.getElementById("translateBtn").innerText = t.translateBtn;
    document.getElementById("submitBtn").innerText = t.submitBtn;
    document.getElementById("addDefectoBtn").innerText = t.addDefectoBtn;
    
    // Botón de logout
    const logoutBtn = document.querySelector('a[href="../logout.php"]');
    if (logoutBtn) {
      logoutBtn.innerHTML = t.logout_btn;
    }
    
    // Labels
    document.getElementById("lbl_fecha").innerText = t.lbl_fecha;
    document.getElementById("lbl_job_order").innerText = t.lbl_job_order;
    document.getElementById("lbl_warehouse").innerText = t.lbl_warehouse;
    document.getElementById("lbl_noparte").innerText = t.lbl_noparte;
    document.getElementById("lbl_defecto").innerText = t.lbl_defecto;
    document.getElementById("lbl_evidencia").innerText = t.lbl_evidencia;
    document.getElementById("lbl_observaciones").innerText = t.lbl_observaciones;
    
    // Mensajes de validación
    document.getElementById("inv_fecha").innerText = t.inv_fecha;
    document.getElementById("inv_job_order").innerText = t.inv_job_order;
    document.getElementById("inv_warehouse").innerText = t.inv_warehouse;
    document.getElementById("inv_noparte").innerText = t.inv_noparte;
    document.getElementById("inv_defecto").innerText = t.inv_defecto;
    document.getElementById("inv_evidencia").innerText = t.inv_evidencia;
    document.getElementById("inv_observaciones").innerText = t.inv_observaciones;
    
    // Placeholders
    document.getElementById("job_order").placeholder = t.placeholder_job_order;
    document.getElementById("warehouse").placeholder = t.placeholder_warehouse;
    document.getElementById("noparte").placeholder = t.placeholder_noparte;
    document.getElementById("observaciones").placeholder = t.placeholder_observaciones;
    
    // File input label
    const fileLabelMain = document.getElementById('file_label_main');
    const fileLabelSub = document.getElementById('file_label_sub');
    if (fileLabelMain) fileLabelMain.textContent = t.file_label_main;
    if (fileLabelSub) fileLabelSub.textContent = t.file_label_sub;
    const takeBtn = document.getElementById('btn-take-photo');
    const galleryBtn = document.getElementById('btn-choose-gallery');
    if (takeBtn) takeBtn.innerText = t.take_photo_btn;
    if (galleryBtn) galleryBtn.innerText = t.gallery_btn;
    
    // Opciones del select de defectos
    const defectoSelect = document.getElementById("defecto");
    Array.from(defectoSelect.options).forEach((opt, idx) => {
      if (t.defect_options[idx]) opt.text = t.defect_options[idx];
    });
    
    // Actualizar defectos adicionales si existen
    const defectosAdicionales = document.querySelectorAll('.defecto-adicional');
    defectosAdicionales.forEach((defecto, index) => {
      // Actualizar contador
      const counter = defecto.querySelector('.defecto-counter');
      const label = currentLang === 'en' ? 'Defect' : 'Defecto';
      counter.textContent = `${label} #${index + 2}`;
      
      // Actualizar label
      const labelElement = defecto.querySelector('.form-label');
      labelElement.textContent = t.defectoLabel;
      
      // Actualizar opciones del select
      const select = defecto.querySelector('select');
      Array.from(select.options).forEach((opt, idx) => {
        if (t.defect_options[idx]) opt.text = t.defect_options[idx];
      });
      
      // Actualizar mensaje de validación
      const feedback = defecto.querySelector('.invalid-feedback');
      feedback.textContent = t.inv_defecto;
      
      // Actualizar tooltip del botón de eliminar
      const removeBtn = defecto.querySelector('.remove-defecto');
      removeBtn.title = currentLang === 'en' ? 'Remove defect' : 'Eliminar defecto';
    });
  }

    document.getElementById("translateBtn").addEventListener("click", function() {
      // Alternar idioma primero
      currentLang = currentLang === "en" ? "es" : "en";
      translateForm();
      // El botón debe mostrar el idioma CONTRARIO al actual
      const btn = document.getElementById("translateBtn");
      btn.innerText = currentLang === 'en' ? 'Español' : 'English';
  });

  // Validación y preview de imagen
  const form = document.getElementById('form-especial');
  const cameraInput = document.getElementById('evidencia_camera');
  const galleryInput = document.getElementById('evidencia_gallery');
  const fileInputLabel = document.getElementById('fileInputLabel');
  const previewContainer = document.getElementById('previewContainer');
  let selectedFiles = [];
  let defectoCounter = 1;
  
  console.log('Form element found:', form ? 'Yes' : 'No');
  console.log('Camera input found:', cameraInput ? 'Yes' : 'No');
  console.log('Gallery input found:', galleryInput ? 'Yes' : 'No');
  
  if (!form) {
    console.error('Form not found! ID: form-especial');
    return;
  }

  // Botones para abrir cámara o galería
  document.getElementById('btn-take-photo').addEventListener('click', function() {
    cameraInput.click();
  });
  document.getElementById('btn-choose-gallery').addEventListener('click', function() {
    galleryInput.click();
  });

  function handleFiles(filesArray) {
    const files = Array.from(filesArray);
    const t = translations[currentLang];
    
    files.forEach(file => {
      if (file.type.startsWith('image/')) {
        if (file.size > 5 * 1024 * 1024) {
          alert(t.alert_file_too_large.replace('{filename}', file.name));
          return;
        }
        
        selectedFiles.push(file);
        const reader = new FileReader();
        reader.onload = function(e) {
          const previewDiv = document.createElement('div');
          previewDiv.className = 'preview-image';
          previewDiv.innerHTML = `
            <img src="${e.target.result}" alt="Preview">
            <button type="button" class="remove-image" onclick="removeImage(this, '${file.name}')">✖</button>
          `;
          previewContainer.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
      } else {
        alert(t.alert_invalid_file.replace('{filename}', file.name));
      }
    });
    
    // Actualizar validación visual
    if (selectedFiles.length > 0) {
      fileInputLabel.classList.remove('is-invalid');
      fileInputLabel.classList.add('is-valid');
    }
  }

  // Eventos de cambio para inputs
  cameraInput.addEventListener('change', function(e) {
    handleFiles(e.target.files);
    // Limpia para permitir tomar la misma foto nuevamente si se desea
    e.target.value = '';
  });
  galleryInput.addEventListener('change', function(e) {
    handleFiles(e.target.files);
    e.target.value = '';
  });

  // Función para remover imagen
  window.removeImage = function(button, fileName) {
    const previewDiv = button.parentElement;
    previewDiv.remove();
    selectedFiles = selectedFiles.filter(file => file.name !== fileName);
    
    // Actualizar validación visual
    if (selectedFiles.length === 0) {
      fileInputLabel.classList.remove('is-valid');
      fileInputLabel.classList.add('is-invalid');
    }
  };

  // Agregar defecto adicional
  document.getElementById('addDefectoBtn').addEventListener('click', function() {
    console.log('Add defecto button clicked');
    defectoCounter++;
    const container = document.getElementById('defectosAdicionales');
    const t = translations[currentLang];
    
    const defectoDiv = document.createElement('div');
    defectoDiv.className = 'defecto-adicional';
    defectoDiv.innerHTML = `
      <div class="defecto-counter">${currentLang === 'en' ? 'Defect' : 'Defecto'} #${defectoCounter}</div>
      <button type="button" class="remove-defecto" onclick="removeDefecto(this)" title="${currentLang === 'en' ? 'Remove defect' : 'Eliminar defecto'}">✖</button>
      <label class="form-label">${t.defectoLabel}</label>
      <select class="form-select" name="defecto_adicional[]" required>
        <option value="">${t.defect_options[0]}</option>
        <option value="Incorrect Account">${t.defect_options[1]}</option>
        <option value="Incorrect Measurement">${t.defect_options[2]}</option>
        <option value="Incorrect Stock Code">${t.defect_options[3]}</option>
        <option value="Welding">${t.defect_options[4]}</option>
        <option value="Shipping Damage">${t.defect_options[5]}</option>
        <option value="Unsecured Cargo">${t.defect_options[6]}</option>
      </select>
      <div class="invalid-feedback">${t.inv_defecto}</div>
    `;
    
    container.appendChild(defectoDiv);
  });

  // Función para remover defecto
  window.removeDefecto = function(button) {
    const defectoDiv = button.parentElement;
    defectoDiv.remove();
    
    // Reordenar contadores
    const defectos = document.querySelectorAll('.defecto-adicional');
    defectos.forEach((defecto, index) => {
      const counter = defecto.querySelector('.defecto-counter');
      const label = currentLang === 'en' ? 'Defect' : 'Defecto';
      counter.textContent = `${label} #${index + 2}`;
    });
    defectoCounter = defectos.length + 1;
  };

  console.log('Adding form submit event listener...');
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Form submit triggered');
    
    // Prevenir doble envío
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn.disabled) {
      console.log('Button already disabled, preventing double submit');
      return;
    }
    
    // Validar campos obligatorios manualmente
    let isValid = true;
    const requiredFields = ['fecha', 'job_order', 'warehouse', 'defecto', 'observaciones'];
    
    requiredFields.forEach(fieldName => {
      const field = form.querySelector(`[name="${fieldName}"]`);
      if (!field || !field.value.trim()) {
        isValid = false;
        field.classList.add('is-invalid');
        console.log(`Field ${fieldName} is invalid`);
      } else {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
      }
    });
    
    // Validar defectos adicionales si existen
    const defectosAdicionales = form.querySelectorAll('select[name="defecto_adicional[]"]');
    defectosAdicionales.forEach(select => {
      if (!select.value) {
        isValid = false;
        select.classList.add('is-invalid');
      } else {
        select.classList.remove('is-invalid');
        select.classList.add('is-valid');
      }
    });
    
    // Validar que se hayan seleccionado imágenes
    const t = translations[currentLang];
    console.log('Selected files count:', selectedFiles.length);
    if (selectedFiles.length === 0) {
      isValid = false;
      fileInputLabel.classList.add('is-invalid');
      console.log('No images selected');
    } else {
      fileInputLabel.classList.remove('is-invalid');
      fileInputLabel.classList.add('is-valid');
    }
    
    if (!isValid) {
      console.log('Form validation failed - showing errors');
      form.classList.add('was-validated');
      
      // Enfocar el primer campo con error
      const firstInvalid = form.querySelector('.is-invalid');
      if (firstInvalid) {
        console.log('First invalid field:', firstInvalid.name || firstInvalid.id);
        firstInvalid.focus();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      
      if (selectedFiles.length === 0) {
        alert(t.alert_no_images);
      }
      return;
    }
    
    console.log('All validation passed, starting form submission...');
    
    // Deshabilitar botón y mostrar estado de carga
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = t.loading_text;
    
    const formData = new FormData();
    
    // Agregar campos del formulario
    formData.append('fecha', form.fecha.value);
    formData.append('job_order', form.job_order.value);
    formData.append('warehouse', form.warehouse.value);
    formData.append('noparte', form.noparte.value);
    formData.append('defecto', form.defecto.value);
    formData.append('observaciones', form.observaciones.value);
    
    // Agregar defectos adicionales
    const defectosAdicionalesSelects = form.querySelectorAll('select[name="defecto_adicional[]"]');
    defectosAdicionalesSelects.forEach((select, index) => {
      if (select.value) {
        formData.append(`defecto_adicional[${index}]`, select.value);
      }
    });
    
    // Agregar archivos seleccionados
    selectedFiles.forEach((file, index) => {
      formData.append(`evidencia_fotografica[${index}]`, file);
    });
    
    console.log('Sending form data...');
    fetch('../includes/guardar_hallazgo_usa.php', {
      method: 'POST',
      body: formData
    })
    .then(res => {
      console.log('Response received:', res.status);
      return res.json();
    })
    .then(data => {
      console.log('Response data:', data);
      if (data.success) {
        // Mostrar mensaje de éxito
        submitBtn.innerHTML = t.success_text;
        submitBtn.style.background = '#28a745';
        
        setTimeout(() => {
          alert(t.alert_success);
          form.reset();
          form.classList.remove('was-validated');
          
          // Limpiar imágenes y defectos adicionales
          selectedFiles = [];
          previewContainer.innerHTML = '';
          document.getElementById('defectosAdicionales').innerHTML = '';
          defectoCounter = 1;
          
          // Restaurar botón
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
          submitBtn.style.background = '';
          
          // Volver a poner la fecha de hoy
          const fechaInput = document.getElementById('fecha');
          const today = new Date();
          const yyyy = today.getFullYear();
          const mm = String(today.getMonth() + 1).padStart(2, '0');
          const dd = String(today.getDate()).padStart(2, '0');
          fechaInput.value = `${yyyy}-${mm}-${dd}`;
          
          // Scroll al inicio
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 1500);
      } else {
        alert('Error: ' + (data.error || 'No se pudo guardar'));
        // Restaurar botón
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    })
    .catch(err => {
      console.error('Error:', err);
      const t = translations[currentLang];
      alert(t.alert_connection_error);
      // Restaurar botón
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    });
  });

  // Fecha actual por defecto (compatible con todos los navegadores)
  const fechaInput = document.getElementById('fecha');
  if (fechaInput) {
    setTimeout(() => {
      const today = new Date();
      const yyyy = today.getFullYear();
      const mm = String(today.getMonth() + 1).padStart(2, '0');
      const dd = String(today.getDate()).padStart(2, '0');
      fechaInput.value = `${yyyy}-${mm}-${dd}`;
    }, 100);
  }

  // Inicializar traducción al cargar
  translateForm();
  // Actualizar textos de botones según idioma actual
  const t0 = translations[currentLang];
  document.getElementById('btn-take-photo').innerText = t0.take_photo_btn;
  document.getElementById('btn-choose-gallery').innerText = t0.gallery_btn;
  // Asegurar que el botón de idioma muestre el contrario al idioma actual
  const btn = document.getElementById('translateBtn');
  btn.innerText = currentLang === 'en' ? 'Español' : 'English';
});
  </script>
</body>
</html>