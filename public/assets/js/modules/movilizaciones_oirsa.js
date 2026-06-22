/**
 * movilizaciones_oirsa.js — Reporte consolidado OIRSA Trazaragro
 * Sólo lectura. Lee de sag_trazaragro_movimientos (tipos 111 y 113).
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
function limpiarEstab(s) {
    return String(s || '').replace(/;\s*\d+\s*$/, '').trim() || '—';
}

$(function () {
    // ─────────────────────────────────────────────────────
    //  Tabs
    // ─────────────────────────────────────────────────────
    let dtMov = null;
    let cargados = { 'por-bodega': false, 'por-proveedor': false, 'por-producto': false };

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

    // Inicializar la tabla al cargar (es el tab activo por defecto)
    initTabla();

    // ─────────────────────────────────────────────────────
    //  TABLA principal (DataTables server-side)
    // ─────────────────────────────────────────────────────
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
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
            ajax: {
                url: BASE_URL + '/movilizaciones/listar',
                method: 'GET',
                data: function (d) {
                    d.f_tipo      = $('#mfTipo').val();
                    d.f_proveedor = $('#mfProveedor').val();
                    d.f_bodega    = $('#mfBodega').val();
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
                    render: (id) => (id === 113 || id === '113')
                        ? '<span class="tipo-badge tipo-recep">Recepción</span>'
                        : '<span class="tipo-badge tipo-ent">Entrega</span>',
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
                        const color = row.tipo_movimiento_id == 113 ? '#1e40af' : '#e8742c';
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

        $('#mfTipo, #mfProveedor, #mfBodega, #mfProducto').on('change', () => dtMov.ajax.reload());
        $('#mfDesde, #mfHasta').on('change', () => dtMov.ajax.reload());
        let tBusca;
        $('#mfGuiasa').on('input', function () {
            clearTimeout(tBusca);
            tBusca = setTimeout(() => dtMov.ajax.reload(), 300);
        });
        $('#btnMfLimpiar').on('click', () => {
            $('#mfTipo,#mfProveedor,#mfBodega,#mfProducto,#mfDesde,#mfHasta,#mfGuiasa').val('');
            dtMov.search('').ajax.reload();
        });
    }

    // ─────────────────────────────────────────────────────
    //  Tabs por Bodega / Proveedor / Producto
    // ─────────────────────────────────────────────────────
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
                    $c.html('<div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">Sin datos disponibles. Sincronizá desde el módulo Entregas.</div>');
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
        let html = '<div style="margin-bottom:10px;font-size:.85rem;color:#666;">'
            + '<i class="fas fa-info-circle"></i> <strong>' + nf(rows.length) + ' bodega(s)</strong>. '
            + 'Stock estimado = recibido − entregado.</div>'
            + '<div class="row g-2">';
        rows.forEach(b => {
            const stock = Number(b.stock_estimado || 0);
            html += '<div class="col-12 col-md-6 col-lg-4">'
                + '<div style="background:#fff;border:1.5px solid var(--borde);border-radius:10px;padding:14px;">'
                + '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">'
                + '<i class="fas fa-warehouse" style="color:#d97706;font-size:1.2rem;"></i>'
                + '<div><strong style="font-size:.92rem;">' + escapar(limpiarEstab(b.nombre)) + '</strong>'
                + (b.departamento ? '<div style="font-size:.7rem;color:#888;">' + escapar(b.departamento) + '</div>' : '') + '</div>'
                + '</div>'
                + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;border-top:1px dashed #e5e7eb;padding-top:10px;">'
                + '<div><div style="font-size:.65rem;color:#888;text-transform:uppercase;">Recibido</div><strong style="color:#1e40af;font-size:1.1rem;">+' + nf(Math.round(b.recibido)) + '</strong></div>'
                + '<div><div style="font-size:.65rem;color:#888;text-transform:uppercase;">Entregado</div><strong style="color:#e8742c;font-size:1.1rem;">−' + nf(Math.round(b.entregado)) + '</strong></div>'
                + '<div><div style="font-size:.65rem;color:#888;text-transform:uppercase;"># Recep.</div>' + nf(b.num_recep) + '</div>'
                + '<div><div style="font-size:.65rem;color:#888;text-transform:uppercase;"># Entreg.</div>' + nf(b.num_ent) + '</div>'
                + '</div>'
                + '<div style="margin-top:10px;padding-top:8px;border-top:1px solid #f1f5f9;">'
                + '<div style="font-size:.65rem;color:#888;text-transform:uppercase;">Stock estimado</div>'
                + '<strong style="font-size:1.3rem;color:' + (stock > 0 ? '#16a34a' : stock < 0 ? '#dc2626' : '#888') + ';">' + nf(Math.round(stock)) + '</strong>'
                + '</div>'
                + '</div></div>';
        });
        html += '</div>';
        $c.html(html);
    }

    function renderProveedores(rows, $c) {
        let html = '<div style="margin-bottom:10px;font-size:.85rem;color:#666;">'
            + '<i class="fas fa-info-circle"></i> <strong>' + nf(rows.length) + ' proveedor(es)</strong> con recepciones registradas en OIRSA.</div>'
            + '<div style="background:#fff;border:1.5px solid var(--borde);border-radius:10px;overflow:auto;">'
            + '<table class="mov-tbl" style="min-width:900px;">'
            + '<thead><tr><th>Proveedor</th><th>Departamento</th><th style="text-align:right;">Movimientos</th>'
            + '<th style="text-align:right;">Cantidad</th><th style="text-align:right;">Productos</th>'
            + '<th style="text-align:right;">Bodegas</th><th>Primera entrega</th><th>Última entrega</th></tr></thead><tbody>';
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
            + '<thead><tr><th>Producto / objeto trazable</th><th>Unidad</th>'
            + '<th style="text-align:right;">Recibido</th><th style="text-align:right;">Entregado</th>'
            + '<th style="text-align:right;">Stock estimado</th>'
            + '<th style="text-align:right;"># Recepciones</th><th style="text-align:right;"># Entregas</th></tr></thead><tbody>';
        rows.forEach(p => {
            const stock = Number(p.stock_estimado || 0);
            html += '<tr>'
                + '<td><strong>' + escapar(p.nombre) + '</strong></td>'
                + '<td>' + escapar(p.unidad || '—') + '</td>'
                + '<td style="text-align:right;color:#1e40af;font-weight:700;">+' + nf(Math.round(p.recibido)) + '</td>'
                + '<td style="text-align:right;color:#e8742c;font-weight:700;">−' + nf(Math.round(p.entregado)) + '</td>'
                + '<td style="text-align:right;font-weight:800;color:' + (stock > 0 ? '#16a34a' : stock < 0 ? '#dc2626' : '#888') + ';">' + nf(Math.round(stock)) + '</td>'
                + '<td style="text-align:right;font-size:.85rem;">' + nf(p.num_recepciones) + '</td>'
                + '<td style="text-align:right;font-size:.85rem;">' + nf(p.num_entregas) + '</td>'
                + '</tr>';
        });
        html += '</tbody></table></div>';
        $c.html(html);
    }
});
