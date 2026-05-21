/**
 * presupuesto.js — Módulo de Ejecución Presupuestaria
 * SAG Programas
 */
$(function () {
    'use strict';

    const P      = SAG_PRES;       // datos PHP
    const perms  = P.perms;        // {esAdmin, esJefe, rol}
    const BASE   = SAG.BASE_URL;

    // ─── HELPERS ──────────────────────────────────────────────
    function fmt(n) { return 'L. ' + parseFloat(n||0).toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function fmtM(n, mon) { return (mon==='USD'?'$ ':'L. ') + parseFloat(n||0).toLocaleString('es-HN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function badgeEstado(e) {
        const map = {
            borrador:'bp-borrador',pendiente:'bp-pendiente',activo:'bp-activo',
            activa:'bp-activo',cerrado:'bp-cerrado',solicitada:'bp-solicitada',
            cotizando:'bp-visto',aprobada:'bp-aprobada',ejecutada:'bp-ejecutada',
            anulada:'bp-anulada',visto_bueno:'bp-visto',rechazada:'bp-rechazada',
            liquidada:'bp-liquidada',registrado:'bp-pendiente',aprobado:'bp-aprobada'
        };
        const label = {
            borrador:'Borrador',pendiente:'Pendiente',activo:'Activo',activa:'Activa',
            cerrado:'Cerrado',solicitada:'Solicitada',cotizando:'En Cotización',
            aprobada:'Aprobada',ejecutada:'Ejecutada',anulada:'Anulada',
            visto_bueno:'Con Visto Bueno',rechazada:'Rechazada',liquidada:'Liquidada',
            registrado:'Registrado',aprobado:'Aprobado'
        };
        return `<span class="badge-pres ${map[e]||'bp-borrador'}">${label[e]||e}</span>`;
    }
    function fileLink(path, label) {
        if (!path) return '<span style="color:#ccc;font-size:.75rem;">—</span>';
        return `<a href="${BASE}/presupuesto/documentos/ver?id=__DOC__" target="_blank"
                   style="font-size:.75rem;color:var(--primario);"
                   onclick="event.stopPropagation()">
                   <i class="fas fa-file-pdf"></i> ${label||'Ver'}
                </a>`;
    }

    // ─── MODALES ──────────────────────────────────────────────
    window.cerrarModal = function(id) {
        document.getElementById(id).classList.remove('show');
    };
    function abrirModal(id) {
        document.getElementById(id).classList.add('show');
    }
    // Cerrar al hacer clic fuera
    document.querySelectorAll('.modal-overlay').forEach(mo => {
        mo.addEventListener('click', e => { if(e.target===mo) mo.classList.remove('show'); });
    });

    // ─── TABS ─────────────────────────────────────────────────
    document.querySelectorAll('.mode-tab[data-tab]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.mode-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const panel = document.getElementById('tab' + this.dataset.tab);
            if (panel) panel.classList.add('active');

            // Cargar datos al cambiar de tab
            const tab = this.dataset.tab;
            if (tab === 'Compras')    cargarCompras();
            if (tab === 'Viaticos')   cargarViaticos();
            if (tab === 'Gastos')     cargarGastos();
            if (tab === 'Documentos') cargarDocumentos();
            if (tab === 'Presupuesto' && P.presupuesto) renderLineas(P.lineas);
        });
    });

    // ─── LÍNEAS PRESUPUESTARIAS ───────────────────────────────
    function renderLineas(lineas) {
        const cont = document.getElementById('lineasRender');
        if (!cont) return;
        if (!lineas || lineas.length === 0) {
            cont.innerHTML = '<div style="text-align:center;padding:30px;color:#aaa;"><i class="fas fa-inbox fa-2x" style="display:block;margin-bottom:8px;"></i>No hay líneas presupuestarias aún.</div>';
            return;
        }
        cont.innerHTML = lineas.map(l => {
            const pctEj  = l.monto_aprobado > 0 ? Math.min(100, (l.ejecutado/l.monto_aprobado*100).toFixed(1)) : 0;
            const pctCom = l.monto_aprobado > 0 ? Math.min(100 - pctEj, (l.comprometido_total/l.monto_aprobado*100).toFixed(1)) : 0;
            const editBtn = perms.esAdmin
                ? `<button class="btn-sm-icon btn-edit-linea ms-1" data-id="${l.id_linea}" title="Editar"><i class="fas fa-pen"></i></button>` : '';
            return `
            <div class="linea-row">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <div>
                  ${l.codigo ? `<span class="linea-codigo">${l.codigo}</span>` : ''}
                  <span class="linea-nombre">${l.nombre}</span>
                  <span class="linea-tipo ms-2">${l.tipo}</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                  <span style="font-size:.8rem;font-weight:700;">${fmt(l.monto_aprobado)}</span>
                  ${editBtn}
                </div>
              </div>
              <div class="prog-budget">
                <div style="display:flex;height:8px;border-radius:4px;overflow:hidden;">
                  <div class="prog-ejecutado" style="width:${pctEj}%;"></div>
                  <div class="prog-comprometido" style="width:${pctCom}%;"></div>
                </div>
              </div>
              <div class="montos-row">
                <div class="monto-item"><strong>${fmt(l.ejecutado)}</strong><br>Ejecutado (${pctEj}%)</div>
                <div class="monto-item" style="color:#d97706;"><strong>${fmt(l.comprometido_total)}</strong><br>Comprometido</div>
                <div class="monto-item" style="color:#8b5cf6;"><strong>${fmt(l.saldo)}</strong><br>Saldo</div>
              </div>
            </div>`;
        }).join('');
    }
    if (P.lineas && P.lineas.length) renderLineas(P.lineas);

    // Cargar líneas al cambiar presupuesto
    $('#selectPresupuesto').on('change', function () {
        const pid = this.value;
        if (!pid) return;
        SAG.ajax({ url:'/presupuesto/lineas/listar', data:{id_presupuesto:pid}, success: res => {
            renderLineas(res.data);
        }});
    });

    // ─── EDITAR LÍNEA ─────────────────────────────────────────
    $(document).on('click', '.btn-edit-linea', function () {
        const id = $(this).data('id');
        const l  = P.lineas.find(x => x.id_linea == id);
        if (!l) return;
        $('#tituloLinea').html('<i class="fas fa-pen me-2"></i>Editar Línea Presupuestaria');
        $('#fLineaId').val(l.id_linea);
        $('#fLineaPresId').val(l.id_presupuesto);
        $('#fLineaCodigo').val(l.codigo);
        $('#fLineaNombre').val(l.nombre);
        $('#fLineaTipo').val(l.tipo);
        $('#fLineaMonto').val(l.monto_aprobado);
        $('#fLineaOrden').val(l.orden);
        $('#fLineaDesc').val(l.descripcion);
        $('#rowJustAjuste').show();
        abrirModal('modalLinea');
    });

    // ─── NUEVA LÍNEA ──────────────────────────────────────────
    $('#btnNuevaLinea').on('click', function () {
        $('#tituloLinea').html('<i class="fas fa-plus me-2"></i>Nueva Línea Presupuestaria');
        document.getElementById('formLinea').reset();
        $('#fLineaId').val(0);
        const pid = P.presupuesto ? P.presupuesto.id_presupuesto : $('#selectPresupuesto').val();
        $('#fLineaPresId').val(pid);
        $('#rowJustAjuste').hide();
        abrirModal('modalLinea');
    });

    $('#btnGuardarLinea').on('click', function () {
        const nombre = $('#fLineaNombre').val().trim();
        const monto  = parseFloat($('#fLineaMonto').val());
        if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        if (isNaN(monto) || monto < 0) { SAG.toast('Ingrese un monto válido.', 'warning'); return; }
        SAG.btnLoading('#btnGuardarLinea', true);
        SAG.ajax({ url:'/presupuesto/lineas/save', data:$('#formLinea').serialize(),
            success: res => {
                SAG.btnLoading('#btnGuardarLinea', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalLinea');
                setTimeout(() => location.reload(), 600);
            },
            error: () => SAG.btnLoading('#btnGuardarLinea', false),
        });
    });

    // ─── PRESUPUESTO CRUD ─────────────────────────────────────
    $('#btnNuevoPresupuesto').on('click', function () {
        $('#tituloPresupuesto').html('<i class="fas fa-plus me-2"></i>Nuevo Presupuesto');
        document.getElementById('formPresupuesto').reset();
        $('#fPresId').val(0);
        $('#rowTipoCambio').hide();
        abrirModal('modalPresupuesto');
    });

    $('#fPresMoneda').on('change', function () {
        $('#rowTipoCambio').toggle(this.value === 'USD');
    });

    $('#btnGuardarPresupuesto').on('click', function () {
        const nombre = $('#fPresNombre').val().trim();
        if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        SAG.btnLoading('#btnGuardarPresupuesto', true);
        const fd = new FormData(document.getElementById('formPresupuesto'));
        $.ajax({
            url: BASE + '/presupuesto/save', type: 'POST', data: fd,
            processData: false, contentType: false,
            success: res => {
                SAG.btnLoading('#btnGuardarPresupuesto', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalPresupuesto');
                setTimeout(() => location.reload(), 600);
            },
            error: () => SAG.btnLoading('#btnGuardarPresupuesto', false),
        });
    });

    // ─── EDITAR PRESUPUESTO EXISTENTE ────────────────────────
    $(document).on('click', '.btn-edit-presupuesto', function () {
        const id = $(this).data('id');
        const p  = P.presupuestos.find(x => x.id_presupuesto == id);
        if (!p) return;
        $('#tituloPresupuesto').html('<i class="fas fa-pen me-2"></i>Editar Presupuesto');
        $('#fPresId').val(p.id_presupuesto);
        $('#fPresNombre').val(p.nombre);
        $('#fPresAnio').val(p.anio);
        $('#fPresDesc').val(p.descripcion || '');
        $('#fPresMoneda').val(p.moneda);
        $('#fPresEstado').val(p.estado);
        $('#fPresObs').val(p.observaciones || '');
        if (p.tipo_cambio) $('#fPresTipoCambio').val(p.tipo_cambio);
        $('#rowTipoCambio').toggle(p.moneda === 'USD');
        // Mostrar doc actual si existe
        if (p.doc_respaldo) {
            $('#docRespaldoActual').html('<i class="fas fa-file-pdf me-1" style="color:#dc2626;"></i>Archivo actual: ' + p.doc_respaldo + ' <em style="color:#aaa;">(suba uno nuevo para reemplazar)</em>');
        } else {
            $('#docRespaldoActual').html('');
        }
        abrirModal('modalPresupuesto');
    });

    // ─── ACTIVAR PRESUPUESTO (desde la lista) ────────────────
    $(document).on('click', '.btn-activar-presupuesto', function () {
        const id     = $(this).data('id');
        const nombre = $(this).data('nombre');
        SAG.confirm(
            '¿Activar el presupuesto "' + nombre + '"?\n\nEsto cerrará cualquier otro presupuesto activo.',
            function () {
                // Buscamos el presupuesto para obtener sus datos
                const p = P.presupuestos.find(x => x.id_presupuesto == id);
                if (!p) return;
                const fd = new FormData();
                fd.append('id_presupuesto', p.id_presupuesto);
                fd.append('nombre', p.nombre);
                fd.append('anio', p.anio);
                fd.append('moneda', p.moneda || 'HNL');
                fd.append('estado', 'activo');
                fd.append('descripcion', p.descripcion || '');
                fd.append('observaciones', p.observaciones || '');
                if (p.tipo_cambio) fd.append('tipo_cambio', p.tipo_cambio);
                $.ajax({
                    url: BASE + '/presupuesto/save', type: 'POST', data: fd,
                    processData: false, contentType: false,
                    success: res => {
                        if (!res.success) { SAG.toast(res.message, 'error'); return; }
                        SAG.toast(res.message || 'Presupuesto activado correctamente.');
                        setTimeout(() => location.reload(), 600);
                    },
                    error: () => SAG.toast('Error de conexión.', 'error'),
                });
            }
        );
    });

    // ─── VER / EDITAR LÍNEAS DE UN PRESUPUESTO ───────────────
    $(document).on('click', '.btn-ver-lineas', function () {
        const id     = $(this).data('id');
        const nombre = $(this).data('nombre');
        const $sec   = $('#seccionLineas');
        const $titulo = $('#tituloLineasSec');

        $titulo.text('Líneas — ' + nombre);
        $sec.show();

        // Scroll suave hacia la sección
        $sec[0].scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Actualizar el campo oculto de la línea para nuevas líneas
        $('#fLineaPresId').val(id);

        // Cargar líneas de este presupuesto
        const $render = $('#lineasRender');
        $render.html('<div style="text-align:center;padding:20px;color:#aaa;"><i class="fas fa-spinner fa-spin me-2"></i>Cargando líneas…</div>');
        SAG.ajax({
            url: '/presupuesto/lineas/listar',
            data: { id_presupuesto: id },
            success: res => {
                // Actualizar P.lineas localmente para que los botones de editar funcionen
                P.lineas = res.data || [];
                renderLineas(P.lineas);
            },
            error: () => $render.html('<div style="color:#dc2626;padding:20px;">Error al cargar líneas.</div>'),
        });
    });

    // Autorizar presupuesto
    $('#btnAutorizarPres').on('click', function () {
        if (!P.presupuesto) return;
        $('#fAutorizarId').val(P.presupuesto.id_presupuesto);
        abrirModal('modalAutorizar');
    });

    $('#btnConfirmarAutorizar').on('click', function () {
        const fd = new FormData(document.getElementById('formAutorizar'));
        if (!fd.get('doc_autorizacion') || !fd.get('doc_autorizacion').size) {
            SAG.toast('El documento de autorización es obligatorio.', 'warning'); return;
        }
        SAG.btnLoading('#btnConfirmarAutorizar', true);
        $.ajax({
            url: BASE + '/presupuesto/autorizar', type: 'POST', data: fd,
            processData: false, contentType: false,
            success: res => {
                SAG.btnLoading('#btnConfirmarAutorizar', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalAutorizar');
                setTimeout(() => location.reload(), 800);
            },
            error: () => SAG.btnLoading('#btnConfirmarAutorizar', false),
        });
    });

    // ─── HELPER: cargar líneas en selects de modales ──────────
    function cargarLineasEnSelect(selector) {
        SAG.ajax({ url: '/presupuesto/api/lineas', data: {}, success: rows => {
            const opts = '<option value="">— Sin asignar —</option>' +
                rows.map(r => `<option value="${r.id_linea}">${r.label} (${fmt(r.monto_aprobado)})</option>`).join('');
            document.querySelectorAll(selector).forEach(s => s.innerHTML = opts);
        }});
    }

    // ─── COMPRAS ──────────────────────────────────────────────
    function cargarCompras() {
        const estado = $('#filtroEstadoCompra').val();
        SAG.ajax({ url:'/presupuesto/compras/listar', data:{estado}, success: res => {
            const rows = res.data || [];
            if (!rows.length) {
                $('#tbodyCompras').html('<tr><td colspan="10" style="text-align:center;padding:20px;color:#aaa;">Sin registros.</td></tr>');
                return;
            }
            const html = rows.map((c,i) => {
                const acciones = [
                    perms.esAdmin || c.id_usuario_solicita == window.__UID
                        ? `<button class="btn-sm-icon btn-edit-compra" data-id="${c.id_compra}" title="Editar"><i class="fas fa-pen"></i></button>` : '',
                    perms.esJefe
                        ? `<button class="btn-sm-icon ms-1 btn-estado-compra" data-id="${c.id_compra}" title="Cambiar estado"><i class="fas fa-arrows-rotate"></i></button>` : '',
                    c.archivo_solicitud
                        ? `<a class="btn-sm-icon ms-1" href="${BASE}/presupuesto/documentos/ver?id=${c.id_compra}" target="_blank" title="Ver archivo"><i class="fas fa-file"></i></a>` : '',
                ].join('');
                return `<tr>
                    <td>${i+1}</td>
                    <td><strong>${c.numero_solicitud||'—'}</strong></td>
                    <td style="max-width:200px;white-space:pre-wrap;">${c.descripcion}</td>
                    <td>${c.linea_nombre||'—'}</td>
                    <td>${fmtM(c.monto_estimado,c.moneda)}</td>
                    <td>${c.proveedor||'—'}</td>
                    <td>${c.solicitante||'—'}</td>
                    <td>${c.fecha_solicitud||'—'}</td>
                    <td>${badgeEstado(c.estado)}</td>
                    <td>${acciones}</td>
                </tr>`;
            }).join('');
            $('#tbodyCompras').html(html);
        }});
    }

    $('#btnFiltrarCompras').on('click', cargarCompras);

    $('#btnNuevaCompra').on('click', function () {
        $('#tituloCompra').html('<i class="fas fa-plus me-2"></i>Nueva Solicitud de Compra');
        document.getElementById('formCompra').reset();
        $('#fCompraId').val(0);
        $('#fCompraFecha').val(new Date().toISOString().split('T')[0]);
        cargarLineasEnSelect('#fCompraLinea');
        abrirModal('modalCompra');
    });

    $('#btnGuardarCompra').on('click', function () {
        if (!$('#fCompraDesc').val().trim()) { SAG.toast('La descripción es obligatoria.', 'warning'); return; }
        SAG.btnLoading('#btnGuardarCompra', true);
        const fd = new FormData(document.getElementById('formCompra'));
        $.ajax({
            url: BASE + '/presupuesto/compras/save', type:'POST', data: fd,
            processData: false, contentType: false,
            success: res => {
                SAG.btnLoading('#btnGuardarCompra', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalCompra');
                cargarCompras();
            },
            error: () => SAG.btnLoading('#btnGuardarCompra', false),
        });
    });

    $(document).on('click', '.btn-estado-compra', function () {
        $('#fECompraId').val($(this).data('id'));
        $('#fECompraObs').val('');
        $('#fECompraEstado').val('solicitada');
        $('#rowMontoAdj').hide();
        abrirModal('modalEstadoCompra');
    });

    $('#fECompraEstado').on('change', function () {
        $('#rowMontoAdj').toggle(this.value === 'ejecutada');
    });

    $('#btnConfirmarEstadoCompra').on('click', function () {
        const id     = $('#fECompraId').val();
        const estado = $('#fECompraEstado').val();
        SAG.ajax({
            url: '/presupuesto/compras/estado',
            data: { id, estado, observacion_estado: $('#fECompraObs').val(), monto_adjudicado: $('#fECompraMonto').val() },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalEstadoCompra');
                cargarCompras();
            },
        });
    });

    // ─── VIÁTICOS ─────────────────────────────────────────────
    function cargarViaticos() {
        const estado = $('#filtroEstadoViatico').val();
        SAG.ajax({ url:'/presupuesto/viaticos/listar', data:{estado}, success: res => {
            const rows = res.data || [];
            if (!rows.length) {
                $('#tbodyViaticos').html('<tr><td colspan="9" style="text-align:center;padding:20px;color:#aaa;">Sin solicitudes.</td></tr>');
                return;
            }
            const html = rows.map((v,i) => {
                let acciones = '';
                if (v.estado === 'pendiente') {
                    if (perms.esJefe)
                        acciones += `<button class="btn-approve" style="font-size:.7rem;padding:3px 9px;" data-id="${v.id_solicitud}" data-dest="${v.destino}" data-monto="${v.monto_solicitado}">Visto Bueno</button> `;
                    if (perms.esAdmin)
                        acciones += `<button class="btn-approve btn-aprobar" style="font-size:.7rem;padding:3px 9px;background:var(--primario);" data-id="${v.id_solicitud}" data-dest="${v.destino}">Aprobar</button> `;
                }
                if (v.estado === 'visto_bueno' && perms.esAdmin) {
                    acciones += `<button class="btn-approve btn-aprobar" style="font-size:.7rem;padding:3px 9px;" data-id="${v.id_solicitud}" data-dest="${v.destino}">Aprobar</button>
                                 <button class="btn-reject btn-rechazar ms-1" style="font-size:.7rem;padding:3px 9px;" data-id="${v.id_solicitud}">Rechazar</button> `;
                }
                if (v.estado === 'aprobada')
                    acciones += `<button class="btn-prim btn-liquidar" style="font-size:.7rem;padding:3px 9px;" data-id="${v.id_solicitud}" data-monto="${v.monto_solicitado}">Liquidar</button>`;

                return `<tr>
                    <td>${i+1}</td>
                    <td><strong>${v.numero_solicitud||'—'}</strong></td>
                    <td>${v.nombre_solicitante||'—'}</td>
                    <td>${v.destino}</td>
                    <td style="white-space:nowrap;">${v.fecha_salida} → ${v.fecha_retorno}</td>
                    <td style="text-align:center;">${v.dias}</td>
                    <td>${fmtM(v.monto_solicitado,v.moneda)}</td>
                    <td>${badgeEstado(v.estado)}</td>
                    <td>${acciones||'—'}</td>
                </tr>`;
            }).join('');
            $('#tbodyViaticos').html(html);
        }});
    }

    $('#btnFiltrarViaticos').on('click', cargarViaticos);

    $('#btnNuevoViatico').on('click', function () {
        document.getElementById('formViatico').reset();
        $('#fViaId').val(0);
        $('#fViaSalida').val(new Date().toISOString().split('T')[0]);
        $('#viaMontoTotal').text('L. 0.00');
        cargarLineasEnSelect('#fViaLinea');
        abrirModal('modalViatico');
    });

    // Calcular total viáticos en tiempo real
    $(document).on('input', '.viatico-input', function () {
        let total = 0;
        $('.viatico-input').each(function () { total += parseFloat(this.value||0); });
        const mon = $('#fViaMoneda').val() === 'USD' ? '$ ' : 'L. ';
        $('#viaMontoTotal').text(mon + total.toLocaleString('es-HN',{minimumFractionDigits:2}));
    });

    $('#btnGuardarViatico').on('click', function () {
        const dest = $('#fViaDestino').val().trim();
        const obj  = $('#fViaObjetivo').val().trim();
        if (!dest || !obj) { SAG.toast('Destino y objetivo son obligatorios.', 'warning'); return; }
        SAG.btnLoading('#btnGuardarViatico', true);
        const fd = new FormData(document.getElementById('formViatico'));
        $.ajax({
            url: BASE + '/presupuesto/viaticos/save', type: 'POST', data: fd,
            processData: false, contentType: false,
            success: res => {
                SAG.btnLoading('#btnGuardarViatico', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalViatico');
                cargarViaticos();
            },
            error: () => SAG.btnLoading('#btnGuardarViatico', false),
        });
    });

    // Visto bueno
    $(document).on('click', '.btn-approve:not(.btn-aprobar)', function () {
        const id   = $(this).data('id');
        const dest = $(this).data('dest');
        const mont = $(this).data('monto');
        $('#fVistoId').val(id);
        $('#fVistoObs').val('');
        $('#fVistoResumen').html(`<i class="fas fa-map-marker-alt me-1"></i>${dest} — <strong>${fmt(mont)}</strong>`);
        abrirModal('modalVisto');
    });

    $('#btnConfirmarVisto').on('click', function () {
        SAG.ajax({
            url: '/presupuesto/viaticos/visto',
            data: { id: $('#fVistoId').val(), observacion: $('#fVistoObs').val() },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalVisto');
                cargarViaticos();
            },
        });
    });

    // Aprobar
    $(document).on('click', '.btn-aprobar', function () {
        $('#fAprobarId').val($(this).data('id'));
        $('#fAprobarAccion').val('aprobar');
        $('#fAprobarObs').val('');
        $('#tituloAprobar').html('<i class="fas fa-stamp me-2"></i>Aprobar Solicitud de Viáticos');
        $('#fAprobarResumen').html(`Destino: <strong>${$(this).data('dest')}</strong>`);
        $('#btnConfirmarAprobar').css('background','#16a34a').html('<i class="fas fa-check me-1"></i>Aprobar');
        abrirModal('modalAprobar');
    });

    // Rechazar
    $(document).on('click', '.btn-rechazar', function () {
        $('#fAprobarId').val($(this).data('id'));
        $('#fAprobarAccion').val('rechazar');
        $('#fAprobarObs').val('');
        $('#tituloAprobar').html('<i class="fas fa-ban me-2"></i>Rechazar Solicitud');
        $('#fAprobarResumen').html('Esta solicitud será marcada como <strong>Rechazada</strong>.');
        $('#btnConfirmarAprobar').css('background','#dc2626').html('<i class="fas fa-ban me-1"></i>Rechazar');
        abrirModal('modalAprobar');
    });

    $('#btnConfirmarAprobar').on('click', function () {
        const obs = $('#fAprobarObs').val().trim();
        if (!obs) { SAG.toast('La observación/resolución es obligatoria.', 'warning'); return; }
        SAG.ajax({
            url: '/presupuesto/viaticos/aprobar',
            data: { id: $('#fAprobarId').val(), accion: $('#fAprobarAccion').val(), observacion: obs },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalAprobar');
                cargarViaticos();
            },
        });
    });

    // Liquidar
    $(document).on('click', '.btn-liquidar', function () {
        $('#fLiqId').val($(this).data('id'));
        $('#fLiqMonto').val($(this).data('monto'));
        $('#fLiqFecha').val(new Date().toISOString().split('T')[0]);
        abrirModal('modalLiquidar');
    });

    $('#btnConfirmarLiquidar').on('click', function () {
        const monto = parseFloat($('#fLiqMonto').val());
        if (isNaN(monto) || monto <= 0) { SAG.toast('Ingrese el monto ejecutado.', 'warning'); return; }
        SAG.btnLoading('#btnConfirmarLiquidar', true);
        const fd = new FormData(document.getElementById('formLiquidar'));
        $.ajax({
            url: BASE + '/presupuesto/viaticos/liquidar', type:'POST', data: fd,
            processData: false, contentType: false,
            success: res => {
                SAG.btnLoading('#btnConfirmarLiquidar', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalLiquidar');
                cargarViaticos();
            },
            error: () => SAG.btnLoading('#btnConfirmarLiquidar', false),
        });
    });

    // ─── GASTOS VARIOS ────────────────────────────────────────
    function cargarGastos() {
        SAG.ajax({ url:'/presupuesto/gastos/listar', data:{}, success: res => {
            const rows = res.data || [];
            if (!rows.length) {
                $('#tbodyGastos').html('<tr><td colspan="10" style="text-align:center;padding:20px;color:#aaa;">Sin registros.</td></tr>');
                return;
            }
            const html = rows.map((g,i) => {
                let acciones = `<button class="btn-sm-icon btn-edit-gasto" data-id="${g.id_gasto}" title="Editar"><i class="fas fa-pen"></i></button>`;
                if (perms.esAdmin && g.estado === 'registrado')
                    acciones += ` <button class="btn-approve" style="font-size:.7rem;padding:3px 8px;" data-id="${g.id_gasto}" data-est="aprobado">Aprobar</button>`;
                return `<tr>
                    <td>${i+1}</td><td>${g.descripcion}</td><td>${g.tipo}</td>
                    <td>${g.linea_nombre||'—'}</td><td>${fmtM(g.monto,g.moneda)}</td>
                    <td>${g.fecha_gasto}</td><td>${g.beneficiario||'—'}</td>
                    <td>${g.numero_documento||'—'}</td><td>${badgeEstado(g.estado)}</td>
                    <td>${acciones}</td></tr>`;
            }).join('');
            $('#tbodyGastos').html(html);
        }});
    }

    $('#btnNuevoGasto').on('click', function () {
        $('#tituloGasto').html('<i class="fas fa-plus me-2"></i>Registrar Gasto');
        document.getElementById('formGasto').reset();
        $('#fGastoId').val(0);
        $('#fGastoFecha').val(new Date().toISOString().split('T')[0]);
        cargarLineasEnSelect('#fGastoLinea');
        abrirModal('modalGasto');
    });

    $('#btnGuardarGasto').on('click', function () {
        if (!$('#fGastoDesc').val().trim()) { SAG.toast('La descripción es obligatoria.', 'warning'); return; }
        SAG.btnLoading('#btnGuardarGasto', true);
        const fd = new FormData(document.getElementById('formGasto'));
        $.ajax({
            url: BASE + '/presupuesto/gastos/save', type:'POST', data: fd,
            processData: false, contentType: false,
            success: res => {
                SAG.btnLoading('#btnGuardarGasto', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalGasto');
                cargarGastos();
            },
            error: () => SAG.btnLoading('#btnGuardarGasto', false),
        });
    });

    $(document).on('click', '.btn-approve[data-est]', function () {
        const id = $(this).data('id'), est = $(this).data('est');
        SAG.confirm('¿Aprobar este gasto?', function () {
            SAG.ajax({ url:'/presupuesto/gastos/estado', data:{id,estado:est},
                success: res => { SAG.toast(res.message,res.success?'success':'error'); cargarGastos(); }
            });
        });
    });

    // ─── DOCUMENTOS ───────────────────────────────────────────
    const TIPOS_DOC = {
        carta_entendimiento:'Carta de Entendimiento', perfil_programa:'Perfil del Programa',
        acuerdo:'Acuerdo', convenio:'Convenio', resolucion:'Resolución',
        acta:'Acta', otro:'Otro'
    };

    function cargarDocumentos() {
        SAG.ajax({ url:'/presupuesto/documentos/listar', data:{}, success: res => {
            const rows = res.data || [];
            if (!rows.length) {
                $('#tbodyDocumentos').html('<tr><td colspan="9" style="text-align:center;padding:20px;color:#aaa;">Sin documentos.</td></tr>');
                return;
            }
            const html = rows.map((d,i) => {
                const size = d.tamano_bytes ? (d.tamano_bytes/1024).toFixed(1)+' KB' : '—';
                const acciones = [
                    `<a class="btn-sm-icon" href="${BASE}/presupuesto/documentos/ver?id=${d.id_documento}" target="_blank" title="Ver documento"><i class="fas fa-eye"></i></a>`,
                    perms.esAdmin
                        ? `<button class="btn-sm-icon btn-del-doc ms-1" data-id="${d.id_documento}" title="Eliminar"><i class="fas fa-trash" style="color:#dc2626;"></i></button>` : '',
                ].join('');
                return `<tr>
                    <td>${i+1}</td>
                    <td>${TIPOS_DOC[d.tipo]||d.tipo}</td>
                    <td><strong>${d.nombre}</strong></td>
                    <td style="max-width:180px;font-size:.77rem;">${d.descripcion||'—'}</td>
                    <td>${d.fecha_documento||'—'}</td>
                    <td>${size}</td>
                    <td>${d.vigente ? '<span class="badge-pres bp-aprobada">Sí</span>' : '<span class="badge-pres bp-borrador">No</span>'}</td>
                    <td>${d.subido_por||'—'}</td>
                    <td>${acciones}</td></tr>`;
            }).join('');
            $('#tbodyDocumentos').html(html);
        }});
    }

    $('#btnNuevoDoc').on('click', function () {
        document.getElementById('formDocumento').reset();
        $('#fDocId').val(0);
        $('#fDocFecha').val(new Date().toISOString().split('T')[0]);
        abrirModal('modalDocumento');
    });

    $('#btnGuardarDoc').on('click', function () {
        if (!$('#fDocNombre').val().trim()) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        if (!$('#fDocArchivo').val() && !$('#fDocId').val()*1) { SAG.toast('El archivo es obligatorio.', 'warning'); return; }
        SAG.btnLoading('#btnGuardarDoc', true);
        const fd = new FormData(document.getElementById('formDocumento'));
        $.ajax({
            url: BASE + '/presupuesto/documentos/save', type:'POST', data: fd,
            processData: false, contentType: false,
            success: res => {
                SAG.btnLoading('#btnGuardarDoc', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message); cerrarModal('modalDocumento');
                cargarDocumentos();
            },
            error: () => SAG.btnLoading('#btnGuardarDoc', false),
        });
    });

    $(document).on('click', '.btn-del-doc', function () {
        const id = $(this).data('id');
        SAG.confirm('¿Eliminar este documento del sistema?', function () {
            SAG.ajax({ url:'/presupuesto/documentos/delete', data:{id},
                success: res => { SAG.toast(res.message,res.success?'success':'error'); cargarDocumentos(); }
            });
        });
    });

    // Link rápido a líneas desde el alert
    $('#linkVerLineas').on('click', function(e) {
        e.preventDefault();
        document.querySelector('.mode-tab[data-tab="Presupuesto"]').click();
    });

});
