/**
 * entregas.js — Movimientos Trazaragro (vista nativa OIRSA)
 * Refactor mayo 2026
 *
 * - Render flat: una fila por MovementId
 * - Filtros client-side sobre window.OIRSA_MOVS
 * - Sincronización: clic normal = incremental, Shift+Clic = limpia BD
 */
$(function () {

    // Data global desde PHP
    let MOVS = Array.isArray(window.OIRSA_MOVS) ? window.OIRSA_MOVS.slice() : [];
    let MOVS_FILTRADOS = MOVS.slice();

    const $tbody    = $('#tbodyOirsa');
    const $contador = $('#contadorFiltrados');

    // ── HELPERS ──────────────────────────────────────────────
    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function fmtFecha(s) {
        if (!s) return '';
        // Acepta "YYYY-MM-DD HH:MM:SS" o ISO
        const d = new Date(s.replace(' ', 'T'));
        if (isNaN(d.getTime())) return escapeHtml(s);
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ` +
               `${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function fmtNum(n) {
        if (n === null || n === undefined || n === '') return '';
        const v = parseFloat(n);
        return isNaN(v) ? '' : v.toLocaleString('es-HN', { maximumFractionDigits: 2 });
    }

    function badgeEstado(estado) {
        const map = {
            'entregado':  ['est-entregado','Entregado'],
            'pendiente':  ['est-pendiente','Pendiente'],
            'observado':  ['est-observado','Observado'],
            'anulado':    ['est-anulado','Anulado'],
        };
        const [cls, lbl] = map[estado] || ['est-pendiente', estado || 'Pendiente'];
        return `<span class="est-badge ${cls}">${lbl}</span>`;
    }

    // ── RENDER ───────────────────────────────────────────────
    function renderTabla() {
        if (!MOVS_FILTRADOS.length) {
            $tbody.html(`<tr><td colspan="18" style="text-align:center;padding:30px;color:#888;">
                <i class="fas fa-inbox" style="font-size:1.5rem;margin-bottom:6px;"></i><br>
                Sin movimientos para mostrar.
            </td></tr>`);
            $contador.html('');
            return;
        }

        const rows = MOVS_FILTRADOS.map(m => {
            const fecha = fmtFecha(m.fecha_autorizacion);
            const destDeptMun = [m.destino_departamento, m.destino_municipio].filter(Boolean).join(' / ');
            const codigoTraza = m.codigo_trazabilidad
                ? `<strong style="color:#0f766e;">${escapeHtml(m.codigo_trazabilidad)}</strong>`
                : `<span style="color:#bbb;font-style:italic;">— sin código —</span>`;
            return `
            <tr data-id="${m.movement_id}" class="row-mov">
              <td class="wrap">${escapeHtml(m.rubro)}</td>
              <td class="wrap">${escapeHtml(m.tipo_movimiento)}</td>
              <td class="wrap"><strong>${escapeHtml(m.objeto_trazable)}</strong></td>
              <td>${codigoTraza}</td>
              <td><strong>${escapeHtml(m.guiasa_no)}</strong></td>
              <td>${escapeHtml(m.codigo_autorizacion)}</td>
              <td>${fecha}</td>
              <td class="wrap">${escapeHtml(m.origen_persona)}</td>
              <td class="wrap">${escapeHtml(m.origen_establecimiento)}</td>
              <td>${escapeHtml(m.origen_departamento)}</td>
              <td class="wrap"><strong>${escapeHtml(m.destino_nombre || m.destino_persona)}</strong></td>
              <td>${escapeHtml(m.destino_dni)}</td>
              <td class="wrap">${escapeHtml(m.destino_establecimiento)}</td>
              <td>${escapeHtml(destDeptMun)}</td>
              <td class="cantidad">${fmtNum(m.cantidad)}</td>
              <td>${escapeHtml(m.unidad)}</td>
              <td class="wrap"><strong style="color:#0d9488;">${escapeHtml(m.autorizado_por)}</strong></td>
              <td>${badgeEstado(m.estado_local)}</td>
            </tr>`;
        }).join('');

        $tbody.html(rows);
        $contador.html(`Mostrando <strong>${MOVS_FILTRADOS.length}</strong> de ${MOVS.length} movimientos`);
    }

    // ── FILTROS ──────────────────────────────────────────────
    function aplicarFiltros() {
        const fRubro  = $('#fRubro').val();
        const fTipo   = $('#fTipo').val();
        const fObjeto = $('#fObjeto').val();
        const fDepto  = $('#fDepto').val();
        const fEstado = $('#fEstado').val();
        const fDesde  = $('#fDesde').val();
        const fHasta  = $('#fHasta').val();
        const fBusca  = ($('#fBusca').val() || '').trim().toLowerCase();

        MOVS_FILTRADOS = MOVS.filter(m => {
            if (fRubro  && m.rubro  !== fRubro)  return false;
            if (fTipo   && m.tipo_movimiento !== fTipo) return false;
            if (fObjeto && m.objeto_trazable !== fObjeto) return false;
            if (fDepto  && m.destino_departamento !== fDepto) return false;
            if (fEstado && m.estado_local !== fEstado) return false;
            if (fDesde && (m.fecha_autorizacion || '') < fDesde) return false;
            if (fHasta && (m.fecha_autorizacion || '').substring(0,10) > fHasta) return false;
            if (fBusca) {
                const haystack = [
                    m.destino_dni, m.destino_nombre, m.destino_persona,
                    m.guiasa_no, m.codigo_autorizacion, m.codigo_trazabilidad,
                    m.objeto_trazable, m.autorizado_por,
                ].filter(Boolean).join(' ').toLowerCase();
                if (!haystack.includes(fBusca)) return false;
            }
            return true;
        });
        renderTabla();
    }

    $('#fRubro, #fTipo, #fObjeto, #fDepto, #fEstado, #fDesde, #fHasta').on('change', aplicarFiltros);
    $('#fBusca').on('input', aplicarFiltros);

    $('#btnLimpiarFiltros').on('click', function () {
        $('#fRubro, #fTipo, #fObjeto, #fDepto, #fEstado').val('');
        $('#fDesde, #fHasta, #fBusca').val('');
        aplicarFiltros();
    });

    // ── DETALLE (modal) ──────────────────────────────────────
    $tbody.on('click', '.row-mov', function () {
        const id = $(this).data('id');
        const m = MOVS.find(x => String(x.movement_id) === String(id));
        if (!m) return;
        mostrarDetalle(m);
    });

    function mostrarDetalle(m) {
        const row = (lbl, val) =>
            `<label>${lbl}</label><div class="v">${val || '<span style="color:#bbb;">—</span>'}</div>`;

        const html = `
          <div class="det-section">
            <div class="det-section-title">Identificación</div>
            <div class="det-grid">
              ${row('Movement ID', escapeHtml(m.movement_id))}
              ${row('Rubro', escapeHtml(m.rubro))}
              ${row('Tipo movimiento', escapeHtml(m.tipo_movimiento))}
              ${row('Objeto trazable', '<strong>'+escapeHtml(m.objeto_trazable)+'</strong>')}
              ${row('Código de trazabilidad', m.codigo_trazabilidad
                  ? '<strong style="color:#0f766e;">'+escapeHtml(m.codigo_trazabilidad)+'</strong>'
                  : '<em style="color:#bbb;">aún no asignado (entrega pendiente)</em>')}
              ${row('GUIASA No.', '<strong>'+escapeHtml(m.guiasa_no)+'</strong>')}
              ${row('Código autorización', escapeHtml(m.codigo_autorizacion))}
              ${row('Status OIRSA', escapeHtml(m.status_oirsa))}
              ${row('Estado local', badgeEstado(m.estado_local))}
            </div>
          </div>

          <div class="det-section">
            <div class="det-section-title">Origen</div>
            <div class="det-grid">
              ${row('Persona', escapeHtml(m.origen_persona))}
              ${row('Establecimiento', escapeHtml(m.origen_establecimiento))}
              ${row('CUE', escapeHtml(m.origen_cue))}
              ${row('Departamento', escapeHtml(m.origen_departamento))}
              ${row('Municipio', escapeHtml(m.origen_municipio))}
            </div>
          </div>

          <div class="det-section">
            <div class="det-section-title">Destino (Beneficiario)</div>
            <div class="det-grid">
              ${row('Persona', '<strong>'+escapeHtml(m.destino_nombre || m.destino_persona)+'</strong>')}
              ${row('DNI', escapeHtml(m.destino_dni))}
              ${row('Establecimiento', escapeHtml(m.destino_establecimiento))}
              ${row('CUE', escapeHtml(m.destino_cue))}
              ${row('Departamento', escapeHtml(m.destino_departamento))}
              ${row('Municipio', escapeHtml(m.destino_municipio))}
            </div>
          </div>

          <div class="det-section">
            <div class="det-section-title">Cantidad / Logística</div>
            <div class="det-grid">
              ${row('Cantidad', '<strong>'+fmtNum(m.cantidad)+' '+escapeHtml(m.unidad)+'</strong>')}
              ${row('Condición', escapeHtml(m.condicion))}
              ${row('Propósito', escapeHtml(m.proposito))}
              ${row('Transportista', escapeHtml(m.transportista))}
              ${row('Vehículo', escapeHtml(m.vehiculo))}
            </div>
          </div>

          <div class="det-section">
            <div class="det-section-title">Autorización</div>
            <div class="det-grid">
              ${row('Autorizado por (SAG/OIRSA)', '<strong style="color:#0d9488;">'+escapeHtml(m.autorizado_por)+'</strong>')}
              ${row('Creado por', escapeHtml(m.creado_por))}
              ${row('Fecha registro', fmtFecha(m.fecha_registro))}
              ${row('Fecha autorización', fmtFecha(m.fecha_autorizacion))}
              ${row('Fecha expiración', fmtFecha(m.fecha_expiracion))}
              ${row('Última sincronización', fmtFecha(m.synced_at))}
            </div>
          </div>
        `;
        $('#detBody').html(html);
        $('#modalDetalle').addClass('show');
    }

    window.cerrarDetalle = function () {
        $('#modalDetalle').removeClass('show');
    };

    // Cerrar modal con click en overlay o tecla Escape
    $('#modalDetalle').on('click', function (e) {
        if (e.target === this) cerrarDetalle();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') cerrarDetalle();
    });

    // ── BOTÓN SINCRONIZAR ────────────────────────────────────
    $('#btnSincronizarTrazaragro').on('click', function (e) {
        const $btn = $(this);
        const limpiar = e.shiftKey ? 1 : 0;

        if (limpiar && !confirm('Esto BORRARÁ todos los movimientos sincronizados y volverá a traerlos desde OIRSA.\n\n¿Estás seguro?')) {
            return;
        }

        $btn.addClass('is-loading').prop('disabled', true);
        $btn.html('<i class="fas fa-rotate"></i> ' + (limpiar ? 'Limpiando y sincronizando...' : 'Sincronizando con OIRSA...'));

        SAG.ajax({
            url: '/entregas/sincronizarTrazaragro',
            data: { limpiar: limpiar },
            success: r => {
                $btn.removeClass('is-loading').prop('disabled', false);
                $btn.html('<i class="fas fa-rotate"></i> Sincronizar con Trazaragro');
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
                setTimeout(() => window.location.reload(), 1200);
            },
            error: () => {
                $btn.removeClass('is-loading').prop('disabled', false);
                $btn.html('<i class="fas fa-rotate"></i> Sincronizar con Trazaragro');
            }
        });
    });

    // ── INIT ─────────────────────────────────────────────────
    renderTabla();
});
