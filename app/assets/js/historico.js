(() => {
  const $ = (id) => document.getElementById(id);
  const parseDate = (s) => {
    if (!s) return null;
    // Try ISO, then MySQL-like 'YYYY-MM-DD HH:MM:SS'
    let d = new Date(s);
    if (!Number.isNaN(d.getTime())) return d;
    const m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
    if (m) {
      const [_, y, mo, da, h, mi, se] = m;
      d = new Date(Number(y), Number(mo)-1, Number(da), Number(h), Number(mi), Number(se||'0'));
      if (!Number.isNaN(d.getTime())) return d;
    }
    return null;
  };
  const fmtDate = (s) => {
    const d = parseDate(s);
    return d ? d.toLocaleString('es-ES') : (s || 'N/A');
  };

  const buildParams = () => {
    const p = new URLSearchParams();
    const estado = $('fEstado').value;
    const fi = $('fFechaInicio').value;
    const ff = $('fFechaFin').value;
    const area = $('fArea').value;
    const modelo = $('fModelo').value;
    const usuario = $('fUsuario').value;
    const retrabajo = $('fRetrabajo').value;

    if (estado) p.append('estado', estado);
    if (fi) p.append('fechaInicio', fi);
    if (ff) p.append('fechaFin', ff);
    if (area) p.append('area', area);
    if (modelo) p.append('modelo', modelo);
    if (usuario) p.append('usuario', usuario);
    if (retrabajo) p.append('retrabajo', retrabajo);
    return p;
  };

  const render = (rows) => {
    const tbody = $('historicoBody');
    const resumen = $('historicoResumen');
    if (!rows || rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4">Sin resultados</td></tr>';
      resumen.textContent = '';
      return;
    }
      const html = rows.map(r => {
        const estadoNorm = (r.estado || '').toString().trim().toLowerCase();
        const btnSol = estadoNorm === 'cerrada' ? `<button class="btn btn-sm btn-outline-primary" data-id="${r.id}" data-solucion-btn><i class="fas fa-eye"></i> Solución</button>` : '';
      return `
      <tr>
        <td>#${r.id}</td>
        <td>${fmtDate(r.fecha_creacion || r.fecha)}</td>
          <td><span class="badge ${estadoNorm==='cerrada' ? 'bg-dark' : 'bg-secondary'}">${r.estado || 'N/A'}</span></td>
        <td>${r.area_ubicacion || 'N/A'}</td>
        <td>${r.modelo || 'N/A'}</td>
        <td>${r.no_parte || 'N/A'}</td>
        <td>${r.job_order || 'N/A'}</td>
        <td>${r.usuario_nombre || 'N/A'}</td>
        <td><span class="badge ${r.retrabajo === 'Si' ? 'bg-warning text-dark' : 'bg-success'}">${r.retrabajo || 'No'}</span></td>
        <td>${r.cantidad_piezas ?? 0}</td>
        <td>${r.total_defectos ?? 0}</td>
        <td>${r.total_evidencias ?? 0}</td>
        <td>${btnSol}</td>
      </tr>`;
    }).join('');
    tbody.innerHTML = html;
    // Attach listeners
    tbody.querySelectorAll('[data-solucion-btn]').forEach(btn => {
      btn.addEventListener('click', () => mostrarSolucion(btn.getAttribute('data-id')));
    });
    resumen.textContent = `Total: ${rows.length} registro(s)`;
  };

  const fetchData = async () => {
    const url = 'includes/hallazgos_data.php?' + buildParams().toString();
    const res = await fetch(url);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Error al consultar');
    render(data.data || []);
    return data.data || [];
  };

  const exportExcel = (rows) => {
    if (!rows || rows.length === 0) { alert('No hay datos para exportar'); return; }
  const headers = ['ID','Fecha','Estado','Área','Modelo','No. Parte','Job Order','Usuario','Retrabajo','Piezas','Defectos','Evidencias','Fecha Cierre','Solución'];
    const data = [headers];
    rows.forEach(r => {
      const fecha = parseDate(r.fecha_creacion || r.fecha || '') || '';
      data.push([
        r.id,
        fecha,
        r.estado || '',
        r.area_ubicacion || '',
        r.modelo || '',
        r.no_parte || '',
        r.job_order || '',
        r.usuario_nombre || '',
        r.retrabajo || '',
        r.cantidad_piezas ?? 0,
        r.total_defectos ?? 0,
  r.total_evidencias ?? 0,
  r.fecha_cierre || '',
  (r.solucion ? r.solucion.replace(/\n/g,' ') : '')
      ]);
    });

    if (window.XLSX) {
      const ws = XLSX.utils.aoa_to_sheet(data, { cellDates: true });
      // Set date format for second column
      const range = XLSX.utils.decode_range(ws['!ref']);
      for (let R = 1; R <= range.e.r; R++) {
        const cell = ws[XLSX.utils.encode_cell({ r: R, c: 1 })];
        if (cell && cell.v instanceof Date) {
          cell.t = 'd';
          cell.z = 'dd/mm/yyyy hh:mm';
        }
      }
      // Basic header styling (requires Pro for full styles; we apply width/row height)
      ws['!rows'] = [{ hpt: 20 }];
      ws['!cols'] = [
        { wch: 6 }, { wch: 20 }, { wch: 12 }, { wch: 16 }, { wch: 16 }, { wch: 14 },
        { wch: 12 }, { wch: 18 }, { wch: 12 }, { wch: 8 }, { wch: 10 }, { wch: 10 }
      ];
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Historico');
      const dateTag = new Date().toISOString().slice(0,10);
      XLSX.writeFile(wb, `Historico_Hallazgos_${dateTag}.xlsx`);
      return;
    }

    // Fallback CSV
    const csvHeaders = headers.join(',');
    const csvRows = rows.map(r => [
      r.id,
      (r.fecha_creacion || r.fecha || ''),
      (r.estado || ''),
      (r.area_ubicacion || ''),
      (r.modelo || ''),
      (r.no_parte || ''),
      (r.job_order || ''),
      (r.usuario_nombre || ''),
      (r.retrabajo || ''),
      (r.cantidad_piezas ?? 0),
      (r.total_defectos ?? 0),
  (r.total_evidencias ?? 0),
  (r.fecha_cierre || ''),
  (r.solucion ? r.solucion.replace(/"/g,'"').replace(/\n/g,' ') : '')
    ].map(v => '"' + String(v).replace(/"/g,'""') + '"').join(',')).join('\n');
    const blob = new Blob(['\ufeff' + csvHeaders + '\n' + csvRows], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    const dateTag = new Date().toISOString().slice(0,10);
    a.download = `Historico_Hallazgos_${dateTag}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
  };

  const mostrarSolucion = async (id) => {
    const modal = new bootstrap.Modal(document.getElementById('solucionModal'));
    const body = document.getElementById('solucionModalBody');
    const spanId = document.getElementById('solucionHallazgoId');
    if (spanId) spanId.textContent = id;
    if (body) body.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando solución...</p></div>';
    modal.show();
    try {
      const res = await fetch('includes/hallazgos_data.php?hallazgo_id=' + id);
      const data = await res.json();
      if (data.success && data.data && data.data.length === 1) {
        const h = data.data[0];
        const solucion = h.solucion && h.solucion.trim() !== '' ? h.solucion.replace(/\n/g,'<br>') : '<em>No se registró solución</em>';
        const fechaCierre = h.fecha_cierre || 'N/A';
        body.innerHTML = `
          <div class="card border-0 bg-light mb-0">
            <div class="card-header bg-secondary text-white py-2"><strong>Detalle de Cierre</strong></div>
            <div class="card-body">
              <p class="mb-2"><strong>Fecha de cierre:</strong> ${fechaCierre}</p>
              <hr class="my-2"/>
              <p class="mb-1"><strong>Solución aplicada:</strong></p>
              <div class="p-3 bg-white border rounded" style="max-height:300px; overflow:auto;">${solucion}</div>
            </div>
          </div>`;
      } else {
        body.innerHTML = '<div class="alert alert-warning">No se encontró la solución.</div>';
      }
    } catch (e) {
      body.innerHTML = '<div class="alert alert-danger">Error cargando la solución</div>';
    }
  };

  let lastRows = [];
  $('btnBuscar').addEventListener('click', async () => {
    try {
      $('historicoBody').innerHTML = '<tr><td colspan="12" class="text-center py-4"><div class="spinner-border"></div></td></tr>';
      lastRows = await fetchData();
    } catch (e) {
      console.error(e);
      $('historicoBody').innerHTML = '<tr><td colspan="12" class="text-center text-danger py-4">Error al cargar</td></tr>';
    }
  });

  $('btnLimpiar').addEventListener('click', () => {
    ['fEstado','fFechaInicio','fFechaFin','fArea','fModelo','fUsuario','fRetrabajo'].forEach(id => { const el = $(id); if (el) el.value = ''; });
    $('historicoBody').innerHTML = '<tr><td colspan="12" class="text-center text-muted py-4">Use los filtros y presione Buscar</td></tr>';
    $('historicoResumen').textContent = '';
    lastRows = [];
  });

  $('btnExportar').addEventListener('click', async () => {
    if (lastRows.length === 0) {
      try { lastRows = await fetchData(); } catch (e) { return; }
    }
    exportExcel(lastRows);
  });
})();
