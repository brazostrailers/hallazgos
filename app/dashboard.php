<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Hallazgo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
  <div class="container py-4">
    <div class="card shadow mx-auto" style="max-width: 480px;">
      <div class="card-body">
        <div class="text-center mb-3">
          <img src="assets/img/logo.jpg" alt="Logo Empresa" class="mb-2" style="width: 80px; height: 80px; object-fit: contain; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        </div>
        <div class="text-center mb-3">
          <button type="button" id="traducirBtn" class="btn btn-outline-secondary btn-sm">Traducir al Inglés</button>
        </div>
        <h3 class="card-title text-center mb-3">Nuevo Hallazgo</h3>
        <form id="hallazgoForm" action="guardar_hallazgo.php" method="POST" autocomplete="off" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="fecha" name="fecha" required>
          </div>
          <div class="mb-3">
            <label for="modelo" class="form-label">Modelo</label>
            <select class="form-select" id="modelo" name="modelo" required>
              <option value="">Selecciona...</option>
              <option value='ED,32 X 48 "SW ROLLED BODY ASSY'>ED,32 X 48 "SW ROLLED BODY ASSY</option>
              <option value="62.5T 9'W LB 30' WELL BED">62.5T 9'W LB 30' WELL BED</option>
              <option value="BD, DS WALL ASSY">BD, DS WALL ASSY</option>
              <option value="BD, PS WALL ASSY">BD, PS WALL ASSY</option>
              <option value="55T LB BED">55T LB BED</option>
              <option value='ED,32 X 48 "SW ROLLED BODY ASSY'>ED,32 X 48 "SW ROLLED BODY ASSY</option>
              <option value="BD GATE ASSY">BD GATE ASSY</option>
              <option value="BD, HOPPER ASSY">BD, HOPPER ASSY</option>
              <option value='9\'W 62.5T LB 9\'1&quot; SUSPENSION FRAME'>9'W 62.5T LB 9'1" SUSPENSION FRAME</option>
              <option value="BD, SPRING SUSPENSION AND ATTACHMENT ASSY">BD, SPRING SUSPENSION AND ATTACHMENT ASSY</option>
              <option value='ED 48 " SW TAILGATE'>ED 48 " SW TAILGATE</option>
              <option value='32&quot; ED DRAFT ARM ASSY'>32" ED DRAFT ARM ASSY</option>
              <option value="BD, HOPPER ASSY">BD, HOPPER ASSY</option>
              <option value="BD 5TH WHEEL PLATE">BD 5TH WHEEL PLATE</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="no_parte" class="form-label">No. de Parte</label>
            <input type="text" class="form-control" id="no_parte" name="no_parte" required placeholder="Número de parte">
          </div>
          <div class="mb-3">
            <label for="no_serie" class="form-label">No. de Serie</label>
            <input type="text" class="form-control" id="no_serie" name="no_serie" required placeholder="Número de serie">
          </div>
          <div class="mb-3">
            <label for="estacion" class="form-label">Estación</label>
            <select class="form-select" id="estacion" name="estacion" required>
              <option value="">Selecciona...</option>
              <option value="Estación 1">Estación 1</option>
              <option value="Estación 2">Estación 2</option>
              <option value="Estación 3">Estación 3</option>
              <option value="Estación 4">Estación 4</option>
              <option value="Estación 5">Estación 5</option>
              <option value="Estación 6">Estación 6</option>
              <option value="Estación 7">Estación 7</option>
              <option value="Estación 8">Estación 8</option>
              <option value="Estación 9">Estación 9</option>
              <option value="Estación 10">Estación 10</option>
              <option value="Estación 11">Estación 11</option>
              <option value="Estación 12">Estación 12</option>
              <option value="Estación 13">Estación 13</option>
              <option value="Estación 14">Estación 14</option>
            </select>
          </div>
          <div class="mb-3">
  <label for="existe_defecto" class="form-label">¿Existe un defecto?</label>
  <select class="form-select" id="existe_defecto" name="existe_defecto" required>
    <option value="">Selecciona...</option>
    <option value="Sí">Sí</option>
    <option value="No">No</option>
  </select>
</div>
<!-- Campos que se muestran solo si existe_defecto = Sí -->
<div id="campos_defecto" style="display:none;">
  <div class="mb-3" id="campo_area_ubicacion">
    <label for="area_ubicacion" class="form-label">Área de Ubicación</label>
    <input type="text" class="form-control" id="area_ubicacion" name="area_ubicacion" placeholder="Área donde se encontró">
  </div>
  <div class="mb-3">
    <label for="retrabajo" class="form-label">¿Retrabajo?</label>
    <select class="form-select" id="retrabajo" name="retrabajo">
      <option value="">Selecciona...</option>
      <option value="Sí">Sí</option>
      <option value="No">No</option>
    </select>
  </div>
  <!-- Solo si retrabajo = Sí -->
  <div class="mb-3" id="campo_cuarentena" style="display:none;">
    <label for="cuarentena" class="form-label">¿Necesita cuarentena?</label>
    <select class="form-select" id="cuarentena" name="cuarentena">
      <option value="">Selecciona...</option>
      <option value="Sí">Sí</option>
      <option value="No">No</option>
    </select>
  </div>
  <div class="mb-3">
    <label for="tipo_defecto" class="form-label">Tipo de Defecto</label>
    <select class="form-select" id="tipo_defecto" name="tipo_defecto">
      <option value="">Selecciona...</option>
      <option value="grietas">Grietas</option>
      <option value="porosidad">Porosidad</option>
      <option value="crater">Crater</option>
      <option value="puntas sobrantes de soldadura">Puntas sobrantes de soldadura</option>
      <option value="Chisporroteo">Chisporroteo</option>
    </select>
  </div>
  <div class="mb-3">
    <label for="observaciones" class="form-label">Observaciones</label>
    <textarea class="form-control" id="observaciones" name="observaciones" rows="2" placeholder="Observaciones adicionales"></textarea>
  </div>
  <div class="mb-3">
    <label for="evidencia" class="form-label">Evidencia fotográfica</label>
    <input class="form-control" type="file" id="evidencia" name="evidencia" accept="image/*" capture="environment">
    <div id="preview" class="mt-2"></div>
  </div>
