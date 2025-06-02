<?php
require_once '../includes/db.php';
require_once '../includes/hallazgos.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Gerencial de Hallazgos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/assets/css/hallazgos.css">
    <style>
        .card-resumen { min-height: 120px; }
        .card-title { font-size: 1rem; }
        .chart-container { min-height: 350px; }
        .chart-container {
    min-height: 350px;
    max-height: 400px;
    height: 400px;
    overflow-y: auto;
}
.chart-container canvas {
    max-height: 380px !important;
}
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4 text-center">Dashboard Gerencial de Hallazgos</h2>
    <div class="row g-3 mb-4">
        
        <div class="col-6 col-md-2">
            <div class="card card-resumen shadow-sm">
                <div class="card-body text-center">
                    <span class="icono-resumen"><i class="bi bi-clipboard-data"></i></span>
                    <div class="fw-bold fs-4"><?= $resumen['total'] ?></div>
                    <div class="text-muted">Total de registros</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-resumen shadow-sm">
                <div class="card-body text-center">
                    <span class="icono-resumen text-danger"><i class="bi bi-x-circle"></i></span>
                    <div class="fw-bold fs-4"><?= $resumen['rechazados'] ?></div>
                    <div class="text-muted">Rechazados (con hallazgo)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-resumen shadow-sm">
                <div class="card-body text-center">
                    <span class="icono-resumen text-warning"><i class="bi bi-arrow-repeat"></i></span>
                    <div class="fw-bold fs-4"><?= $resumen['retrabajos'] ?></div>
                    <div class="text-muted">Retrabajo</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card card-resumen shadow-sm">
                <div class="card-body text-center">
                    <span class="icono-resumen text-info"><i class="bi bi-exclamation-triangle"></i></span>
                    <div class="fw-bold fs-4"><?= $resumen['cuarentena'] ?></div>
                    <div class="text-muted">Cuarentena</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-resumen shadow-sm">
                <div class="card-body text-center">
                    <span class="icono-resumen text-success"><i class="bi bi-bar-chart-line"></i></span>
                    <div class="fw-bold fs-6">
                        FPY Día: <b><?= $fpy_dia ?>%</b><br>
                        FPY Semana: <b><?= $fpy_semana ?>%</b><br>
                        FPY Mes: <b><?= $fpy_mes ?>%</b>
                    </div>
                    <div class="text-muted">FPY (Yield)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3 mb-4">
    <!-- Card: Piezas en Scrap -->
    <div class="col-6 col-md-3">
        <div class="card card-resumen shadow-sm">
            <div class="card-body text-center">
                <span class="icono-resumen text-danger"><i class="bi bi-trash"></i></span>
                <div class="fw-bold fs-4"><?= $piezas_scrap ?></div>
                <div class="text-muted">Piezas en Scrap</div>
            </div>
        </div>
    </div>
    <!-- Card: Dinero de Scrap -->
    <div class="col-6 col-md-3">
        <div class="card card-resumen shadow-sm">
            <div class="card-body text-center">
                <span class="icono-resumen text-success"><i class="bi bi-cash-coin"></i></span>
                <div class="fw-bold fs-4">$<?= number_format($dinero_scrap, 2) ?></div>
                <div class="text-muted">Dinero de Scrap</div>
            </div>
        </div>
    </div>
</div>

    <!-- Gráficas de hallazgos -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body chart-container">
                    <h6 class="card-title">Hallazgos por Área</h6>
                    <canvas id="graficoArea"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body chart-container">
                    <h6 class="card-title">Hallazgos por Modelo</h6>
                    <canvas id="graficoModelo"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body chart-container">
                    <h6 class="card-title">Hallazgos por Estación</h6>
                    <canvas id="graficoEstacion"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body chart-container">
                    <h6 class="card-title">Hallazgos por Tipo de Defecto</h6>
                    <canvas id="graficoDefecto"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas de registros exitosos (100% exitosos) -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body chart-container">
                <h6 class="card-title">Estaciones 100% Exitosas (sin hallazgos)</h6>
                <canvas id="graficoEstacionesExitosas"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body chart-container">
                <h6 class="card-title">Modelos 100% Exitosos (sin hallazgos)</h6>
                <canvas id="graficoModelosExitosos"></canvas>
            </div>
        </div>
    </div>
