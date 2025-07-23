<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Special Finding</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f5f6fa;
    }
    .custom-card {
      border-radius: 18px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
      border: none;
    }
    .custom-header {
      background: #003366;
      color: #fff;
      border-radius: 18px 18px 0 0;
      padding: 1.5rem 1rem 1rem 1rem;
      text-align: center;
    }
    .custom-header img {
      width: 60px;
      margin-bottom: 0.5rem;
    }
    .divider {
      border-bottom: 1px solid #e0e0e0;
      margin: 1.5rem 0;
    }
    .form-label {
      font-weight: 500;
    }
    .btn-primary {
      background: #003366;
      border: none;
    }
    .btn-primary:hover {
      background: #002244;
    }
    .btn-outline-secondary {
      border-radius: 20px;
      font-size: 0.95rem;
      padding: 0.3rem 1.2rem;
    }
    .preview-container {
      text-align: center;
    }
    #preview-img {
      margin-top: 0.5rem;
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      background: #fff;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="card custom-card mx-auto" style="max-width: 540px;">
      <div class="custom-header">
        <img src="/assets/img/logo.jpg" alt="Logo">
        <h3 class="mt-2 mb-0" id="title">New Special Finding</h3>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-end mb-2">
          <button type="button" id="translateBtn" class="btn btn-outline-secondary btn-sm">
            Translate to Spanish
          </button>
        </div>
        <form id="form-especial" method="post" enctype="multipart/form-data" novalidate>
          <div class="divider"></div>
          <div class="mb-3">
            <label for="fecha" class="form-label" id="lbl_fecha">Date</label>
            <input type="date" class="form-control" name="fecha" id="fecha" required>
            <div class="invalid-feedback" id="inv_fecha">Please select a date</div>
          </div>
          <div class="mb-3">
            <label for="job_order" class="form-label" id="lbl_job_order">Job Order</label>
            <input type="text" class="form-control" name="job_order" id="job_order" maxlength="100" required autocomplete="off">
            <div class="invalid-feedback" id="inv_job_order">Please enter the Job Order</div>
          </div>
          <div class="mb-3">
            <label for="warehouse" class="form-label" id="lbl_warehouse">Warehouse</label>
            <input type="text" class="form-control" name="warehouse" id="warehouse" maxlength="100" required autocomplete="off">
            <div class="invalid-feedback" id="inv_warehouse">Please enter the warehouse</div>
          </div>
          <div class="mb-3">
            <label for="noparte" class="form-label" id="lbl_noparte">Part Number (optional)</label>
            <input type="text" class="form-control" name="noparte" id="noparte" maxlength="100" autocomplete="off">
            <div class="invalid-feedback" id="inv_noparte">Please enter the part number</div>
          </div>
         <div class="mb-3">
  <label for="defecto" class="form-label" id="lbl_defecto">Defect</label>
  <select class="form-select" name="defecto" id="defecto" required>
    <option value="">Select a defect</option>
    <option value="Incorrect Account">Incorrect Account</option>
    <option value="Incorrect Measurement">Incorrect Measurement</option>
    <option value="Incorrect Stock Code">Incorrect Stock Code</option>
    <option value="Welding">Welding</option>
    <option value="Shipping Damage">Shipping Damage</option>
    <option value="Unsecured Cargo">Unsecured Cargo</option>
  </select>
  <div class="invalid-feedback" id="inv_defecto">Please describe the defect</div>