</div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Registrar Hallazgo</button>
        </form>
      </div>
    </div>
  </div>
<script>
document.getElementById('existe_defecto').addEventListener('change', function() {
  const camposDefecto = document.getElementById('campos_defecto');
  const areaUbicacion = document.getElementById('area_ubicacion');
  if (this.value === 'Sí') {
    camposDefecto.style.display = '';
    areaUbicacion.required = true;
  } else {
    camposDefecto.style.display = 'none';
    areaUbicacion.value = '';
    areaUbicacion.required = false;
    document.getElementById('retrabajo').value = '';
    document.getElementById('tipo_defecto').value = '';
    document.getElementById('observaciones').value = '';
    document.getElementById('evidencia').value = '';
    document.getElementById('cuarentena').value = '';
    document.getElementById('campo_cuarentena').style.display = 'none';
  }
});

  document.getElementById('retrabajo').addEventListener('change', function() {
    const campoCuarentena = document.getElementById('campo_cuarentena');
    if (this.value === 'Sí') {
      campoCuarentena.style.display = '';
    } else {
      campoCuarentena.style.display = 'none';
      document.getElementById('cuarentena').value = '';
    }
  });

  // Previsualización de imagen
  document.getElementById('evidencia').addEventListener('change', function(e) {
    const preview = document.getElementById('preview');
    preview.innerHTML = '';
    if (this.files && this.files[0]) {
      const img = document.createElement('img');
      img.src = URL.createObjectURL(this.files[0]);
      img.className = 'img-fluid rounded shadow-sm mt-2';
      img.style.maxHeight = '180px';
      preview.appendChild(img);
    }
  });

  // Autocompletar fecha de hoy
  document.getElementById('fecha').valueAsDate = new Date();

  // Traducción dinámica
  const traduccionesES = {
    'Fecha': 'Date',
    'Modelo': 'Model',
    'No. de Parte': 'Part Number',
    'No. de Serie': 'Serial Number',
    'Estación': 'Station',
    'Tipo de Defecto': 'Defect Type',
    'Área de Ubicación': 'Location Area',
    '¿Existe un defecto?': 'Is there a defect?',
    '¿Retrabajo?': 'Rework?',
    '¿Necesita cuarentena?': 'Needs quarantine?',
    'Observaciones': 'Observations',
    'Evidencia fotográfica': 'Photographic Evidence',
    'Registrar Hallazgo': 'Register Finding',
    'Selecciona...': 'Select...',
    'Sí': 'Yes',
    'No': 'No',
    'Nuevo Hallazgo': 'New Finding',
    'Grietas': 'Cracks',
    'Porosidad': 'Porosity',
    'Crater': 'Crater',
    'Puntas sobrantes de soldadura': 'Welding spatter',
    'Chisporroteo': 'Sparks'
  };
  const traduccionesEN = Object.fromEntries(
    Object.entries(traduccionesES).map(([es, en]) => [en, es])
  );
  let idiomaActual = 'es';

  document.getElementById('traducirBtn').addEventListener('click', () => {
    const diccionario = idiomaActual === 'es' ? traduccionesES : traduccionesEN;

    // Traduce los labels
    document.querySelectorAll('label').forEach(label => {
      if (diccionario[label.innerText]) {
        label.innerText = diccionario[label.innerText];
      }
    });

    // Traduce opciones de selects
    document.querySelectorAll('select').forEach(select => {
      Array.from(select.options).forEach(option => {
        if (diccionario[option.text]) {
          option.text = diccionario[option.text];
        }
      });
    });

    // Traduce el título
    const titulo = document.querySelector('.card-title');
    if (titulo && diccionario[titulo.innerText]) {
      titulo.innerText = diccionario[titulo.innerText];
    }

    // Traduce botón de submit
    const boton = document.querySelector('button[type="submit"]');
    if (boton && diccionario[boton.innerText]) {
      boton.innerText = diccionario[boton.innerText];
    }

    // Cambia idioma actual
    idiomaActual = idiomaActual === 'es' ? 'en' : 'es';

    // Actualiza texto del botón toggle
    const btn = document.getElementById('traducirBtn');
    btn.innerText = idiomaActual === 'es' ? 'Traducir al Inglés' : 'Translate to Spanish';
  });
</script>
</body>
</html>