/* =====================================================
   INVENTARIOS DE INCENTIVOS — frontend
   SAG Programas Honduras Sin Hambre
   ===================================================== */

(function () {
  'use strict';

  // ── Tabs ───────────────────────────────────────────
  document.querySelectorAll('.inv-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.inv-tab').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.inv-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
  });

  // ── Toggle cronograma (acordeón) ───────────────────
  window.toggleCronograma = function (id) {
    const row = document.querySelector(`.cron-row[data-id="${id}"]`);
    if (row) row.classList.toggle('open');
  };

  window.verCronograma = function (id) {
    const row = document.querySelector(`.cron-row[data-id="${id}"]`);
    if (row && !row.classList.contains('open')) row.classList.add('open');
    if (row) row.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  // ── Eliminar cronograma (con confirmación) ─────────
  window.eliminarCronograma = function (id, codigo) {
    const msg = `¿Eliminar el cronograma ${codigo}?\n\n` +
                `Si tiene recepciones registradas, se marcará como CANCELADO ` +
                `(para preservar el kardex). Si no tiene movimientos, se borrará por completo.`;
    if (!confirm(msg)) return;

    fetch(BASE_URL + '/inventarios/deleteCronograma', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': CSRF_TOKEN,
      },
      body: new URLSearchParams({ _csrf: CSRF_TOKEN, id: id }),
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        toast(res.message, 'success');
        // Fade out de la fila y recarga
        const row = document.querySelector(`.cron-row[data-id="${id}"]`);
        if (row) {
          row.style.transition = 'opacity .3s, transform .3s';
          row.style.opacity = '0';
          row.style.transform = 'translateX(-20px)';
        }
        setTimeout(() => location.reload(), 800);
      } else {
        toast(res.message || 'Error al eliminar', 'error');
      }
    })
    .catch(() => toast('Error de conexión', 'error'));
  };

  // ══════════════════════════════════════════════════
  //  MODAL: Nuevo cronograma
  // ══════════════════════════════════════════════════
  const modalCron = document.getElementById('modalCronograma');
  const btnNuevo  = document.getElementById('btnNuevoCronograma');
  if (btnNuevo) btnNuevo.addEventListener('click', abrirModal);

  function abrirModal() {
    document.getElementById('formCronograma').reset();
    document.getElementById('lineasTBody').innerHTML = '';
    agregarLinea(); // arranca con 1 línea
    document.getElementById('resultadoImportInventario').style.display = 'none';
    modalCron.classList.add('show');
  }

  window.cerrarModal = function () { modalCron.classList.remove('show'); };

  // Cerrar al click fuera
  modalCron.addEventListener('click', (e) => {
    if (e.target === modalCron) cerrarModal();
  });

  // ── Filas dinámicas ────────────────────────────────
  let lineaIdx = 0;
  window.agregarLinea = function () {
    lineaIdx++;
    const cat = window.INV_CAT || {};
    const prodOpts = (cat.productos || []).map(p =>
      `<option value="${p.id_producto}" data-unidad="${p.unidad}">${p.nombre} (${p.presentacion})</option>`
    ).join('');
    const bodOpts = (cat.bodegas || []).map(b =>
      `<option value="${b.id_bodega}">${b.codigo} · ${b.nombre}</option>`
    ).join('');

    const tr = document.createElement('tr');
    tr.dataset.idx = lineaIdx;
    tr.innerHTML = `
      <td>
        <select class="fs prod-sel" onchange="syncUnidad(this)">
          <option value="">— Producto —</option>${prodOpts}
        </select>
      </td>
      <td>
        <select class="fs">
          <option value="">— Bodega —</option>${bodOpts}
        </select>
      </td>
      <td><input type="date" class="fc"></td>
      <td><input type="number" class="fc" step="0.01" min="0" placeholder="0"></td>
      <td><input type="text" class="fc unidad-cell" readonly placeholder="—" style="background:var(--gris);color:var(--texto-sec);"></td>
      <td><button type="button" class="btn-rm" onclick="quitarLinea(this)"><i class="fas fa-trash"></i></button></td>
    `;
    document.getElementById('lineasTBody').appendChild(tr);
  };

  window.quitarLinea = function (btn) {
    const tr = btn.closest('tr');
    const total = document.querySelectorAll('#lineasTBody tr').length;
    if (total <= 1) {
      alert('Debe quedar al menos una línea.');
      return;
    }
    tr.remove();
  };

  window.syncUnidad = function (sel) {
    const opt = sel.options[sel.selectedIndex];
    const u = opt ? opt.dataset.unidad : '';
    const td = sel.closest('tr').querySelector('.unidad-cell');
    if (td) td.value = u || '—';
  };

  // ── Importar líneas desde Excel/CSV ───────────────
  const inputExcel = document.getElementById('archivoInventarioExcel');
  const btnImportExcel = document.getElementById('btnImportarInventarioExcel');
  const btnPlantilla = document.getElementById('btnPlantillaInventario');
  if (btnImportExcel && inputExcel) {
    btnImportExcel.addEventListener('click', () => inputExcel.click());
    inputExcel.addEventListener('change', importarExcel);
  }
  if (btnPlantilla) btnPlantilla.addEventListener('click', descargarPlantilla);

  function descargarPlantilla() {
    const contenido = '\uFEFFcodigo_producto,codigo_bodega,fecha_programada,cantidad\r\n' +
      'CODIGO_PRODUCTO,CODIGO_BODEGA,2026-07-01,100\r\n';
    const url = URL.createObjectURL(new Blob([contenido], { type: 'text/csv;charset=utf-8;' }));
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = 'plantilla_inventario.csv';
    enlace.click();
    URL.revokeObjectURL(url);
  }

  function importarExcel() {
    if (!inputExcel.files.length) return;
    const fd = new FormData();
    fd.append('_csrf', CSRF_TOKEN);
    fd.append('archivo', inputExcel.files[0]);
    btnImportExcel.disabled = true;
    btnImportExcel.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Leyendo archivo...';

    fetch(BASE_URL + '/inventarios/importarExcel', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF_TOKEN },
      body: fd,
    })
    .then(r => r.json())
    .then(res => {
      btnImportExcel.disabled = false;
      btnImportExcel.innerHTML = '<i class="fas fa-file-excel"></i> Importar líneas desde Excel';
      inputExcel.value = '';
      const resultado = document.getElementById('resultadoImportInventario');
      resultado.style.display = 'block';
      if (!res.success) {
        resultado.style.color = '#991b1b';
        resultado.textContent = res.message || 'No fue posible importar el archivo.';
        return;
      }
      document.getElementById('lineasTBody').innerHTML = '';
      (res.data.lineas || []).forEach(agregarLineaImportada);
      const errores = res.data.errores || [];
      resultado.style.color = errores.length ? '#854d0e' : '#166534';
      resultado.innerHTML = `<strong>${res.message}</strong>` +
        (errores.length ? `<br>${errores.slice(0, 8).join('<br>')}${errores.length > 8 ? '<br>...' : ''}` : '');
    })
    .catch(() => {
      btnImportExcel.disabled = false;
      btnImportExcel.innerHTML = '<i class="fas fa-file-excel"></i> Importar líneas desde Excel';
      toast('Error de conexión al importar.', 'error');
    });
  }

  function agregarLineaImportada(linea) {
    agregarLinea();
    const tr = document.querySelector('#lineasTBody tr:last-child');
    const sels = tr.querySelectorAll('select');
    const inps = tr.querySelectorAll('input');
    sels[0].value = String(linea.id_producto);
    sels[1].value = String(linea.id_bodega);
    inps[0].value = linea.fecha_programada;
    inps[1].value = linea.cantidad_programada;
    tr.querySelector('.unidad-cell').value = linea.unidad || '—';
  }

  // ── Guardar cronograma ─────────────────────────────
  document.getElementById('btnGuardarCronograma').addEventListener('click', guardarCronograma);

  function guardarCronograma() {
    const form = document.getElementById('formCronograma');
    const datos = {
      id_proveedor:     form.id_proveedor.value,
      num_contrato:     form.num_contrato.value.trim(),
      num_orden_compra: form.num_orden_compra.value.trim(),
      monto_total:      parseFloat(form.monto_total.value || 0),
      fecha_inicio:     form.fecha_inicio.value,
      fecha_fin:        form.fecha_fin.value,
      descripcion:      form.descripcion.value.trim(),
    };

    // Recolectar líneas
    const lineas = [];
    document.querySelectorAll('#lineasTBody tr').forEach(tr => {
      const sels = tr.querySelectorAll('select');
      const inps = tr.querySelectorAll('input');
      lineas.push({
        id_producto:        sels[0].value,
        id_bodega:          sels[1].value,
        fecha_programada:   inps[0].value,
        cantidad_programada: parseFloat(inps[1].value || 0),
      });
    });

    const btn = document.getElementById('btnGuardarCronograma');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    fetch(BASE_URL + '/inventarios/saveCronograma', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': CSRF_TOKEN,
      },
      body: new URLSearchParams({
        _csrf:  CSRF_TOKEN,
        datos:  JSON.stringify(datos),
        lineas: JSON.stringify(lineas),
      }),
    })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false; btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Guardar cronograma';
      if (res.success) {
        toast(res.message, 'success');
        cerrarModal();
        setTimeout(() => location.reload(), 800);
      } else {
        toast(res.message || 'Error al guardar', 'error');
      }
    })
    .catch(() => {
      btn.disabled = false; btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Guardar cronograma';
      toast('Error de conexión', 'error');
    });
  }

  // ══════════════════════════════════════════════════
  //  MODAL: Recibir línea
  // ══════════════════════════════════════════════════
  const modalRec = document.getElementById('modalRecibir');

  window.abrirRecibir = function (idLinea, producto, pendiente) {
    document.getElementById('recLineaId').value = idLinea;
    document.getElementById('recProducto').textContent = producto;
    document.getElementById('recPendiente').textContent = Number(pendiente).toLocaleString();
    document.getElementById('recCantidad').value = pendiente;
    document.getElementById('recCantidad').max = pendiente;
    modalRec.classList.add('show');
  };

  window.cerrarModalRecibir = function () { modalRec.classList.remove('show'); };
  modalRec.addEventListener('click', (e) => { if (e.target === modalRec) cerrarModalRecibir(); });

  document.getElementById('btnConfirmarRecibir').addEventListener('click', confirmarRecibir);

  function confirmarRecibir() {
    const idLinea  = document.getElementById('recLineaId').value;
    const cantidad = parseFloat(document.getElementById('recCantidad').value || 0);
    const fecha    = document.getElementById('recFecha').value;
    const resp     = document.getElementById('recResponsable').value.trim();

    if (cantidad <= 0) { toast('La cantidad debe ser mayor a 0', 'error'); return; }

    const btn = document.getElementById('btnConfirmarRecibir');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';

    fetch(BASE_URL + '/inventarios/recibirLinea', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': CSRF_TOKEN,
      },
      body: new URLSearchParams({
        _csrf:           CSRF_TOKEN,
        id_linea:        idLinea,
        cantidad:        cantidad,
        fecha_recibida:  fecha,
        responsable:     resp,
      }),
    })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Confirmar recepción';
      if (res.success) {
        toast(res.message, 'success');
        cerrarModalRecibir();
        setTimeout(() => location.reload(), 800);
      } else {
        toast(res.message || 'Error', 'error');
      }
    })
    .catch(() => {
      btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Confirmar recepción';
      toast('Error de conexión', 'error');
    });
  }

  // ══════════════════════════════════════════════════
  //  TOAST mínimo (compatible con main.js si existe)
  // ══════════════════════════════════════════════════
  function toast(msg, type) {
    if (window.sagToast) { window.sagToast(msg, type); return; }
    let t = document.getElementById('_invToast');
    if (!t) {
      t = document.createElement('div');
      t.id = '_invToast';
      t.className = 'sag-toast';
      t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;min-width:280px;background:#0f172a;color:#fff;padding:14px 18px;border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,.2);font-family:Inter,sans-serif;font-size:.86rem;font-weight:500;display:flex;align-items:center;gap:10px;';
      document.body.appendChild(t);
    }
    t.style.borderLeft = '4px solid ' + (type === 'success' ? '#16a34a' : type === 'error' ? '#dc2626' : '#3b82f6');
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'circle-check' : type === 'error' ? 'circle-xmark' : 'circle-info'}"></i><span>${msg}</span>`;
    t.style.display = 'flex';
    clearTimeout(t._tm);
    t._tm = setTimeout(() => { t.style.display = 'none'; }, 4000);
  }

})();
