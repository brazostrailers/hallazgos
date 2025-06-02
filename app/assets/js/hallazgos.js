// Gráfica por Área
document.addEventListener('DOMContentLoaded', function() {

new Chart(document.getElementById('graficoArea').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: por_area.labels,
        datasets: [{
            data: por_area.values,
            backgroundColor: ['#007bff', '#28a745', '#ffc107', '#17a2b8', '#6c757d']
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

// Gráfica por Modelo
new Chart(document.getElementById('graficoModelo').getContext('2d'), {
    type: 'pie',
    data: {
        labels: por_modelo.labels,
        datasets: [{
            data: por_modelo.values,
            backgroundColor: ['#fd7e14', '#20c997', '#e83e8c', '#6f42c1']
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

// Gráfica por Tipo de Defecto
new Chart(document.getElementById('graficoDefecto').getContext('2d'), {
    type: 'bar',
    data: {
        labels: por_tipo_defecto.labels,
        datasets: [{
            label: 'Cantidad',
            data: por_tipo_defecto.values,
            backgroundColor: '#007bff'
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } }
    }
});

// Gráfica por Estación
new Chart(document.getElementById('graficoEstacion').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: por_estacion.labels,
        datasets: [{
            data: por_estacion.values,
            backgroundColor: ['#ffc107', '#007bff', '#28a745', '#6c757d', '#fd7e14', '#20c997', '#e83e8c', '#6f42c1']
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

// Gráfica de evolución semanal
new Chart(document.getElementById('graficoEvolucion').getContext('2d'), {
    type: 'line',
    data: {
        labels: evolucion_labels,
        datasets: [{
            label: 'Hallazgos',
            data: evolucion_data,
            fill: true,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.1)',
            tension: 0.3
        }]
    },
    options: {
        plugins: {
            legend: { display: false }
        }
    }
});
function verEvidenciaModal(src) {
    document.getElementById('imgEvidenciaModal').src = src;
    var modal = new bootstrap.Modal(document.getElementById('modalEvidencia'));
    modal.show();
}
});