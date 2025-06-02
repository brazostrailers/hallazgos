<?php

require_once '../includes/db.php';

// Consulta todos los hallazgos con los campos solicitados
$hallazgos = [];
$sql = "
    SELECT 
        h.fecha, 
        h.area_ubicacion as area, 
        h.no_parte, 
        h.no_serie, 
        h.tipo_defecto, 
        h.estacion, 
        h.evidencia, 
        h.cantidad_producida, 
        h.cantidad_defectos, 
        h.fpy
    FROM hallazgos h
    ORDER BY h.fecha DESC
";
$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    $hallazgos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Histórico de Hallazgos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/historico.css" rel="stylesheet">
  <!-- SheetJS para Excel -->
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
  <!-- jsPDF y autotable para PDF -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
  <div class="container py-4">
    <h2 class="mb-4 text-center">Histórico de Hallazgos</h2>
    <div class="d-flex justify-content-start mb-3">
      <a href="../encargado/ver_hallazgos.php" class="btn btn-outline-secondary btn-lg">
        <i class="bi bi-arrow-left me-2"></i> Regresar al Dashboard
      </a>
    </div>
    <div class="mb-3 d-flex gap-2">
      <button class="btn btn-success" onclick="exportarExcel()">
        <i class="bi bi-file-earmark-excel"></i> Exportar Excel
      </button>
      <button class="btn btn-danger" onclick="exportarPDF()">
        <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
      </button>
    </div>
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <form id="filtros" class="row g-3">
          <div class="col-md-3">
            <label for="filtroFecha" class="form-label">Fecha</label>
            <input type="date" class="form-control" id="filtroFecha" name="fecha">
          </div>
          <div class="col-md-3">
            <label for="filtroArea" class="form-label">Área</label>
            <input type="text" class="form-control" id="filtroArea" name="area" placeholder="Área">
          </div>
          <div class="col-md-3">
            <label for="filtroDefecto" class="form-label">Tipo de Defecto</label>
            <input type="text" class="form-control" id="filtroDefecto" name="tipo_defecto" placeholder="Tipo de defecto">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-primary w-100" onclick="filtrarTabla()">Buscar</button>
          </div>
        </form>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle" id="tablaHallazgos">
        <thead class="table-dark">
          <tr>
            <th>Fecha</th>
            <th>Área</th>
            <th>No. de Parte</th>
            <th>No. de Serie</th>
            <th>Tipo de Defecto</th>
            <th>Estación</th>
            <th>Evidencia</th>
            <th>Cant. Producida</th>
            <th>Cant. Defectos</th>
            <th>FPY (%)</th>
          </tr>
        </thead>
        <tbody id="bodyHallazgos">
          <!-- Se llena por JS -->
        </tbody>
      </table>
    </div>
    <nav>
      <ul class="pagination justify-content-center" id="paginacion"></ul>
    </nav>
  </div>
  <div class="modal fade" id="modalEvidencia" tabindex="-1" aria-labelledby="modalEvidenciaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEvidenciaLabel">Evidencia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <img id="imgEvidenciaModal" src="" alt="Evidencia" style="max-width:100%; max-height:60vh; border-radius:10px;">
      </div>
    </div>
  </div>