</div>

 <div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Registros en Cuarentena</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Modelo</th>
                                <th>No. Serie</th>
                                <th>Área</th>
                                <th>Estación</th>
                                <th>Tipo Defecto</th>
                                <th>Horas en cuarentena</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                       <tbody>
<?php foreach ($en_cuarentena as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['modelo']) ?></td>
        <td><?= htmlspecialchars($row['no_serie']) ?></td>
        <td><?= htmlspecialchars($row['area_ubicacion']) ?></td>
        <td><?= htmlspecialchars($row['estacion']) ?></td>
        <td><?= htmlspecialchars($row['tipo_defecto']) ?></td>
        <td><?= intval($row['horas_en_cuarentena']) ?> h</td>
       <td>
    <button type="button" class="btn btn-danger btn-sm"
        onclick='abrirModalScrap(<?= json_encode($row['id']) ?>,<?= json_encode($row['modelo']) ?>,<?= json_encode($row['no_serie']) ?>)'>Scrap</button>
</td>
    </tr>
<?php endforeach ?>
</tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Scrap -->
    <div class="modal fade" id="modalScrap" tabindex="-1" aria-labelledby="modalScrapLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="formScrap" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalScrapLabel">Registrar Scrap</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="scrap_id_hallazgo" name="id_hallazgo">
            <div class="mb-3">
              <label class="form-label">Modelo</label>
              <input type="text" class="form-control" id="scrap_modelo" name="modelo" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">No. Serie</label>
              <input type="text" class="form-control" id="scrap_no_serie" name="no_serie" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">Número de pieza</label>
              <input type="text" class="form-control" id="scrap_numero_pieza" name="numero_pieza" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Precio</label>
              <input type="number" class="form-control" id="scrap_precio" name="precio" min="0" step="0.01" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-danger">Registrar Scrap</button>
          </div>
        </form>
      </div>
    </div>
    <div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Registros con Hallazgos</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Modelo</th>
                                <th>No. Serie</th>
                                <th>Área</th>
                                <th>Estación</th>
                                <th>Tipo Defecto</th>
                                <th>Foto</th>
                                <th>Cuarentena</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($hallazgos as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['modelo']) ?></td>
                                <td><?= htmlspecialchars($row['no_serie']) ?></td>
                                <td><?= htmlspecialchars($row['area_ubicacion']) ?></td>
                                <td><?= htmlspecialchars($row['estacion']) ?></td>
                                <td><?= htmlspecialchars($row['tipo_defecto']) ?></td>
<td>
<?php if (!empty($row['evidencia'])): ?>
    <button type="button" class="btn btn-outline-secondary btn-sm"
        onclick="verFoto('http://192.168.50.95:8085/uploads/<?= rawurlencode($row['evidencia']) ?>')">
        <i class="bi bi-image"></i> Ver
    </button>
<?php else: ?>
    <span class="text-muted">Sin foto</span>
<?php endif ?>
</td>
                                <td>
                                    <span class="badge bg-<?= $row['cuarentena'] == 'Sí' ? 'warning text-dark' : 'secondary' ?>">
                                        <?= $row['cuarentena'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['cuarentena'] === 'No'): ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="cambiarCuarentena(<?= $row['id'] ?>, '<?= $row['cuarentena'] ?>')">
                                            Cambiar Cuarentena
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Registros Correctos (Sin Hallazgos)</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Modelo</th>
                                <th>No. Serie</th>
            
                                <th>Estación</th>
                                <th>Fecha de Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($registros_correctos as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['modelo']) ?></td>
                                <td><?= htmlspecialchars($row['no_serie']) ?></td>

                                <td><?= htmlspecialchars($row['estacion']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['creado_en'])) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- Modal para ver foto -->
<div class="modal fade" id="modalFoto" tabindex="-1" aria-labelledby="modalFotoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFotoLabel">Foto del Hallazgo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <img id="fotoHallazgo" src="" alt="Foto" class="img-fluid rounded">
      </div>
    </div>
  </div>
</div>
<script>
function verFoto(url) {
    document.getElementById('fotoHallazgo').src = url;
    var modal = new bootstrap.Modal(document.getElementById('modalFoto'));
    modal.show();
}
</script>

