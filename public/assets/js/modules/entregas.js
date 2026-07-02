/**
 * entregas.js — Movimientos Trazaragro (vista nativa OIRSA)
 * Refactor mayo 2026 · Paginación server-side julio 2026
 *
 * - Render flat: una fila por MovementId
 * - La tabla y el reporte por productor se cargan por AJAX paginado
 *   (/entregas/datos y /entregas/datosProductores); los filtros se aplican
 *   en SQL. El dataset completo ya no viaja embebido en la página
 *   (window.OIRSA_MOVS agotaba la memoria de PHP y del navegador).
 * - Sincronización: clic normal = incremental, Shift+Clic = limpia BD
 */
// Tabs del módulo Entregas — expuesto globalmente porque los botones lo invocan inline
window.switchEntregasTab = function (tab) {
    ['resumen', 'movimientos', 'departamentos', 'productores', 'bodegas', 'anomalias'].forEach(t => {
        const el = document.getElementById('tab-ent-' + t);
        if (el) el.style.display = (t === tab) ? 'block' : 'none';
    });
    const order = ['resumen', 'movimientos', 'departamentos', 'productores', 'bodegas', 'anomalias'];
    const idx = order.indexOf(tab);
    document.querySelectorAll('.mode-tab').forEach((btn, i) => {
        btn.classList.toggle('active', i === idx);
    });
    // Recordar la pestaña activa para que sobreviva al reload tras sync
    try { localStorage.setItem('sag_entregas_tab', tab); } catch (e) {}
};

