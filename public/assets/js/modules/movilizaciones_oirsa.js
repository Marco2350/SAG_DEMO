/**
 * movilizaciones_oirsa.js — Reporte consolidado OIRSA Trazaragro
 * Sólo lectura. Lee de sag_trazaragro_movimientos (tipos 111, 112, 113).
 */

const BASE_URL = (function () {
    const m = document.querySelector('meta[name="base-url"]');
    return m ? m.content : (window.BASE_URL || '');
})();

function escapar(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function nf(n) { return (new Intl.NumberFormat('es-HN')).format(n || 0); }
function fechaCorta(s) { return s ? String(s).substring(0, 10) : ''; }
function limpiarEstab(s) { return String(s || '').replace(/;\s*\d+\s*$/, '').trim() || '—'; }

$(function () {
    let dtMov = null;
    let cargados = { 'por-bodega': false, 'por-proveedor': false, 'por-producto': false };

    $('#btnSyncMovilizaciones').on('click', function () {
        const $btn = $(this);
        const original = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizando...');
        SAG.ajax({
            url: '/entregas/sincronizarTrazaragro',
            method: 'POST',
            // Inventario requiere el historial completo: una salida reciente
            // puede corresponder a una entrada de años anteriores.
            data: { tipos_movimiento: '111,112,113', desde: '2000-01-01', top: 100000 },
            success: function (res) {
                if (!res || !res.success) {
                    SAG.toast((res && res.message) || 'No se pudo sincronizar con OIRSA.', 'error');
                    $btn.prop('disabled', false).html(original);
                    return;
                }
                SAG.toast(res.message, 'success');
                window.setTimeout(() => window.location.reload(), 900);
            },
            error: function () {
                $btn.prop('disabled', false).html(original);
            },
        });
    });

    document.querySelectorAll('.mov-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.mov-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.mov-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            const tab = btn.dataset.tab;
            document.getElementById('mov-tab-' + tab).classList.add('active');

            if (tab === 'movimientos' && !dtMov) initTabla();
            if ((tab === 'por-bodega' || tab === 'por-proveedor' || tab === 'por-producto') && !cargados[tab]) {
                cargados[tab] = true;
                loadResumen(tab.replace('por-', ''));
            }
        });
    });
    initTabla();

    function initTabla() {
        if (dtMov) return;
        dtMov = $('#tblMovilizaciones').DataTable({
            serverSide: true,
            processing: true,
            searching: true,
            ordering: true,
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[15, 25, 50, 100, 200], [15, 25, 50, 100, 200]],
            language: { url: (document.querySelector('meta[name="base-url"]')?.content || '') + '/public/assets/js/datatables/es-MX.json' },
            ajax: {
                url: BASE_URL + '/movilizaciones/listar',
                method: 'GET',
                data: function (d) {
                    d.f_tipo      = $('#mfTipo').val();
                    d.f_proveedor = $('#mfProveedor').val();
                    d.f_bodega    = $('#mfBodega').val();
                    d.f_departamento = $('#mfDepartamento').val();
                    d.f_producto  = $('#mfProducto').val();
                    d.f_desde     = $('#mfDesde').val();
                    d.f_hasta     = $('#mfHasta').val();
                    d.f_guiasa    = $('#mfGuiasa').val();
                },
                error: function () { SAG.toast('Error al cargar movimientos.', 'error'); },
            },
            columns: [
                { data: 'fecha_autorizacion', render: fechaCorta },
                {
                    data: 'tipo_movimiento_id',
                    render: (id) => {
                        if (id == 113) return '<span class="tipo-badge tipo-recep">Recepción</span>';
                        if (id == 112) return '<span class="tipo-badge tipo-tras">Traslado</span>';
                        return '<span class="tipo-badge tipo-ent">Entrega</span>';
                    },
                },
                {
                    data: null,
                    render: (r) => {
                        const e = limpiarEstab(r.origen_establecimiento);
                        const d = r.origen_departamento || '';
                        return '<span class="wrap"><strong>' + escapar(e) + '</strong>'
                            + (d ? '<br><small style="color:#888;">' + escapar(d) + '</small>' : '') + '</span>';
                    },
                },
                {
                    data: null,
                    render: (r) => {
                        if ((r.tipo_movimiento_id == 111) && r.destino_dni) {
                            return '<span class="wrap"><strong>' + escapar(r.destino_nombre || r.destino_persona || '') + '</strong>'
                                + '<br><small style="color:#888;">DNI: ' + escapar(r.destino_dni) + '</small></span>';
                        }
                        const e = limpiarEstab(r.destino_establecimiento);
                        const d = r.destino_departamento || '';
                        const m = r.destino_municipio || '';
                        return '<span class="wrap"><strong>' + escapar(e) + '</strong>'
                            + (d ? '<br><small style="color:#888;">' + escapar(d) + (m ? ' / ' + escapar(m) : '') + '</small>' : '') + '</span>';
                    },
                },
                { data: 'objeto_trazable', render: (d) => '<span class="wrap"><strong>' + escapar(d) + '</strong></span>' },
                { data: 'guiasa_no' },
                {
                    data: 'cantidad',
                    render: (d, type, row) => {
                        const n = nf(Math.round(Number(d || 0)));
                        let color = '#e8742c';
                        if (row.tipo_movimiento_id == 113) color = '#1e40af';
                        else if (row.tipo_movimiento_id == 112) color = '#7c3aed';
                        return '<span style="font-weight:700;color:' + color + ';">' + n + '</span>';
                    },
                },
                { data: 'unidad' },
                {
                    data: 'codigo_trazabilidad',
                    render: (d) => d ? '<strong style="color:#0f766e;">' + escapar(d) + '</strong>'
                                     : '<em style="color:#bbb;">—</em>',
                },
                { data: 'status_oirsa' },
            ],
        });

        $('#mfTipo, #mfProveedor, #mfBodega, #mfDepartamento, #mfProducto').on('change', () => dtMov.ajax.reload());
        $('#mfDesde, #mfHasta').on('change', () => dtMov.ajax.reload());
        let tBusca;
        $('#mfGuiasa').on('input', function () {
            clearTimeout(tBusca);
            tBusca = setTimeout(() => dtMov.ajax.reload(), 300);
        });
        $('#btnMfLimpiar').on('click', () => {
            $('#mfTipo,#mfProveedor,#mfBodega,#mfDepartamento,#mfProducto,#mfDesde,#mfHasta,#mfGuiasa').val('');
            dtMov.search('').ajax.reload();
        });
    }

    function loadResumen(agrupacion) {
        const containerId = agrupacion === 'bodega' ? 'mov-resBodega'
                          : agrupacion === 'proveedor' ? 'mov-resProveedor'
                          : 'mov-resProducto';
        const $c = $('#' + containerId);
        $c.html('<div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
        SAG.ajax({
            url: '/movilizaciones/resumen',
            method: 'GET',
            data: { agrupacion: agrupacion },
            success: function (res) {
                if (!res || !res.data) { $c.html('<div>Error.</div>'); return; }
                if (res.data.length === 0) {
                    $c.html('<div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">Sin datos. Sincronizá desde el módulo Entregas.</div>');
                    return;
                }
                if (agrupacion === 'bodega')         renderBodegas(res.data, $c);
                else if (agrupacion === 'proveedor') renderProveedores(res.data, $c);
                else                                 renderProductos(res.data, $c);
            },
            error: function () { $c.html('<div>Error al cargar resumen.</div>'); },
        });
    }

    function renderBodegas(rows, $c) {
        let html = '<div class="mov-filtros" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">'
            + '<div><div class="fl">Buscar bodega o producto</div>'
            + '<input type="text" class="fc" id="bodBusca" placeholder="Texto libre..."/></div>'
            + '<div><div class="fl">Mostrar productos</div>'
            + '<select class="fc" id="bodMostrarProd">'
            + '<option value="collapse" selected>Colapsados (clic para expandir)</option>'
            + '<option value="expand">Expandidos por defecto</option>'
            + '<option value="hide">No mostrar</option>'
            + '</select></div>'
            + '<div style="display:flex;align-items:flex-end;">'
            + '<button class="fc" id="bodLimpiar" style="background:#eef0f7;cursor:pointer;font-weight:700;">'
            + '<i class="fas fa-broom"></i> Limpiar</button>'
            + '</div></div>';

        html += '<div style="margin-bottom:10px;font-size:.85rem;color:#666;">'
            + '<i class="fas fa-info-circle"></i> <strong id="bodCount">' + nf(rows.length) + ' bodega(s)</strong>. '
            + 'Stock estimado = recibido − entregado (incluye traslados). Clic en la lista para expandir.</div>'
            + '<div class="row g-2" id="bodCards">';

        rows.forEach(b => {
            const stock      = Number(b.stock_estimado || 0);
            const productos  = Array.isArray(b.productos) ? b.productos : [];
            const totalProds = b.productos_total || productos.length;
            const searchHay  = (limpiarEstab(b.nombre) + ' ' + (b.departamento || '') + ' '
                + productos.map(p => p.producto).join(' ')).toLowerCase();

            html += '<div class="col-12 col-md-6 col-lg-4 bod-card" data-search="' + escapar(searchHay) + '">'
                + '<div style="background:#fff;border:1.5px solid var(--borde);border-radius:10px;padding:14px;height:100%;display:flex;flex-direction:column;">'
                + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">'
                + '<i class="fas fa-warehouse" style="color:#d97706;font-size:1.2rem;"></i>'
                + '<div style="flex:1;min-width:0;">'
                + '<strong style="font-size:.92rem;display:block;">' + escapar(limpiarEstab(b.nombre)) + '</strong>'
                + (b.departamento ? '<div style="font-size:.7rem;color:#888;">' + escapar(b.departamento) + '</div>' : '')
                + (b.cue ? '<div style="font-size:.65rem;color:#94a3b8;">CUE: ' + escapar(b.cue) + '</div>' : '')
                + '</div></div>'
                + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;border-top:1px dashed #e5e7eb;padding-top:10px;">'
                + '<div><div style="font-size:.65rem;color:#888;text-transform:uppercase;">Recibido</div><strong style="color:#1e40af;font-size:1.1rem;">+' + nf(Math.round(b.recibido)) + '</strong></div>'
                + '<div><div style="font-size:.65rem;color:#888;text-transform:uppercase;">Entregado</div><strong style="color:#e8742c;font-size:1.1rem;">-' + nf(Math.round(b.entregado)) + '</strong></div>'
                + '<div><div style="font-size:.65rem;color:#888;text-transform:uppercase;"># Entradas</div>' + nf(b.num_recep) + '</div>'
                + '<div><div style="font-size:.65rem;color:#888;text-transform:uppercase;"># Salidas</div>' + nf(b.num_ent) + '</div>'
                + '</div>'
                + '<div style="margin-top:10px;padding-top:8px;border-top:1px solid #f1f5f9;">'
                + '<div style="font-size:.65rem;color:#888;text-transform:uppercase;">Stock estimado</div>'
                + '<strong style="font-size:1.3rem;color:' + (stock > 0 ? '#16a34a' : stock < 0 ? '#dc2626' : '#888') + ';">' + nf(Math.round(stock)) + '</strong>'
                + '</div>';

            if (totalProds > 0) {
                html += '<div class="bod-productos-wrap" style="margin-top:10px;border-top:1px dashed #e5e7eb;padding-top:8px;flex:1;">'
                    + '<div class="bod-prod-toggle" style="cursor:pointer;font-size:.72rem;font-weight:700;color:#0d9488;text-transform:uppercase;display:flex;align-items:center;gap:6px;user-select:none;">'
                    + '<i class="fas fa-chevron-down bod-chev"></i>'
                    + '<span><i class="fas fa-boxes-stacked" style="margin-right:4px;"></i>'
                    + totalProds + ' producto(s) registrado(s)</span>'
                    + '</div>'
                    + '<div class="bod-prod-lista" style="display:none;margin-top:8px;">';

                productos.forEach(p => {
                    const ps = Number(p.stock_estimado || 0);
                    const sc = ps > 0 ? '#16a34a' : ps < 0 ? '#dc2626' : '#888';
                    html += '<div style="font-size:.72rem;padding:5px 0;border-bottom:1px dotted #f1f5f9;">'
                        + '<div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">'
                        + '<span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#374151;font-weight:600;" title="' + escapar(p.producto) + '">'
                        + escapar(p.producto) + '</span>'
                        + '<strong style="color:' + sc + ';flex-shrink:0;font-size:.8rem;">' + nf(Math.round(ps)) + '</strong>'
                        + '</div>'
                        + '<div style="display:flex;gap:8px;color:#888;font-size:.65rem;margin-top:2px;">'
                        + '<span style="color:#1e40af;">+' + nf(Math.round(p.recibido)) + '</span>'
                        + '<span style="color:#e8742c;">-' + nf(Math.round(p.entregado)) + '</span>'
                        + (p.unidad ? '<span style="margin-left:auto;">' + escapar(p.unidad) + '</span>' : '')
                        + '</div>'
                        + '</div>';
                });
                html += '</div></div>';
            }

            html += '</div></div>';
        });
        html += '</div>';
        $c.html(html);

        $('#bodBusca').on('input', function () {
            const q = (this.value || '').toLowerCase().trim();
            let vis = 0;
            $('#bodCards .bod-card').each(function () {
                const hay = $(this).data('search') || '';
                const m = q === '' || String(hay).indexOf(q) !== -1;
                $(this).toggle(m);
                if (m) vis++;
            });
            $('#bodCount').text(nf(vis) + ' bodega(s)');
        });
        $('#bodLimpiar').on('click', () => {
            $('#bodBusca').val('').trigger('input');
            $('#bodMostrarProd').val('collapse').trigger('change');
        });
        $('#bodMostrarProd').on('change', function () {
            const m = this.value;
            if (m === 'hide') {
                $('.bod-productos-wrap').hide();
            } else if (m === 'expand') {
                $('.bod-productos-wrap').show();
                $('.bod-prod-lista').show();
                $('.bod-chev').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            } else {
                $('.bod-productos-wrap').show();
                $('.bod-prod-lista').hide();
                $('.bod-chev').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            }
        });
        $('#bodCards').on('click', '.bod-prod-toggle', function () {
            const $lista = $(this).next('.bod-prod-lista');
            const $chev  = $(this).find('.bod-chev');
            $lista.slideToggle(150);
            $chev.toggleClass('fa-chevron-down fa-chevron-up');
        });
    }

    function renderProveedores(rows, $c) {
        let html = '<div style="margin-bottom:10px;font-size:.85rem;color:#666;">'
            + '<i class="fas fa-info-circle"></i> <strong>' + nf(rows.length) + ' proveedor(es)</strong> con recepciones registradas en OIRSA.</div>'
            + '<div style="background:#fff;border:1.5px solid var(--borde);border-radius:10px;overflow:auto;">'
            + '<table class="mov-tbl" style="min-width:900px;">'
            + '<thead><tr><th>Proveedor</th><th>Departamento</th><th style="text-align:right;">Movimientos</th>'
            + '<th style="text-align:right;">Cantidad</th><th style="text-align:right;">Productos</th>'
            + '<th style="text-align:right;">Bodegas</th><th>Primera</th><th>Última</th></tr></thead><tbody>';
        rows.forEach(p => {
            html += '<tr>'
                + '<td><strong>' + escapar(limpiarEstab(p.nombre)) + '</strong></td>'
                + '<td>' + escapar(p.departamento || '—') + '</td>'
                + '<td style="text-align:right;font-weight:700;color:#1e40af;">' + nf(p.movimientos) + '</td>'
                + '<td style="text-align:right;font-weight:700;color:#16a34a;">+' + nf(Math.round(p.cantidad)) + '</td>'
                + '<td style="text-align:right;">' + nf(p.productos) + '</td>'
                + '<td style="text-align:right;">' + nf(p.bodegas_entregadas) + '</td>'
                + '<td style="font-size:.75rem;color:#888;">' + escapar(fechaCorta(p.primera)) + '</td>'
                + '<td style="font-size:.75rem;color:#888;">' + escapar(fechaCorta(p.ultima)) + '</td>'
                + '</tr>';
        });
        html += '</tbody></table></div>';
        $c.html(html);
    }

    function renderProductos(rows, $c) {
        let html = '<div style="margin-bottom:10px;font-size:.85rem;color:#666;">'
            + '<i class="fas fa-info-circle"></i> <strong>' + nf(rows.length) + ' producto(s)</strong>. '
            + 'Stock estimado = recibido − entregado.</div>'
            + '<div style="background:#fff;border:1.5px solid var(--borde);border-radius:10px;overflow:auto;">'
            + '<table class="mov-tbl" style="min-width:900px;">'
            + '<thead><tr><th>Producto</th><th>Unidad</th>'
            + '<th style="text-align:right;">Recibido</th><th style="text-align:right;">Entregado</th>'
            + '<th style="text-align:right;">Stock estimado</th>'
            + '<th style="text-align:right;"># Recep.</th><th style="text-align:right;"># Entreg.</th></tr></thead><tbody>';
        rows.forEach(p => {
            const stock = Number(p.stock_estimado || 0);
            html += '<tr>'
                + '<td><strong>' + escapar(p.nombre) + '</strong></td>'
                + '<td>' + escapar(p.unidad || '—') + '</td>'
                + '<td style="text-align:right;color:#1e40af;font-weight:700;">+' + nf(Math.round(p.recibido)) + '</td>'
                + '<td style="text-align:right;color:#e8742c;font-weight:700;">-' + nf(Math.round(p.entregado)) + '</td>'
                + '<td style="text-align:right;font-weight:800;color:' + (stock > 0 ? '#16a34a' : stock < 0 ? '#dc2626' : '#888') + ';">' + nf(Math.round(stock)) + '</td>'
                + '<td style="text-align:right;font-size:.85rem;">' + nf(p.num_recepciones) + '</td>'
                + '<td style="text-align:right;font-size:.85rem;">' + nf(p.num_entregas) + '</td>'
                + '</tr>';
        });
        html += '</tbody></table></div>';
        $c.html(html);
    }

    // ─────────────────────────────────────────────────────
    //  TAB AUDITORÍA — inconsistencias detectadas
    // ─────────────────────────────────────────────────────
    let auditoriaLoaded = false;
    document.querySelectorAll('.mov-tab[data-tab="auditoria"]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (auditoriaLoaded) return;
            auditoriaLoaded = true;
            loadAuditoria();
        });
    });

    function loadAuditoria() {
        const $c = $('#mov-auditoria');
        $c.html('<div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;"><i class="fas fa-spinner fa-spin"></i> Analizando datos...</div>');
        SAG.ajax({
            url: '/movilizaciones/auditoria',
            method: 'GET',
            success: function (res) {
                if (!res) { $c.html('<div>Error.</div>'); return; }
                renderAuditoria(res);
            },
            error: function () { $c.html('<div>Error al cargar auditoría.</div>'); },
        });
    }

    function renderAuditoria(r) {
        const A = r.bodegas_entregan_sin_recibir || [];
        const B = r.bodegas_reciben_sin_entregar || [];
        const C = r.productos_stock_negativo     || [];
        const D = r.posibles_duplicados_bodega   || [];

        let html = '';

        // ── KPIs de auditoría ──
        html += '<div class="row g-2 mb-3">'
            + kpiCard('Bodegas que entregan sin recibir', A.length, '#dc2626', 'fa-warehouse')
            + kpiCard('Bodegas con stock estancado',      B.length, '#d97706', 'fa-clock')
            + kpiCard('Productos con stock negativo',     C.length, '#9a3412', 'fa-arrow-trend-down')
            + kpiCard('Bodegas duplicadas',               D.length, '#7c3aed', 'fa-clone')
            + '</div>';

        // ── 1. Bodegas que entregan sin recibir ──
        html += seccion(
            'Bodegas con entregas sin recepciones',
            'Estas bodegas tienen movimientos tipo 111 (Bodega → Productor) pero <strong>nunca</strong> aparecen como destino en recepciones (113) ni traslados (112). Significa que entregaron algo que OIRSA no registra haber recibido.',
            A,
            ['Bodega', 'Departamento', 'Entregas', 'Cantidad entregada', 'Primera', 'Última'],
            (row) => [
                escapar(limpiarEstab(row.bodega)),
                escapar(row.departamento || '—'),
                nf(row.num_entregas),
                '<strong style="color:#dc2626;">' + nf(Math.round(row.cantidad_entregada)) + '</strong>',
                escapar(fechaCorta(row.primera)),
                escapar(fechaCorta(row.ultima)),
            ],
            '#dc2626'
        );

        // ── 2. Bodegas que reciben sin entregar ──
        html += seccion(
            'Bodegas con stock estancado',
            'Estas bodegas tienen recepciones (113) pero <strong>nunca</strong> aparecen como origen en entregas (111) ni traslados (112). El insumo entró pero no salió. Puede ser stock real o registros incompletos.',
            B,
            ['Bodega', 'Departamento', 'Recepciones', 'Cantidad recibida', 'Primera', 'Última'],
            (row) => [
                escapar(limpiarEstab(row.bodega)),
                escapar(row.departamento || '—'),
                nf(row.num_recepciones),
                '<strong style="color:#d97706;">+' + nf(Math.round(row.cantidad_recibida)) + '</strong>',
                escapar(fechaCorta(row.primera)),
                escapar(fechaCorta(row.ultima)),
            ],
            '#d97706'
        );

        // ── 3. Productos con stock negativo ──
        html += seccion(
            'Productos con stock teórico negativo',
            'Sumando todo el programa, estos productos tienen <strong>entregado mayor que recibido</strong>. Generalmente indica que faltan recepciones por registrar en OIRSA (no se está duplicando entregas).',
            C,
            ['Producto', 'Unidad', 'Recibido', 'Entregado', 'Stock'],
            (row) => [
                '<strong>' + escapar(row.producto) + '</strong>',
                escapar(row.unidad || '—'),
                '<span style="color:#1e40af;">+' + nf(Math.round(row.recibido)) + '</span>',
                '<span style="color:#e8742c;">-' + nf(Math.round(row.entregado)) + '</span>',
                '<strong style="color:#dc2626;">' + nf(Math.round(row.stock)) + '</strong>',
            ],
            '#9a3412'
        );

        // ── 4. Bodegas duplicadas ──
        html += seccion(
            'Posibles bodegas duplicadas',
            'El mismo nombre de bodega aparece con <strong>varios CUE distintos</strong>. Probablemente OIRSA registró la misma bodega dos veces. Si las consolidás, los reportes Por Bodega serán más precisos.',
            D,
            ['Nombre base', 'Variantes', 'Detalle'],
            (row) => [
                '<strong>' + escapar(row.nombre_corto) + '</strong>',
                '<span style="background:#ede9fe;color:#7c3aed;padding:2px 8px;border-radius:20px;font-weight:700;">' + nf(row.variantes) + '</span>',
                '<small style="color:#666;">' + escapar(row.lista) + '</small>',
            ],
            '#7c3aed'
        );

        $c.html(html);
    }

    function kpiCard(label, val, color, icon) {
        return '<div class="col-6 col-md-3">'
            + '<div class="mov-kpi">'
            + '<div class="ki" style="background:' + color + '20;color:' + color + ';"><i class="fas ' + icon + '"></i></div>'
            + '<div><div class="kv" style="color:' + color + ';">' + nf(val) + '</div><div class="kl">' + escapar(label) + '</div></div>'
            + '</div></div>';
    }

    function seccion(titulo, descripcion, rows, cabeceras, mapFila, color) {
        if (!rows || rows.length === 0) {
            return '<div class="card-box" style="margin-bottom:14px;">'
                + '<div class="card-box-header"><h6 style="color:' + color + ';"><i class="fas fa-circle-check"></i> ' + escapar(titulo) + '</h6></div>'
                + '<div style="padding:18px;color:#16a34a;font-size:.85rem;text-align:center;">'
                + '<i class="fas fa-circle-check"></i> Todo en orden — no se detectaron casos.</div></div>';
        }
        let html = '<div class="card-box" style="margin-bottom:14px;border-left:4px solid ' + color + ';">'
            + '<div class="card-box-header"><h6 style="color:' + color + ';"><i class="fas fa-triangle-exclamation"></i> ' + escapar(titulo)
            + ' <span style="background:' + color + ';color:#fff;padding:1px 8px;border-radius:20px;font-size:.7rem;margin-left:6px;">' + nf(rows.length) + '</span></h6></div>'
            + '<div style="padding:10px 16px;font-size:.8rem;color:#555;background:#fafafa;border-bottom:1px solid var(--borde-suave);">' + descripcion + '</div>'
            + '<div style="overflow:auto;">'
            + '<table class="mov-tbl" style="min-width:700px;"><thead><tr>';
        cabeceras.forEach(c => { html += '<th>' + escapar(c) + '</th>'; });
        html += '</tr></thead><tbody>';
        rows.forEach(row => {
            html += '<tr>';
            mapFila(row).forEach(celda => { html += '<td>' + celda + '</td>'; });
            html += '</tr>';
        });
        html += '</tbody></table></div></div>';
        return html;
    }
});
