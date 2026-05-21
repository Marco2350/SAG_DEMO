/**
 * entregas.js — Módulo de Incentivos Entregados (mock)
 * Mayo 2026 — Vista de prueba antes de conectar con KoBoToolbox.
 */
$(function () {
    'use strict';

    const E = SAG_ENT;
    const BASE = SAG.BASE_URL;

    // ── Helpers ──────────────────────────────────────────────
    const ESTADOS = {
        aprobada:           { label: 'Aprobada',           cls: 'eb-aprobada' },
        pendiente_revision: { label: 'Pendiente revisión', cls: 'eb-pendiente_revision' },
        con_alerta:         { label: 'Con alerta',         cls: 'eb-con_alerta' },
        rechazada:          { label: 'Rechazada',          cls: 'eb-rechazada' }
    };
    const RAZONES_NO = {
        stock_insuficiente:       'Stock insuficiente en bodega',
        beneficiario_rechazo:     'Beneficiario rechazó parte',
        beneficiario_no_presente: 'Beneficiario no se presentó',
        logistica:                'Problema logístico/transporte',
        otro:                     'Otro'
    };

    function badge(estado) {
        const e = ESTADOS[estado] || { label: estado, cls: 'eb-pendiente_revision' };
        const alertaIcon = (estado === 'con_alerta' || estado === 'pendiente_revision')
            ? '<i class="fas fa-triangle-exclamation alerta-icon"></i>' : '';
        return `${alertaIcon}<span class="ent-badge ${e.cls}">${e.label}</span>`;
    }

    function fechaHora(fecha, hora) {
        return `${fecha}<br><small style="color:#888;">${hora || ''}</small>`;
    }

    // ── Modal helpers ────────────────────────────────────────
    window.cerrarModal = function (id) {
        document.getElementById(id).classList.remove('show');
    };
    function abrirModal(id) {
        document.getElementById(id).classList.add('show');
    }
    document.querySelectorAll('.modal-overlay').forEach(mo => {
        mo.addEventListener('click', e => { if (e.target === mo) mo.classList.remove('show'); });
    });

    // ── Render tabla ─────────────────────────────────────────
    function renderTabla(filas) {
        const $tb = $('#tblEntregas');
        if (!filas.length) {
            $tb.html(`<tr><td colspan="8" class="tbl-empty">
                <i class="fas fa-inbox"></i>
                No hay entregas que coincidan con los filtros.
            </td></tr>`);
            return;
        }
        const html = filas.map(e => `
            <tr data-id="${e.id_entrega}">
                <td><strong>#${e.id_entrega}</strong></td>
                <td style="white-space:nowrap;">${fechaHora(e.fecha_entrega, e.hora_entrega)}</td>
                <td><code style="font-size:.74rem;color:#0f766e;">${e.dni}</code></td>
                <td>${e.beneficiario}<br><small style="color:#888;">${(e.municipio||'—')} / ${(e.aldea||'—')}</small></td>
                <td><small>${e.bodega}</small></td>
                <td><small>${e.tecnico}</small></td>
                <td style="text-align:center;">
                    <strong>${e.sacos_entregados ?? '—'}</strong>
                    <small style="color:#888;">/ ${e.sacos_asignados ?? '—'}</small>
                </td>
                <td>${badge(e.estado)}</td>
            </tr>
        `).join('');
        $tb.html(html);
    }

    // ── Catálogos dinámicos: Departamento → Municipio ───────
    // Construye el catálogo único de departamentos y municipios desde los datos
    function construirCatalogos() {
        const deptos = new Set();
        const muniPorDepto = {}; // { 'Francisco Morazán': Set('Tegucigalpa', 'Distrito Central', ...) }

        E.entregas.forEach(e => {
            if (e.departamento) {
                deptos.add(e.departamento);
                if (!muniPorDepto[e.departamento]) muniPorDepto[e.departamento] = new Set();
                if (e.municipio) muniPorDepto[e.departamento].add(e.municipio);
            }
        });

        // Llenar select de departamentos (ordenados alfabéticamente)
        const $dep = $('#fDepartamento');
        const deptosList = Array.from(deptos).sort();
        deptosList.forEach(d => $dep.append(`<option value="${d}">${d}</option>`));

        // Guardar el mapa para usarlo cuando cambien el departamento
        E._muniPorDepto = muniPorDepto;
    }

    function actualizarMunicipios() {
        const dep = $('#fDepartamento').val();
        const $mun = $('#fMunicipio');
        $mun.html('<option value="">Todos</option>');
        if (dep && E._muniPorDepto && E._muniPorDepto[dep]) {
            const munis = Array.from(E._muniPorDepto[dep]).sort();
            munis.forEach(m => $mun.append(`<option value="${m}">${m}</option>`));
            $mun.prop('disabled', false);
        } else if (!dep) {
            // Si no hay depto, mostrar TODOS los municipios de todos los deptos
            const todos = new Set();
            Object.values(E._muniPorDepto || {}).forEach(set => set.forEach(m => todos.add(m)));
            Array.from(todos).sort().forEach(m => $mun.append(`<option value="${m}">${m}</option>`));
            $mun.prop('disabled', false);
        }
    }

    // ── Filtrado ─────────────────────────────────────────────
    function aplicarFiltros() {
        const fEstado  = $('#fEstado').val();
        const fDepto   = $('#fDepartamento').val();
        const fMuni    = $('#fMunicipio').val();
        const fBodega  = $('#fBodega').val();
        const fTecnico = $('#fTecnico').val();
        const fDesde   = $('#fDesde').val();
        const fHasta   = $('#fHasta').val();
        const fBuscar  = $('#fBuscar').val().toLowerCase().trim();

        const filas = E.entregas.filter(e => {
            if (fEstado  && e.estado !== fEstado) return false;
            if (fDepto   && e.departamento !== fDepto) return false;
            if (fMuni    && e.municipio !== fMuni) return false;
            if (fBodega  && e.bodega_codigo !== fBodega) return false;
            if (fTecnico && e.tecnico_codigo !== fTecnico) return false;
            if (fDesde   && e.fecha_entrega < fDesde) return false;
            if (fHasta   && e.fecha_entrega > fHasta) return false;
            if (fBuscar) {
                const haystack = `${e.dni} ${e.beneficiario}`.toLowerCase();
                if (!haystack.includes(fBuscar)) return false;
            }
            return true;
        });
        renderTabla(filas);
    }

    // Cuando cambia el departamento → actualizar municipios y re-filtrar
    $('#fDepartamento').on('change', function () {
        actualizarMunicipios();
        aplicarFiltros();
    });

    $('#fEstado, #fMunicipio, #fBodega, #fTecnico, #fDesde, #fHasta').on('change', aplicarFiltros);
    $('#fBuscar').on('input', aplicarFiltros);

    // Botón limpiar filtros
    $('#btnLimpiarFiltros').on('click', function () {
        $('#fEstado, #fDepartamento, #fMunicipio, #fBodega, #fTecnico, #fDesde, #fHasta, #fBuscar').val('');
        actualizarMunicipios();
        aplicarFiltros();
    });

    // ── Click en fila → modal de detalle ─────────────────────
    $(document).on('click', '#tblEntregas tr[data-id]', function () {
        const id = $(this).data('id');
        const e = E.entregas.find(x => x.id_entrega == id);
        if (!e) return;
        abrirDetalle(e);
    });

    function abrirDetalle(e) {
        $('#dTitulo').html(`<i class="fas fa-truck-ramp-box me-2" style="color:var(--primario);"></i>Entrega #${e.id_entrega} — ${e.beneficiario}`);

        let alertaHtml = '';
        if (e.estado === 'con_alerta' || e.estado === 'pendiente_revision') {
            alertaHtml = `<div class="det-alerta">
                <i class="fas fa-triangle-exclamation me-1"></i>
                <strong>Atención:</strong> ${e.alerta_motivo || 'Esta entrega requiere revisión manual.'}
            </div>`;
        }
        if (e.estado === 'rechazada') {
            alertaHtml = `<div class="det-rechazo">
                <i class="fas fa-circle-xmark me-1"></i>
                <strong>Entrega rechazada:</strong> ${e.alerta_motivo || 'Sin motivo especificado.'}
                ${e.revisado_por ? `<br><small>Por ${e.revisado_por} el ${e.fecha_revision || ''}</small>` : ''}
            </div>`;
        }

        const razonNo = e.razon_no_completa ? (RAZONES_NO[e.razon_no_completa] || e.razon_no_completa) : null;

        const body = `
            ${alertaHtml}

            <!-- Beneficiario -->
            <div class="det-section">
                <div class="det-section-title"><i class="fas fa-id-card me-1"></i>Beneficiario</div>
                <div class="det-grid">
                    <div class="det-item"><div class="k">DNI</div><div class="v"><code>${e.dni}</code></div></div>
                    <div class="det-item"><div class="k">Nombre completo</div><div class="v">${e.beneficiario}</div></div>
                    <div class="det-item"><div class="k">Sexo</div><div class="v">${e.sexo === 'F' ? 'Femenino' : (e.sexo === 'M' ? 'Masculino' : '—')}</div></div>
                    <div class="det-item"><div class="k">Departamento</div><div class="v">${e.departamento || '—'}</div></div>
                    <div class="det-item"><div class="k">Municipio</div><div class="v">${e.municipio || '—'}</div></div>
                    <div class="det-item"><div class="k">Aldea</div><div class="v">${e.aldea || '—'}</div></div>
                </div>
                ${e.telefono_actualizado || e.direccion_actualizada ? `
                <div style="background:#fef9e7;border-left:3px solid #d97706;padding:8px 12px;margin-top:8px;border-radius:4px;font-size:.78rem;">
                    <strong>📝 El técnico actualizó:</strong>
                    ${e.telefono_actualizado ? `Teléfono → <strong>${e.telefono_actualizado}</strong>` : ''}
                    ${e.telefono_actualizado && e.direccion_actualizada ? ' · ' : ''}
                    ${e.direccion_actualizada ? `Dirección → <strong>${e.direccion_actualizada}</strong>` : ''}
                </div>` : ''}
            </div>

            <!-- Entrega -->
            <div class="det-section">
                <div class="det-section-title"><i class="fas fa-boxes-stacked me-1"></i>Entrega</div>
                <div class="det-grid">
                    <div class="det-item"><div class="k">Insumo</div><div class="v"><strong>Fertilizante 20-3-18</strong></div></div>
                    <div class="det-item"><div class="k">Sacos asignados</div><div class="v">${e.sacos_asignados ?? '—'}</div></div>
                    <div class="det-item"><div class="k">Sacos entregados</div><div class="v"><strong style="color:${e.entrega_completa ? '#16a34a' : '#d97706'};">${e.sacos_entregados ?? 0}</strong></div></div>
                    <div class="det-item"><div class="k">Entrega completa</div><div class="v">${e.entrega_completa === 1 ? '<span style="color:#16a34a;">✓ Sí</span>' : (e.entrega_completa === 0 ? '<span style="color:#d97706;">✗ No</span>' : '—')}</div></div>
                    ${razonNo ? `<div class="det-item" style="grid-column:span 2;"><div class="k">Razón no completa</div><div class="v">${razonNo}</div></div>` : ''}
                </div>
            </div>

            <!-- Logística -->
            <div class="det-section">
                <div class="det-section-title"><i class="fas fa-warehouse me-1"></i>Logística</div>
                <div class="det-grid">
                    <div class="det-item"><div class="k">Bodega</div><div class="v">${e.bodega} <small style="color:#888;">(${e.bodega_codigo})</small></div></div>
                    <div class="det-item"><div class="k">Técnico</div><div class="v">${e.tecnico}</div></div>
                    <div class="det-item"><div class="k">Fecha y hora</div><div class="v">${e.fecha_entrega} ${e.hora_entrega || ''}</div></div>
                    <div class="det-item"><div class="k">Ubicación GPS</div><div class="v">
                        ${e.gps_lat && e.gps_lon
                            ? `<a class="gps-link" href="https://www.google.com/maps?q=${e.gps_lat},${e.gps_lon}" target="_blank">
                                  <i class="fas fa-map-location-dot"></i> ${e.gps_lat.toFixed(4)}, ${e.gps_lon.toFixed(4)}
                                  ${e.gps_precision ? ` (±${e.gps_precision}m)` : ''}
                               </a>`
                            : '—'}
                    </div></div>
                </div>
            </div>

            ${e.observaciones ? `
            <div class="det-section">
                <div class="det-section-title"><i class="fas fa-comment me-1"></i>Observaciones del técnico</div>
                <div style="background:#f8fafc;padding:10px 12px;border-radius:6px;font-size:.85rem;font-style:italic;color:#475569;">
                    "${e.observaciones}"
                </div>
            </div>` : ''}

            <!-- Imágenes y firmas -->
            <div class="det-section">
                <div class="det-section-title"><i class="fas fa-camera me-1"></i>Verificación visual</div>
                <div class="det-imgs">
                    ${e.foto_dni ? `<div class="det-img"><img src="${e.foto_dni}" alt="DNI"/><div class="label">📷 Foto DNI</div></div>` : ''}
                    ${e.foto_entrega ? `<div class="det-img"><img src="${e.foto_entrega}" alt="Entrega"/><div class="label">📷 Foto Entrega</div></div>` : ''}
                    ${e.firma_beneficiario ? `<div class="det-img"><img src="${e.firma_beneficiario}" alt="Firma"/><div class="label">✍️ Firma Beneficiario</div></div>` : ''}
                    ${e.firma_tecnico ? `<div class="det-img"><img src="${e.firma_tecnico}" alt="Firma técnico"/><div class="label">✍️ Firma Técnico</div></div>` : ''}
                    ${(!e.foto_dni && !e.foto_entrega && !e.firma_beneficiario && !e.firma_tecnico)
                        ? '<div style="color:#aaa;font-size:.85rem;padding:14px;">Sin imágenes adjuntas.</div>' : ''}
                </div>
            </div>

            <!-- Metadata técnica -->
            <div class="det-section">
                <div class="det-section-title"><i class="fas fa-database me-1"></i>Sincronización</div>
                <div class="det-grid">
                    <div class="det-item"><div class="k">ID KoBo</div><div class="v"><code style="font-size:.75rem;">${e.kobo_submission_id}</code></div></div>
                    <div class="det-item"><div class="k">Sincronizado</div><div class="v">${e.synced_at || '—'}</div></div>
                    ${e.revisado_por ? `<div class="det-item"><div class="k">Revisado por</div><div class="v">${e.revisado_por}</div></div>` : ''}
                    ${e.fecha_revision ? `<div class="det-item"><div class="k">Fecha revisión</div><div class="v">${e.fecha_revision}</div></div>` : ''}
                </div>
            </div>
        `;
        $('#dBody').html(body);

        // Botones de acción según estado y rol
        let foot = `<div></div><div>`;
        if (E.esAdmin && (e.estado === 'pendiente_revision' || e.estado === 'con_alerta')) {
            foot += `<button class="btn-rechazar me-2" data-id="${e.id_entrega}" data-accion="rechazar">
                <i class="fas fa-circle-xmark"></i> Rechazar
            </button>`;
            foot += `<button class="btn-aprobar" data-id="${e.id_entrega}" data-accion="aprobar">
                <i class="fas fa-circle-check"></i> Aprobar entrega
            </button>`;
        }
        foot += `<button class="btn-cerrar ms-2" onclick="cerrarModal('modalDetalle')">Cerrar</button></div>`;
        $('#dFoot').html(foot);

        abrirModal('modalDetalle');
    }

    // ── Botones aprobar/rechazar ─────────────────────────────
    $(document).on('click', '#dFoot button[data-accion]', function () {
        const id = $(this).data('id');
        const accion = $(this).data('accion');
        const e = E.entregas.find(x => x.id_entrega == id);
        if (!e) return;

        $('#oId').val(id);
        $('#oAccion').val(accion);
        $('#oTexto').val('');

        const colorAccion = accion === 'aprobar' ? '#16a34a' : '#dc2626';
        $('#oTitulo').html(`<i class="fas fa-${accion === 'aprobar' ? 'check' : 'ban'} me-2" style="color:${colorAccion};"></i>${accion === 'aprobar' ? 'Aprobar' : 'Rechazar'} entrega`);
        $('#oResumen').html(`
            <strong>${e.beneficiario}</strong> &mdash; DNI ${e.dni}<br>
            <small style="color:#666;">Entrega #${e.id_entrega} en ${e.bodega}</small>
        `);
        $('#oBtnConfirmar')
            .removeClass('btn-aprobar btn-rechazar')
            .addClass(accion === 'aprobar' ? 'btn-aprobar' : 'btn-rechazar')
            .html(`<i class="fas fa-${accion === 'aprobar' ? 'check' : 'ban'}"></i> Confirmar ${accion === 'aprobar' ? 'aprobación' : 'rechazo'}`);

        cerrarModal('modalDetalle');
        abrirModal('modalObs');
    });

    $('#oBtnConfirmar').on('click', function () {
        const id = $('#oId').val();
        const accion = $('#oAccion').val();
        const obs = $('#oTexto').val().trim();
        if (accion === 'rechazar' && !obs) {
            SAG.toast('Debe escribir el motivo del rechazo.', 'warning');
            return;
        }
        SAG.btnLoading('#oBtnConfirmar', true);
        SAG.ajax({
            url: '/entregas/' + accion,
            data: { id, observacion: obs },
            success: r => {
                SAG.btnLoading('#oBtnConfirmar', false);
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
                cerrarModal('modalObs');
                // Mock: actualizar visualmente la entrega en el array local
                const e = E.entregas.find(x => x.id_entrega == id);
                if (e) {
                    e.estado = accion === 'aprobar' ? 'aprobada' : 'rechazada';
                    e.alerta_motivo = obs || e.alerta_motivo;
                }
                aplicarFiltros();
            },
            error: () => SAG.btnLoading('#oBtnConfirmar', false)
        });
    });

    // ── Botón sincronizar (KoBo) ─────────────────────────────
    $('#btnSincronizar').on('click', function () {
        const $btn = $(this);
        $btn.addClass('is-loading').prop('disabled', true);
        $btn.html('<i class="fas fa-rotate"></i> Sincronizando con KoBo...');

        SAG.ajax({
            url: '/entregas/sincronizar',
            data: {},
            success: r => {
                $btn.removeClass('is-loading').prop('disabled', false);
                $btn.html('<i class="fas fa-rotate"></i> Sincronizar con KoBo');
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
            },
            error: () => {
                $btn.removeClass('is-loading').prop('disabled', false);
                $btn.html('<i class="fas fa-rotate"></i> Sincronizar con KoBo');
            }
        });
    });

    // ── Botón sincronizar (Trazaragro) ───────────────────────
    $('#btnSincronizarTrazaragro').on('click', function () {
        const $btn = $(this);
        $btn.addClass('is-loading').prop('disabled', true);
        $btn.html('<i class="fas fa-link"></i> Conectando con Trazaragro...');

        SAG.ajax({
            url: '/entregas/sincronizarTrazaragro',
            data: {},
            success: r => {
                $btn.removeClass('is-loading').prop('disabled', false);
                $btn.html('<i class="fas fa-link"></i> Sincronizar con Trazaragro');
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
            },
            error: () => {
                $btn.removeClass('is-loading').prop('disabled', false);
                $btn.html('<i class="fas fa-link"></i> Sincronizar con Trazaragro');
            }
        });
    });

    // ── INIT ─────────────────────────────────────────────────
    construirCatalogos();
    actualizarMunicipios();
    aplicarFiltros();
});