$(function () {

    // Restaurar última pestaña activa (si el usuario sincronizó y la página recargó)
    try {
        const saved = localStorage.getItem('sag_entregas_tab');
        if (saved && ['resumen','movimientos','departamentos','productores','bodegas','anomalias'].includes(saved)) {
            window.switchEntregasTab(saved);
        }
    } catch (e) {}

    // Estado de paginación server-side
    const MOVS_POR_PAGINA  = 100;
    const PRODS_POR_PAGINA = 30;
    let movsPagina = 1,  movsTotal = 0,  movsRows = [], movsReq = 0;
    let prodsPagina = 1, prodsTotal = 0, prodsReq = 0;

    const $tbody      = $('#tbodyOirsa');
    const $contador   = $('#contadorFiltrados');
    const $pagerMovs  = $('#pagerMovs');
    const $listaProds = $('#listaProductores');
    const $pagerProds = $('#pagerProds');

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

    // ── PAGINADOR (compartido) ───────────────────────────────
    function renderPager($el, pagina, total, porPagina, onGo) {
        if (!$el.length || total <= porPagina) { $el.html(''); return; }
        const totalPag = Math.max(1, Math.ceil(total / porPagina));
        $el.html(`
            <button type="button" class="pg-btn" data-pg="prev" ${pagina <= 1 ? 'disabled' : ''}>
              <i class="fas fa-chevron-left"></i> Anterior
            </button>
            <span>Página <strong>${pagina}</strong> de ${totalPag.toLocaleString('es-HN')}</span>
            <button type="button" class="pg-btn" data-pg="next" ${pagina >= totalPag ? 'disabled' : ''}>
              Siguiente <i class="fas fa-chevron-right"></i>
            </button>
        `);
        $el.find('[data-pg="prev"]').on('click', () => onGo(pagina - 1));
        $el.find('[data-pg="next"]').on('click', () => onGo(pagina + 1));
    }

    // ── CARGA + RENDER (tab Movimientos) ─────────────────────
    function filtrosMovs() {
        return {
            rubro:  $('#fRubro').val()  || '',
            tipo:   $('#fTipo').val()   || '',
            objeto: $('#fObjeto').val() || '',
            depto:  $('#fDepto').val()  || '',
            estado: $('#fEstado').val() || '',
            desde:  $('#fDesde').val()  || '',
            hasta:  $('#fHasta').val()  || '',
            busca:  ($('#fBusca').val() || '').trim(),
        };
    }

    function cargarMovs(pagina) {
        const req = ++movsReq;
        $tbody.html(`<tr><td colspan="18" style="text-align:center;padding:30px;color:#888;">
            <i class="fas fa-spinner fa-spin" style="font-size:1.3rem;"></i><br>
            Cargando movimientos...
        </td></tr>`);
        SAG.ajax({
            url: '/entregas/datos',
            data: Object.assign({ page: pagina, per_page: MOVS_POR_PAGINA }, filtrosMovs()),
            success: r => {
                if (req !== movsReq) return;   // llegó tarde: hay una petición más nueva
                if (!r.success || !r.data) {
                    movsRows = []; movsTotal = 0;
                    renderTabla();
                    return;
                }
                movsPagina = r.data.page;
                movsTotal  = r.data.total;
                movsRows   = r.data.rows || [];
                renderTabla();
            }
        });
    }

    function renderTabla() {
        renderPager($pagerMovs, movsPagina, movsTotal, MOVS_POR_PAGINA, cargarMovs);
        if (!movsRows.length) {
            $tbody.html(`<tr><td colspan="18" style="text-align:center;padding:30px;color:#888;">
                <i class="fas fa-inbox" style="font-size:1.5rem;margin-bottom:6px;"></i><br>
                Sin movimientos para mostrar.
            </td></tr>`);
            $contador.html('');
            return;
        }

        const rows = movsRows.map(m => {
            const fecha = fmtFecha(m.fecha_autorizacion);
            const destDeptMun = [m.destino_departamento, m.destino_municipio].filter(Boolean).join(' / ');
            const codigoTraza = m.codigo_trazabilidad
                ? `<strong style="color:#0f766e;">${escapeHtml(m.codigo_trazabilidad)}</strong>`
                : `<span style="color:#bbb;font-style:italic;">— sin código —</span>`;
            return `
            <tr data-id="${m.id}" class="row-mov">
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
        const desde = (movsPagina - 1) * MOVS_POR_PAGINA + 1;
        const hasta = Math.min(movsTotal, desde + movsRows.length - 1);
        $contador.html(`Mostrando <strong>${desde.toLocaleString('es-HN')}–${hasta.toLocaleString('es-HN')}</strong> de ${movsTotal.toLocaleString('es-HN')} movimientos`);
    }

    // ── FILTROS (se aplican en el servidor) ──────────────────
    $('#fRubro, #fTipo, #fObjeto, #fDepto, #fEstado, #fDesde, #fHasta').on('change', () => cargarMovs(1));

    let buscaTimer = null;
    $('#fBusca').on('input', function () {
        clearTimeout(buscaTimer);
        buscaTimer = setTimeout(() => cargarMovs(1), 350);
    });

    $('#btnLimpiarFiltros').on('click', function () {
        $('#fRubro, #fTipo, #fObjeto, #fDepto, #fEstado').val('');
        $('#fDesde, #fHasta, #fBusca').val('');
        cargarMovs(1);
    });

    // ── CARGA + RENDER (tab Por Productor) ───────────────────
    function cargarProds(pagina) {
        if (!$listaProds.length) return;   // el servidor mostró el estado vacío
        const req = ++prodsReq;
        $listaProds.html(`<div style="text-align:center;padding:40px;color:#888;">
            <i class="fas fa-spinner fa-spin" style="font-size:1.3rem;"></i><br>Cargando productores...
        </div>`);
        SAG.ajax({
            url: '/entregas/datosProductores',
            data: {
                page:     pagina,
                per_page: PRODS_POR_PAGINA,
                depto:    $('#fpDepto').val()  || '',
                padron:   $('#fpPadron').val() || '',
                busca:    ($('#fpBusca').val() || '').trim(),
            },
            success: r => {
                if (req !== prodsReq) return;
                if (!r.success || !r.data) {
                    prodsTotal = 0;
                    renderProductores([]);
                    return;
                }
                prodsPagina = r.data.page;
                prodsTotal  = r.data.total;
                renderProductores(r.data.rows || []);
            }
        });
    }

    function renderProductores(rows) {
        renderPager($pagerProds, prodsPagina, prodsTotal, PRODS_POR_PAGINA, cargarProds);
        if (!rows.length) {
            $listaProds.html(`<div style="text-align:center;padding:40px;color:#888;">
                <i class="fas fa-user-tag" style="font-size:1.5rem;margin-bottom:6px;display:block;color:#bbb;"></i>
                Sin productores para los filtros seleccionados.
            </div>`);
            $('#fpContador').html('');
            return;
        }
        $listaProds.html(rows.map(prodCardHtml).join(''));
        const desde = (prodsPagina - 1) * PRODS_POR_PAGINA + 1;
        const hasta = Math.min(prodsTotal, desde + rows.length - 1);
        $('#fpContador').html(`Mostrando <strong>${desde.toLocaleString('es-HN')}–${hasta.toLocaleString('es-HN')}</strong> de ${prodsTotal.toLocaleString('es-HN')} productor(es)`);
    }

    function prodCardHtml(p) {
        let badge = '';
        if (p.validacion === 'no_padron') {
            badge = '<span style="background:#fed7aa;color:#9a3412;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;margin-left:6px;">⚠ NO EN PADRÓN</span>';
        } else if (p.validacion === 'en_padron') {
            badge = '<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;margin-left:6px;">✓ EN PADRÓN</span>';
        }
        const ubicacion = escapeHtml(p.departamento || '') + (p.municipio ? ' / ' + escapeHtml(p.municipio) : '');
        const estab = p.establecimiento
            ? `<span style="margin:0 8px;color:#bbb;">·</span><i class="fas fa-house" style="margin-right:3px;"></i>${escapeHtml(p.establecimiento)}`
            : '';
        const acta = p.acta_url
            ? `<a href="${escapeHtml(p.acta_url)}" target="_blank"
                  style="display:inline-block;margin-top:8px;padding:5px 12px;background:#0d9488;color:#fff;border-radius:6px;text-decoration:none;font-size:.72rem;font-weight:700;"
                  title="Generar acta imprimible / PDF"><i class="fas fa-file-pdf"></i> Acta / PDF</a>`
            : '';
        const filas = (p.objetos || []).map(o => `
            <tr style="border-bottom:1px dashed #f3f4f6;">
              <td style="padding:5px 6px;"><strong>${escapeHtml(o.objeto)}</strong></td>
              <td style="padding:5px 6px;">${o.codigo_traza
                  ? '<strong style="color:#0f766e;">' + escapeHtml(o.codigo_traza) + '</strong>'
                  : '<em style="color:#bbb;">— sin código —</em>'}</td>
              <td style="padding:5px 6px;">${escapeHtml(o.guiasa)}</td>
              <td style="padding:5px 6px;">${escapeHtml(String(o.fecha || '').substring(0, 10))}</td>
              <td style="padding:5px 6px;text-align:right;font-weight:600;">${fmtNum(o.cantidad)} ${escapeHtml(o.unidad)}</td>
              <td style="padding:5px 6px;color:#0d9488;">${escapeHtml(o.autoriza)}</td>
              <td style="padding:5px 6px;text-align:center;">
                <span class="est-badge est-${escapeHtml(o.estado)}">${o.estado === 'entregado' ? '✓ Entregado' : 'Pendiente'}</span>
              </td>
            </tr>`).join('');
        const omitidos = p.objetos_omitidos > 0
            ? `<div style="padding:6px 0 0;color:#888;font-size:.72rem;">+ ${p.objetos_omitidos} movimiento(s) más no listado(s)</div>`
            : '';
        return `
        <div class="prod-card" style="padding:14px 16px;border-bottom:2px solid #f1f5f9;background:${p.tiene_alerta ? '#fffbeb' : '#fff'};">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
            <div style="flex:1;min-width:240px;">
              <strong style="font-size:.95rem;color:#1a1a1a;">${escapeHtml(p.nombre)}</strong>${badge}
              <div style="margin-top:3px;font-size:.78rem;color:#666;">
                <i class="fas fa-id-card" style="margin-right:3px;"></i>DNI: <strong>${escapeHtml(p.dni)}</strong>
                <span style="margin:0 8px;color:#bbb;">·</span>
                <i class="fas fa-map-marker-alt" style="margin-right:3px;"></i>${ubicacion}${estab}
              </div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:1.2rem;font-weight:800;color:#16a34a;line-height:1;">${p.num_objetos}</div>
              <div style="font-size:.7rem;color:#666;text-transform:uppercase;">objetos</div>
              <div style="font-size:.7rem;color:#888;margin-top:3px;">
                ${p.num_manifiestos} GUIASA(s) · <span style="color:#16a34a;">${p.entregados} entreg.</span> · <span style="color:#d97706;">${p.pendientes} pend.</span>
              </div>
              ${acta}
            </div>
          </div>
          <table style="width:100%;margin-top:10px;font-size:.76rem;border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:1.5px solid #e5e7eb;color:#555;text-transform:uppercase;font-size:.65rem;">
                <th style="text-align:left;padding:5px 6px;">Objeto trazable</th>
                <th style="text-align:left;padding:5px 6px;">Cód. trazabilidad</th>
                <th style="text-align:left;padding:5px 6px;">GUIASA</th>
                <th style="text-align:left;padding:5px 6px;">Fecha</th>
                <th style="text-align:right;padding:5px 6px;">Cantidad</th>
                <th style="text-align:left;padding:5px 6px;">Autorizó</th>
                <th style="text-align:center;padding:5px 6px;">Estado</th>
              </tr>
            </thead>
            <tbody>${filas}</tbody>
          </table>
          ${omitidos}
        </div>`;
    }

    let fpBuscaTimer = null;
    $('#fpDepto, #fpPadron').on('change', () => cargarProds(1));
    $('#fpBusca').on('input', function () {
        clearTimeout(fpBuscaTimer);
        fpBuscaTimer = setTimeout(() => cargarProds(1), 350);
    });
    $('#btnFpLimpiar').on('click', function () {
        $('#fpDepto, #fpPadron, #fpBusca').val('');
        cargarProds(1);
    });

    // ── DETALLE (modal) ──────────────────────────────────────
    $tbody.on('click', '.row-mov', function () {
        const id = $(this).data('id');
        const m = movsRows.find(x => String(x.id) === String(id));
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

        const data = { limpiar: limpiar };
        if (limpiar) {
            data.tipos_movimiento = '111,112,113';
            data.desde = '2000-01-01';
            data.top = 100000;
        }

        SAG.ajax({
            url: '/entregas/sincronizarTrazaragro',
            data: data,
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

    // ── SYNC PERSONALIZADA (dropdown opciones) ───────────────
    // Lanza el modal con el modo elegido del dropdown
    $(document).on('click', '[data-sync-modo]', function (e) {
        e.preventDefault();
        const modo = $(this).data('sync-modo');
        const titulos = {
            guiasa: 'Sincronizar una GUIASA especifica',
            bodega: 'Sincronizar por bodega',
            rango:  'Sincronizar por rango de fechas',
            departamento: 'Sincronizar por departamento',
            historico: 'Historico completo desde 2026-04-01'
        };
        const descs = {
            guiasa: 'Solo trae movimientos de OIRSA cuyo numero de GUIASA contenga el valor ingresado.',
            bodega: 'Solo trae movimientos donde la bodega (origen o destino) coincida con el CUE.',
            rango:  'Trae movimientos cuya fecha de autorizacion este en el rango.',
            departamento: 'Trae movimientos donde el departamento de origen o destino coincida.',
            historico: 'Re-sincronizacion completa desde la fecha base de los programas.'
        };
        $('#syncPersTitulo').text(titulos[modo] || 'Sincronizacion personalizada');
        $('#syncPersDesc').text(descs[modo] || '');
        $('#syncCampoGuiasa,#syncCampoBodega,#syncCampoRango,#syncCampoHistorico,#syncCampoDepto').hide();
        if (modo === 'guiasa') $('#syncCampoGuiasa').show();
        if (modo === 'bodega') $('#syncCampoBodega').show();
        if (modo === 'rango')  {
            const hoy = new Date();
            const mesAtras = new Date(hoy.getTime() - 30*24*3600*1000);
            $('#syncInputDesde').val(mesAtras.toISOString().slice(0,10));
            $('#syncInputHasta').val(hoy.toISOString().slice(0,10));
            $('#syncCampoRango').show();
        }
        if (modo === 'historico') $('#syncCampoHistorico').show();
        if (modo === 'departamento') $('#syncCampoDepto').show();
        $('#btnSyncPersConfirmar').data('modo', modo);
        new bootstrap.Modal(document.getElementById('modalSyncPers')).show();
    });

    // Confirmar sync personalizada
    $('#btnSyncPersConfirmar').on('click', function () {
        const $btn = $(this);
        const modo = $btn.data('modo');
        const data = { modo: modo };

        if (modo === 'guiasa') {
            const g = ($('#syncInputGuiasa').val() || '').trim();
            if (!g) { SAG.toast('Ingresa un numero de GUIASA.', 'warning'); return; }
            data.guiasa_no = g;
        } else if (modo === 'bodega') {
            const c = ($('#syncInputBodega').val() || '').trim();
            if (!c) { SAG.toast('Ingresa un CUE de bodega.', 'warning'); return; }
            data.bodega_cue = c;
        } else if (modo === 'rango') {
            const d = $('#syncInputDesde').val();
            const h = $('#syncInputHasta').val();
            if (!d || !h) { SAG.toast('Completa ambas fechas.', 'warning'); return; }
            if (d < '2026-04-01') { SAG.toast('La fecha desde no puede ser anterior a 2026-04-01.', 'warning'); return; }
            data.desde = d;
            data.hasta = h;
        } else if (modo === 'departamento') {
            const d = ($("#syncInputDepto").val() || "").trim();
            if (!d) { SAG.toast('Selecciona un departamento.', 'warning'); return; }
            data.departamento = d;
        } else if (modo === 'historico') {
            if (!confirm('Esto BORRARA todos los movimientos del programa y los re-descarga desde 2026-04-01. Continuar?')) return;
            data.limpiar = 1;
            data.tipos_movimiento = '111,112,113';
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sincronizando...');
        SAG.ajax({
            url: '/entregas/sincronizarTrazaragro',
            data: data,
            success: r => {
                $btn.prop('disabled', false).html('<i class="fas fa-rotate me-1"></i> Sincronizar');
                bootstrap.Modal.getInstance(document.getElementById('modalSyncPers'))?.hide();
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            },
            error: () => {
                $btn.prop('disabled', false).html('<i class="fas fa-rotate me-1"></i> Sincronizar');
                SAG.toast('Error en la sincronizacion.', 'error');
            }
        });
    });

    // ── INIT ─────────────────────────────────────────────────
    cargarMovs(1);
    cargarProds(1);
});