</div>
  <script>
    // Datos reales desde PHP
    const hallazgos = <?= json_encode($hallazgos) ?>;

    const porPagina = 10;
    let paginaActual = 1;
    let hallazgosFiltrados = hallazgos;

    function renderTabla() {
      const tbody = document.getElementById('bodyHallazgos');
      tbody.innerHTML = '';
      const inicio = (paginaActual - 1) * porPagina;
      const fin = inicio + porPagina;
      const datos = hallazgosFiltrados.slice(inicio, fin);

      if (datos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted">No se encontraron hallazgos</td></tr>`;
      } else {
        datos.forEach(h => {
          tbody.innerHTML += `
            <tr>
              <td>${h.fecha}</td>
              <td>${resaltar(h.area)}</td>
              <td>${h.no_parte || ''}</td>
              <td>${h.no_serie || ''}</td>
              <td>${h.tipo_defecto || ''}</td>
              <td>${h.estacion || ''}</td>
             <td>
  ${h.evidencia 
    ? `<button class="btn btn-sm btn-outline-secondary" onclick="verEvidenciaModal('/uploads/${h.evidencia}')">Ver</button>`
    : '<span class="text-muted">Sin foto</span>'}
</td>
              <td>${h.cantidad_producida || ''}</td>
              <td>${h.cantidad_defectos || ''}</td>
              <td>${h.fpy !== null && h.fpy !== undefined ? h.fpy : ''}</td>
            </tr>
          `;
        });
      }
      renderPaginacion();
    }

    function renderPaginacion() {
      const totalPaginas = Math.ceil(hallazgosFiltrados.length / porPagina);
      const pag = document.getElementById('paginacion');
      pag.innerHTML = '';
      if (totalPaginas <= 1) return;
      for (let i = 1; i <= totalPaginas; i++) {
        pag.innerHTML += `<li class="page-item${i === paginaActual ? ' active' : ''}">
          <button class="page-link" onclick="irPagina(${i})">${i}</button>
        </li>`;
      }
    }

    function irPagina(num) {
      paginaActual = num;
      renderTabla();
    }

    function filtrarTabla() {
      const fecha = document.getElementById('filtroFecha').value;
      const area = document.getElementById('filtroArea').value.toLowerCase();
      const defecto = document.getElementById('filtroDefecto').value.toLowerCase();

      hallazgosFiltrados = hallazgos.filter(h =>
        (!fecha || h.fecha === fecha) &&
        (!area || (h.area && h.area.toLowerCase().includes(area))) &&
        (!defecto || (h.tipo_defecto && h.tipo_defecto.toLowerCase().includes(defecto)))
      );
      paginaActual = 1;
      renderTabla();
    }

    function resaltar(texto) {
      const area = document.getElementById('filtroArea').value;
      let t = texto || '';
      if (area && t.toLowerCase().includes(area.toLowerCase())) {
        t = t.replace(new RegExp(area, 'gi'), match => `<mark>${match}</mark>`);
      }
      return t;
    }

    // Inicializa tabla
    renderTabla();

    // Exportar a Excel
    function exportarExcel() {
      const datos = hallazgosFiltrados.map(h => ({
        Fecha: h.fecha,
        Área: h.area,
        'No. de Parte': h.no_parte,
        'No. de Serie': h.no_serie,
        'Tipo de Defecto': h.tipo_defecto,
        Estación: h.estacion,
        Evidencia: h.evidencia ? 'Sí' : 'No',
        'Cant. Producida': h.cantidad_producida,
        'Cant. Defectos': h.cantidad_defectos,
        'FPY (%)': h.fpy
      }));
      const ws = XLSX.utils.json_to_sheet(datos);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "Hallazgos");
      XLSX.writeFile(wb, "historico_hallazgos.xlsx");
    }

    // Exportar a PDF
    function exportarPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      const columnas = ["Fecha", "Área", "No. de Parte", "No. de Serie", "Tipo de Defecto", "Estación", "Evidencia", "Cant. Producida", "Cant. Defectos", "FPY (%)"];
      const filas = hallazgosFiltrados.map(h => [
        h.fecha,
        h.area,
        h.no_parte,
        h.no_serie,
        h.tipo_defecto,
        h.estacion,
        h.evidencia ? 'Sí' : 'No',
        h.cantidad_producida,
        h.cantidad_defectos,
        h.fpy
      ]);
      doc.text("Histórico de Hallazgos", 14, 14);
      doc.autoTable({
        head: [columnas],
        body: filas,
        startY: 20,
        styles: { fontSize: 9 }
      });
      doc.save("historico_hallazgos.pdf");
    }
  
function verEvidenciaModal(src) {
  document.getElementById('imgEvidenciaModal').src = src;
  var modal = new bootstrap.Modal(document.getElementById('modalEvidencia'));
  modal.show();
}

  </script>
</body>
</html>