</div>
          <div class="mb-3">
            <label class="form-label" id="lbl_evidencia">Photographic Evidence</label>
            <input type="file" name="evidencia_fotografica" id="evidencia_fotografica" accept="image/*" required>
            <div class="invalid-feedback" id="inv_evidencia">Please add a photo as evidence</div>
            <div class="preview-container mt-2">
              <img id="preview-img" src="#" alt="Preview" style="display:none; max-width:100%; max-height:180px;" />
            </div>
          </div>
          <div class="mb-3">
            <label for="observaciones" class="form-label" id="lbl_observaciones">Observations</label>
            <textarea class="form-control" name="observaciones" id="observaciones" rows="3" required></textarea>
            <div class="invalid-feedback" id="inv_observaciones">Please add observations</div>
          </div>
          <div class="divider"></div>
          <button type="submit" class="btn btn-primary w-100" id="submitBtn">
            Register Finding
          </button>
        </form>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
  // Traducciones
  const translations = {
    en: {
      title: "New Special Finding",
      translateBtn: "Translate to Spanish",
      lbl_fecha: "Date",
      lbl_job_order: "Job Order",
      lbl_warehouse: "Warehouse",
      lbl_noparte: "Part Number (optional)",
      lbl_defecto: "Defect",
      lbl_evidencia: "Photographic Evidence",
      lbl_observaciones: "Observations",
      inv_fecha: "Please select a date",
      inv_job_order: "Please enter the Job Order",
      inv_warehouse: "Please enter the warehouse",
      inv_noparte: "Please enter the part number",
      inv_defecto: "Please describe the defect",
      inv_evidencia: "Please add a photo as evidence",
      inv_observaciones: "Please add observations",
      submitBtn: "Register Finding",
      defect_options: [
        "Select a defect",
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
      translateBtn: "Traducir al Inglés",
      lbl_fecha: "Fecha",
      lbl_job_order: "Job Order",
      lbl_warehouse: "Almacén",
      lbl_noparte: "Número de Parte (opcional)",
      lbl_defecto: "Defecto",
      lbl_evidencia: "Evidencia Fotográfica",
      lbl_observaciones: "Observaciones",
      inv_fecha: "Por favor selecciona una fecha",
      inv_job_order: "Por favor ingresa el Job Order",
      inv_warehouse: "Por favor ingresa el almacén",
      inv_noparte: "Por favor ingresa el número de parte",
      inv_defecto: "Por favor describe el defecto",
      inv_evidencia: "Por favor agrega una foto como evidencia",
      inv_observaciones: "Por favor agrega observaciones",
      submitBtn: "Registrar Hallazgo",
      defect_options: [
        "Selecciona un defecto",
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
    document.getElementById("title").innerText = t.title;
    document.getElementById("translateBtn").innerText = t.translateBtn;
    document.getElementById("lbl_fecha").innerText = t.lbl_fecha;
    document.getElementById("lbl_job_order").innerText = t.lbl_job_order;
    document.getElementById("lbl_warehouse").innerText = t.lbl_warehouse;
    document.getElementById("lbl_noparte").innerText = t.lbl_noparte;
    document.getElementById("lbl_defecto").innerText = t.lbl_defecto;
    document.getElementById("lbl_evidencia").innerText = t.lbl_evidencia;
    document.getElementById("lbl_observaciones").innerText = t.lbl_observaciones;
    document.getElementById("inv_fecha").innerText = t.inv_fecha;
    document.getElementById("inv_job_order").innerText = t.inv_job_order;
    document.getElementById("inv_warehouse").innerText = t.inv_warehouse;
    document.getElementById("inv_noparte").innerText = t.inv_noparte;
    document.getElementById("inv_defecto").innerText = t.inv_defecto;
    document.getElementById("inv_evidencia").innerText = t.inv_evidencia;
    document.getElementById("inv_observaciones").innerText = t.inv_observaciones;
    document.getElementById("submitBtn").innerText = t.submitBtn;
    // Traducir opciones del select de defectos
    const defectoSelect = document.getElementById("defecto");
    Array.from(defectoSelect.options).forEach((opt, idx) => {
      if (t.defect_options[idx]) opt.text = t.defect_options[idx];
    });
  }

  document.getElementById("translateBtn").addEventListener("click", function() {
    currentLang = currentLang === "en" ? "es" : "en";
    translateForm();
  });

  // Validación y preview de imagen
  const form = document.getElementById('form-especial');
  const fileInput = document.getElementById('evidencia_fotografica');
  const preview = document.getElementById('preview-img');

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!form.checkValidity()) {
      e.stopPropagation();
      form.classList.add('was-validated');
      return;
    }
    const formData = new FormData(form);
    fetch('../includes/guardar_hallazgo_usa.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Registro guardado correctamente');
        form.reset();
        preview.src = '#';
        preview.style.display = 'none';
        // Opcional: vuelve a poner la fecha de hoy
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        fechaInput.value = `${yyyy}-${mm}-${dd}`;
      } else {
        alert('Error: ' + (data.error || 'No se pudo guardar'));
      }
    })
    .catch(err => {
      alert('Error de conexión');
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
});
  </script>
</body>
</html>