<script>
var por_area = <?= json_encode($por_area_hallazgo) ?>;
var por_modelo = <?= json_encode($por_modelo_hallazgo) ?>;
var por_estacion = <?= json_encode($por_estacion_hallazgo) ?>;
var por_tipo_defecto = <?= json_encode($por_tipo_defecto) ?>;
var estaciones_exitosas = <?= json_encode($estaciones_exitosas) ?>;
var modelos_exitosos = <?= json_encode($modelos_exitosos) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Limitar a 10 para evitar scroll infinito en horizontales


    // Hallazgos por área (vertical)
    new Chart(document.getElementById('graficoArea').getContext('2d'), {
        type: 'bar',
        data: {
            labels: por_area.labels,
            datasets: [{
                label: 'Hallazgos',
                data: por_area.values,
                backgroundColor: '#dc3545'
            }]
        },
        options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });

    // Hallazgos por modelo (vertical)
    new Chart(document.getElementById('graficoModelo').getContext('2d'), {
        type: 'bar',
        data: {
            labels: por_modelo.labels,
            datasets: [{
                label: 'Hallazgos',
                data: por_modelo.values,
                backgroundColor: '#0d6efd'
            }]
        },
        options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });

    // Hallazgos por estación (vertical)
    new Chart(document.getElementById('graficoEstacion').getContext('2d'), {
        type: 'bar',
        data: {
            labels: por_estacion.labels,
            datasets: [{
                label: 'Hallazgos',
                data: por_estacion.values,
                backgroundColor: '#ffc107'
            }]
        },
        options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });

    // Hallazgos por tipo de defecto (vertical)
    new Chart(document.getElementById('graficoDefecto').getContext('2d'), {
        type: 'bar',
        data: {
            labels: por_tipo_defecto.labels,
            datasets: [{
                label: 'Hallazgos',
                data: por_tipo_defecto.values,
                backgroundColor: '#20c997'
            }]
        },
        options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });

   new Chart(document.getElementById('graficoEstacionesExitosas').getContext('2d'), {
        type: 'bar',
        data: {
            labels: estaciones_exitosas.labels,
            datasets: [{
                label: 'Registros exitosos',
                data: estaciones_exitosas.values,
                backgroundColor: '#198754'
            }]
        },
        options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });

    // Modelos 100% exitosos (vertical)
    new Chart(document.getElementById('graficoModelosExitosos').getContext('2d'), {
        type: 'bar',
        data: {
            labels: modelos_exitosos.labels,
            datasets: [{
                label: 'Registros exitosos',
                data: modelos_exitosos.values,
                backgroundColor: '#0d6efd'
            }]
        },
        options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
    });
});
// Modal Scrap
function abrirModalScrap(id, modelo, no_serie) {
    console.log('abrirModalScrap', id, modelo, no_serie);
    document.getElementById('scrap_id_hallazgo').value = id;
    document.getElementById('scrap_modelo').value = modelo;
    document.getElementById('scrap_no_serie').value = no_serie;
    document.getElementById('scrap_numero_pieza').value = '';
    document.getElementById('scrap_precio').value = '';
    var modal = new bootstrap.Modal(document.getElementById('modalScrap'));
    modal.show();
}
document.getElementById('formScrap').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = e.target;
    var datos = new FormData(form);
    fetch('../includes/registrar_scrap.php', {
        method: 'POST',
        body: datos
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('Scrap registrado correctamente');
            location.reload();
        } else {
            alert(data.msg || 'Error al registrar scrap');
        }
    })
    .catch(() => alert('Error de conexión'));
});


function cambiarCuarentena(id, actual) {
    let nuevo = actual === 'Sí' ? 'No' : 'Sí';
    if (!confirm('¿Seguro que deseas cambiar el estado de cuarentena a "' + nuevo + '"?')) return;
    fetch('../includes/cambiar_cuarentena.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id) + '&cuarentena=' + encodeURIComponent(nuevo)
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            alert('Estado de cuarentena actualizado');
            location.reload();
        } else {
            alert(data.msg || 'Error al actualizar');
        }
    })
    .catch(() => alert('Error de conexión'));
}
</script>
</body>
</html>