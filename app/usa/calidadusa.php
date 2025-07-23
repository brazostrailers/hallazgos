<?php
session_start();
if (
    !isset($_SESSION['usuario']) ||
    ($_SESSION['usuario']['rol'] !== 'usa' && $_SESSION['usuario']['rol'] !== 'encargadousa')
) {
    header('Location: ../login.php');
    exit;
}
require_once '../includes/db.php';

// Cards: conteos rápidos
$total_registros = $mysqli->query("SELECT COUNT(*) as total FROM hallazgos_usa")->fetch_assoc()['total'];

// Defectos para cards y gráficas
$defectos = [];
$defecto_labels = [];
$defecto_counts = [];
$res = $mysqli->query("SELECT defecto, COUNT(*) as total FROM hallazgos_usa GROUP BY defecto");
while ($row = $res->fetch_assoc()) {
    $defectos[$row['defecto']] = $row['total'];
    $defecto_labels[] = $row['defecto'];
    $defecto_counts[] = $row['total'];
}

// Warehouses para gráfica
$warehouse_labels = [];
$warehouse_counts = [];
$res = $mysqli->query("SELECT warehouse, COUNT(*) as total FROM hallazgos_usa GROUP BY warehouse ORDER BY total DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
    $warehouse_labels[] = $row['warehouse'];
    $warehouse_counts[] = $row['total'];
}

// Registros para la tabla
$registros = $mysqli->query("SELECT * FROM hallazgos_usa ORDER BY created_at DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quality USA Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background: #f8fafc; }
        .dashboard-title { font-weight: 700; letter-spacing: 1px; }
        .card-summary {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s;
        }
        .card-summary:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08);}
        .card-summary .icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 0.5rem;
        }
        .table thead th {
            background: #f1f3f6;
            font-weight: 600;
        }
        .table tbody tr:hover {
            background: #f6faff;
        }
        .chart-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 1.5rem;
        }
        @media (max-width: 767px) {
            .dashboard-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="dashboard-title mb-4"><i class="bi bi-patch-check"></i> USA Quality Dashboard</h2>
    <!-- Cards resumen -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card card-summary text-center">
                <div class="card-body">
                    <div class="icon"><i class="bi bi-clipboard-data"></i></div>
                    <div class="fs-2 fw-bold"><?= $total_registros ?></div>
                    <div class="text-muted">Total Findings</div>
                </div>
            </div>
        </div>
        <?php foreach ($defectos as $defecto => $count): ?>
        <div class="col-6 col-md-2">
            <div class="card card-summary text-center">
                <div class="card-body">
                    <div class="icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="fs-4 fw-bold"><?= $count ?></div>
                    <div class="text-muted" style="font-size:0.95em"><?= htmlspecialchars($defecto) ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Gráficas -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card chart-card">
                <div class="card-body">
                    <h6 class="card-title mb-3"><i class="bi bi-bar-chart"></i> Findings by Defect</h6>
                    <canvas id="barDefectos" height="180"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card chart-card">
                <div class="card-body">
                    <h6 class="card-title mb-3"><i class="bi bi-pie-chart"></i> Defect Distribution</h6>
                    <canvas id="pieDefectos" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card chart-card">
                <div class="card-body">
                    <h6 class="card-title mb-3"><i class="bi bi-building"></i> Warehouses with Most Findings</h6>
                    <canvas id="barWarehouses" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            <i class="bi bi-list-check"></i> Findings Records
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Job Order</th>
                            <th>Warehouse</th>
                            <th>Part No.</th>
                            <th>Defect</th>
                            <th>Evidence</th>
                            <th>Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['fecha']) ?></td>
                            <td><?= htmlspecialchars($row['job_order']) ?></td>
                            <td><?= htmlspecialchars($row['warehouse']) ?></td>
                            <td><?= htmlspecialchars($row['noparte']) ?></td>
                            <td><?= htmlspecialchars($row['defecto']) ?></td>
                            <td>
                                <?php if ($row['evidencia_fotografica']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($row['evidencia_fotografica']) ?>"
                                         style="width:40px;max-height:40px;border-radius:6px;cursor:pointer;"
                                         onclick="verEvidenciaModal('<?= htmlspecialchars($row['evidencia_fotografica']) ?>')">
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['observaciones']) ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalEvidencia" tabindex="-1" aria-labelledby="modalEvidenciaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <img id="modalEvidenciaImg" src="" alt="Evidencia" class="img-fluid rounded">
      </div>
    </div>
  </div>
</div>
<script>
    function verEvidenciaModal(imagen) {
    document.getElementById('modalEvidenciaImg').src = '../uploads/' + imagen;
    var modal = new bootstrap.Modal(document.getElementById('modalEvidencia'));
    modal.show();
}
const defectLabels = <?= json_encode($defecto_labels) ?>;
const defectCounts = <?= json_encode($defecto_counts) ?>;
new Chart(document.getElementById('barDefectos'), {
    type: 'bar',
    data: {
        labels: defectLabels,
        datasets: [{
            label: 'Findings',
            data: defectCounts,
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
new Chart(document.getElementById('pieDefectos'), {
    type: 'pie',
    data: {
        labels: defectLabels,
        datasets: [{
            data: defectCounts,
            backgroundColor: ['#0d6efd','#dc3545','#ffc107','#198754','#6f42c1','#fd7e14','#20c997']
        }]
    }
});
const warehouseLabels = <?= json_encode($warehouse_labels) ?>;
const warehouseCounts = <?= json_encode($warehouse_counts) ?>;
new Chart(document.getElementById('barWarehouses'), {
    type: 'bar',
    data: {
        labels: warehouseLabels,
        datasets: [{
            label: 'Findings',
            data: warehouseCounts,
            backgroundColor: '#198754'
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});
</script>
</body>
</html>