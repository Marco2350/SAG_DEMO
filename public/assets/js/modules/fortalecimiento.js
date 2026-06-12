/**
 * fortalecimiento.js — Módulo Acciones de Fortalecimiento (FPROG)
 */
$(function () {

    const BASE      = SAG.BASE_URL;
    const CSRF      = () => document.querySelector('meta[name="csrf-token"]').content;

    let tabla;
    let accionActivaId   = 0;
    let accionActivaData = null;
    let idAEliminar      = 0;

    const modalAccion  = new bootstrap.Modal('#modalAccion');
    const modalVer     = new bootstrap.Modal('#modalVerAccion');
    const modalDelete  = new bootstrap.Modal('#modalConfirmarDelete');

    // ── TABS ──────────────────────────────────────────────────────
    window.switchTab = function (tab) {
        ['listado', 'participantes'].forEach(t => {
            document.getElementById('tab-' + t).style.display = (t === tab) ? 'block' : 'none';
        });
        document.querySelectorAll('.mode-tab').forEach((btn, i) => {
            btn.classList.toggle('active',
                (tab === 'listado' && i === 0) ||
                (tab === 'participantes' && i === 1)
            );
        });
        if (tab === 'participantes') actualizarBannerAccion();
    };

    // ── DATATABLE ─────────────────────────────────────────────────
    function initTabla() {
        tabla = $('#tablaAcciones').DataTable({
            processing: true,
            ajax: {
                url:  BASE + '/fortalecimiento/listar',
                type: 'POST',
                data: d => {
                    d.tipo_accion     = $('#filtroTipo').val();
                    d.estado          = $('#filtroEstado').val();
                    d.id_departamento = $('#filtroDepto').val();
                    d._csrf           = CSRF();
                },
                dataSrc: 'data',
            },
            columns: [
                { data: 'id_accion',     width: '45px' },
                { data: 'tipo',          width: '110px' },
                { data: 'titulo' },
                { data: 'alcance' },
                { data: 'fecha_inicio',  width: '95px' },
                { data: 'fecha_fin',     width: '95px' },
                { data: 'responsable' },
                { data: 'participantes', className: 'text-center', width: '55px' },
                { data: 'avance',        className: 'text-center', width: '100px', orderable: false },
                { data: 'estado',        className: 'text-center', orderable: false },
                { data: 'acciones',      className: 'text-center', orderable: false, width: '80px' },
            ],
            language:   { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
            order:      [[4, 'desc']],
            pageLength: 15,
        });
    }
    initTabla();

    // ── FILTROS ───────────────────────────────────────────────────
    $('#btnFiltrar').on('click', () => tabla.ajax.reload());
    $('#filtroTipo, #filtroEstado, #filtroDepto').on('change', () => tabla.ajax.reload());

    // ── ABRIR MODAL NUEVA ─────────────────────────────────────────
    $('#btnNuevaAccion, #btnCrearDesdeParticipantes').on('click', () => {
        limpiarFormAccion();
        $('#modalAccionTitulo').html('<i class="fas fa-plus-circle me-2"></i>Nueva Acción');
        modalAccion.show();
    });

    // ── VER DETALLE ───────────────────────────────────────────────
    $(document).on('click', '.btn-ver-accion', function () {
        const id = $(this).data('id');
        cargarDetalleModal(id);
    });

    function cargarDetalleModal(id) {
        SAG.post(BASE + '/fortalecimiento/get', { id }, res => {
            if (!res.success) { SAG.toast(res.message, 'error'); return; }
            accionActivaId   = id;
            accionActivaData = res.data.accion;
            renderModalVer(res.data.accion);
            modalVer.show();
        });
    }

    function renderModalVer(a) {
        const fmtFecha = f => f ? f : '—';
        const fmtMoney = v => v ? 'L ' + parseFloat(v).toLocaleString('es-HN', { minimumFractionDigits: 2 }) : '—';
        const pct = (a.meta > 0) ? Math.min(100, Math.round((a.avance / a.meta) * 100)) : null;

        const estadoColors = {
            planificado:  { bg: '#FBF3DB', color: '#956400' },
            en_ejecucion: { bg: '#dbeafe', color: '#1d4ed8' },
            completado:   { bg: '#EDF3EC', color: '#346538' },
            cancelado:    { bg: '#F1F1EF', color: '#787774' },
        };
        const ec = estadoColors[a.estado] ?? { bg: '#f0f0f0', color: '#555' };

        const etiquetasEstado = {
            planificado:  'Planificado',
            en_ejecucion: 'En Ejecución',
            completado:   'Completado',
            cancelado:    'Cancelado',
        };

        const etiquetasTipo = {
            consultoria: 'Consultoría', taller: 'Taller', reunion: 'Reunión',
            estudio: 'Estudio', asistencia_tecnica: 'Asistencia Técnica',
            capacitacion: 'Capacitación', otro: 'Otro',
        };

        document.getElementById('modalVerBody').innerHTML = `
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
          <div>
            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#999;margin-bottom:4px;">
              ${etiquetasTipo[a.tipo_accion] ?? a.tipo_accion}
            </div>
            <h5 style="font-weight:700;margin:0;">${SAG.escapeHtml(a.titulo)}</h5>
          </div>
          <span style="background:${ec.bg};color:${ec.color};padding:5px 14px;border-radius:999px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;">
            ${etiquetasEstado[a.estado] ?? a.estado}
          </span>
        </div>

        ${a.objetivo ? `<p style="font-size:.87rem;color:#555;border-left:3px solid var(--primario);padding-left:10px;margin-bottom:16px;">${SAG.escapeHtml(a.objetivo)}</p>` : ''}

        <div class="row g-3 mb-3" style="font-size:.85rem;">
          <div class="col-6 col-md-3">
            <div style="color:#999;font-size:.72rem;text-transform:uppercase;margin-bottom:2px;">Alcance</div>
            <strong>${a.alcance === 'departamental' ? SAG.escapeHtml(a.departamento ?? '—') : 'Nacional'}</strong>
          </div>
          <div class="col-6 col-md-3">
            <div style="color:#999;font-size:.72rem;text-transform:uppercase;margin-bottom:2px;">Inicio</div>
            <strong>${fmtFecha(a.fecha_inicio)}</strong>
          </div>
          <div class="col-6 col-md-3">
            <div style="color:#999;font-size:.72rem;text-transform:uppercase;margin-bottom:2px;">Fin</div>
            <strong>${fmtFecha(a.fecha_fin)}</strong>
          </div>
          <div class="col-6 col-md-3">
            <div style="color:#999;font-size:.72rem;text-transform:uppercase;margin-bottom:2px;">Participantes</div>
            <strong>${a.num_participantes}</strong>
          </div>
          <div class="col-12 col-md-6">
            <div style="color:#999;font-size:.72rem;text-transform:uppercase;margin-bottom:2px;">Responsable</div>
            <strong>${SAG.escapeHtml(a.responsable ?? '—')}</strong>
          </div>
          <div class="col-12 col-md-6">
            <div style="color:#999;font-size:.72rem;text-transform:uppercase;margin-bottom:2px;">Institución Ejecutora</div>
            <strong>${SAG.escapeHtml(a.institucion_ejecutora ?? '—')}</strong>
          </div>
        </div>

        ${a.indicador ? `
        <div style="background:#f8f9fa;border-radius:8px;padding:12px 16px;margin-bottom:12px;">
          <div style="font-size:.72rem;text-transform:uppercase;color:#999;margin-bottom:6px;">Indicador</div>
          <div style="font-size:.87rem;">${SAG.escapeHtml(a.indicador)}</div>
          ${pct !== null ? `
          <div style="margin-top:8px;">
            <div style="display:flex;justify-content:space-between;font-size:.75rem;color:#666;margin-bottom:4px;">
              <span>Avance: ${a.avance} / ${a.meta}</span><span>${pct}%</span>
            </div>
            <div style="background:#e5e7eb;border-radius:4px;height:8px;">
              <div style="background:var(--primario);width:${pct}%;height:8px;border-radius:4px;transition:width .4s;"></div>
            </div>
          </div>` : ''}
        </div>` : ''}

        <div class="row g-3" style="font-size:.85rem;">
          <div class="col-6">
            <div style="color:#999;font-size:.72rem;text-transform:uppercase;margin-bottom:2px;">Presupuesto Asignado</div>
            <strong>${fmtMoney(a.presupuesto_asignado)}</strong>
          </div>
          <div class="col-6">
            <div style="color:#999;font-size:.72rem;text-transform:uppercase;margin-bottom:2px;">Presupuesto Ejecutado</div>
            <strong>${fmtMoney(a.presupuesto_ejecutado)}</strong>
          </div>
        </div>

        ${a.observaciones ? `<div style="margin-top:14px;font-size:.84rem;color:#555;border-top:1px solid #f0f0f0;padding-top:12px;">${SAG.escapeHtml(a.observaciones)}</div>` : ''}
        `;

        // Botones de estado en el footer del modal
        const btnEditar       = document.querySelector('.btn-editar-desde-modal');
        const btnParticipantes = document.querySelector('.btn-participantes-desde-modal');
        const btnIniciar      = document.querySelector('.btn-iniciar');
        const btnCompletar    = document.querySelector('.btn-completar');
        const btnCancelar     = document.querySelector('.btn-cancelar');
        const btnReactivar    = document.querySelector('.btn-reactivar');

        [btnEditar, btnParticipantes, btnIniciar, btnCompletar, btnCancelar, btnReactivar].forEach(b => {
            if (b) b.setAttribute('data-id', a.id_accion);
        });

        btnIniciar.classList.toggle('d-none',   a.estado !== 'planificado');
        btnCompletar.classList.toggle('d-none',  a.estado !== 'en_ejecucion');
        btnCancelar.classList.toggle('d-none',   !['planificado', 'en_ejecucion'].includes(a.estado));
        btnReactivar.classList.toggle('d-none',  a.estado !== 'cancelado');
    }

    // ── EDITAR desde listado ──────────────────────────────────────
    $(document).on('click', '.btn-editar-accion', function () {
        const id = $(this).data('id');
        SAG.post(BASE + '/fortalecimiento/get', { id }, res => {
            if (!res.success) { SAG.toast(res.message, 'error'); return; }
            llenarFormAccion(res.data.accion);
            $('#modalAccionTitulo').html('<i class="fas fa-pen me-2"></i>Editar Acción');
            modalAccion.show();
        });
    });

    // ── EDITAR desde modal detalle ────────────────────────────────
    $(document).on('click', '.btn-editar-desde-modal', function () {
        const id = $(this).data('id');
        modalVer.hide();
        SAG.post(BASE + '/fortalecimiento/get', { id }, res => {
            if (!res.success) { SAG.toast(res.message, 'error'); return; }
            llenarFormAccion(res.data.accion);
            $('#modalAccionTitulo').html('<i class="fas fa-pen me-2"></i>Editar Acción');
            setTimeout(() => modalAccion.show(), 300);
        });
    });

    // ── PARTICIPANTES desde modal detalle ─────────────────────────
    $(document).on('click', '.btn-participantes-desde-modal', function () {
        const id = $(this).data('id');
        modalVer.hide();
        switchTab('participantes');
        if (accionActivaData) activarAccionParticipantes(accionActivaData);
    });

    // ── GUARDAR ───────────────────────────────────────────────────
    $('#btnGuardarAccion').on('click', () => {
        const data = SAG.formData('#formAccion');
        if (!data.tipo_accion) { SAG.toast('Seleccione el tipo de acción.', 'warn'); return; }
        if (!data.titulo?.trim()) { SAG.toast('El título es obligatorio.', 'warn'); return; }
        if (!data.fecha_inicio) { SAG.toast('Ingrese la fecha de inicio.', 'warn'); return; }
        if (data.alcance === 'departamental' && !data.id_departamento) {
            SAG.toast('Seleccione el departamento.', 'warn'); return;
        }

        SAG.post(BASE + '/fortalecimiento/save', data, res => {
            if (!res.success) { SAG.toast(res.message, 'error'); return; }
            SAG.toast(res.message, 'success');
            modalAccion.hide();
            tabla.ajax.reload();
            // Si estamos en participantes, activar la nueva acción
            accionActivaId = res.data.id;
            SAG.post(BASE + '/fortalecimiento/get', { id: res.data.id }, r2 => {
                if (r2.success) {
                    accionActivaData = r2.data.accion;
                    actualizarBannerAccion();
                }
            });
        });
    });

    // ── ELIMINAR ──────────────────────────────────────────────────
    $(document).on('click', '.btn-eliminar-accion', function () {
        idAEliminar = $(this).data('id');
        modalDelete.show();
    });

    $('#btnConfirmarDelete').on('click', () => {
        SAG.post(BASE + '/fortalecimiento/delete', { id: idAEliminar }, res => {
            SAG.toast(res.message, res.success ? 'success' : 'error');
            if (res.success) { tabla.ajax.reload(); modalDelete.hide(); }
        });
    });

    // ── TRANSICIONES DE ESTADO ────────────────────────────────────
    $(document).on('click', '.btn-iniciar',   function () { cambiarEstado($(this).data('id'), 'en_ejecucion'); });
    $(document).on('click', '.btn-completar', function () { cambiarEstado($(this).data('id'), 'completado'); });
    $(document).on('click', '.btn-cancelar',  function () { cambiarEstado($(this).data('id'), 'cancelado'); });
    $(document).on('click', '.btn-reactivar', function () { cambiarEstado($(this).data('id'), 'planificado'); });

    function cambiarEstado(id, estado) {
        SAG.post(BASE + '/fortalecimiento/estado', { id, estado }, res => {
            SAG.toast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                modalVer.hide();
                tabla.ajax.reload();
            }
        });
    }

    // ── ALCANCE toggle departamento ───────────────────────────────
    $('#aAlcance').on('change', function () {
        const isDept = this.value === 'departamental';
        $('#wrapDepartamento').toggle(isDept);
        if (!isDept) $('#aDepartamento').val('');
    });

    // ── LIMPIAR / LLENAR FORMULARIO ───────────────────────────────
    function limpiarFormAccion() {
        document.getElementById('formAccion').reset();
        $('#aId').val(0);
        $('#wrapDepartamento').hide();
    }

    function llenarFormAccion(a) {
        limpiarFormAccion();
        $('#aId').val(a.id_accion);
        $('#aTipo').val(a.tipo_accion);
        $('#aTitulo').val(a.titulo);
        $('#aObjetivo').val(a.objetivo ?? '');
        $('#aDescripcion').val(a.descripcion ?? '');
        $('#aAlcance').val(a.alcance).trigger('change');
        if (a.alcance === 'departamental') $('#aDepartamento').val(a.id_departamento ?? '');
        $('#aFechaInicio').val(a.fecha_inicio ?? '');
        $('#aFechaFin').val(a.fecha_fin ?? '');
        $('#aResponsable').val(a.id_responsable ?? '');
        $('#aInstitucion').val(a.institucion_ejecutora ?? '');
        $('#aIndicador').val(a.indicador ?? '');
        $('#aMeta').val(a.meta ?? '');
        $('#aAvance').val(a.avance ?? '');
        $('#aPresAsig').val(a.presupuesto_asignado ?? '');
        $('#aPresEjec').val(a.presupuesto_ejecutado ?? '');
    }

    // ══════════════════════════════════════════════════════════════
    //  PARTICIPANTES
    // ══════════════════════════════════════════════════════════════

    // Activar acción desde el listado (clic en fila)
    $(document).on('click', '.btn-ver-accion', function () {
        accionActivaId = $(this).data('id');
    });

    // Ir a participantes desde modal de detalle
    $(document).on('click', '.btn-participantes-desde-modal', function () {
        if (accionActivaData) activarAccionParticipantes(accionActivaData);
    });

    function activarAccionParticipantes(a) {
        if (!a) return;
        accionActivaId   = a.id_accion;
        accionActivaData = a;

        $('#sinAccionActiva').hide();
        $('#panelParticipantes').show();
        $('#accionActivaBanner').css('display', 'flex');
        $('#bannerAccionNombre').text(a.titulo);
        $('#bannerAccionInfo').text(
            (a.tipo_accion ? AccionFortalecimientoModel_etiqueta(a.tipo_accion) : '') +
            (a.fecha_inicio ? ' · ' + a.fecha_inicio : '')
        );
        $('#pAccionId').val(a.id_accion);

        cargarParticipantes(a.id_accion);
    }

    function actualizarBannerAccion() {
        if (accionActivaId && accionActivaData) {
            activarAccionParticipantes(accionActivaData);
        }
    }

    // Helper local para etiquetas de tipo (evita repetir el objeto)
    function AccionFortalecimientoModel_etiqueta(tipo) {
        const m = {
            consultoria: 'Consultoría', taller: 'Taller', reunion: 'Reunión',
            estudio: 'Estudio', asistencia_tecnica: 'Asistencia Técnica',
            capacitacion: 'Capacitación', otro: 'Otro',
        };
        return m[tipo] ?? tipo;
    }

    function cargarParticipantes(idAccion) {
        SAG.post(BASE + '/fortalecimiento/get', { id: idAccion }, res => {
            if (!res.success) return;
            renderTablaParticipantes(res.data.participantes ?? []);
        });
    }

    function renderTablaParticipantes(rows) {
        const tbody = document.getElementById('tbodyParticipantes');
        const trSin = document.getElementById('trSinPart');

        // Limpiar filas dinámicas
        tbody.querySelectorAll('tr:not(#trSinPart)').forEach(r => r.remove());

        if (rows.length === 0) {
            trSin.style.display = '';
            $('#labelTotalPart').text('0 registrados');
            $('#bannerPartCount').text('0 participantes');
            return;
        }

        trSin.style.display = 'none';
        rows.forEach((p, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${i + 1}</td>
                <td>${SAG.escapeHtml(p.nombre + ' ' + (p.apellido ?? ''))}</td>
                <td>${SAG.escapeHtml(p.cargo ?? '—')}</td>
                <td>${SAG.escapeHtml(p.institucion ?? '—')}</td>
                <td class="text-center">${p.sexo ?? '—'}</td>
                <td class="text-center">
                  <button class="btn-sm-icon btn-outline btn-del-part"
                          data-id="${p.id_participante}" data-accion="${accionActivaId}"
                          title="Eliminar">
                    <i class="fas fa-trash-can" style="color:#dc2626;"></i>
                  </button>
                </td>`;
            tbody.appendChild(tr);
        });

        const total = rows.length;
        $('#labelTotalPart').text(total + ' registrado' + (total !== 1 ? 's' : ''));
        $('#bannerPartCount').text(total + ' participante' + (total !== 1 ? 's' : ''));
    }

    // Agregar participante
    $('#btnAgregarPart').on('click', () => {
        if (!accionActivaId) { SAG.toast('Seleccione una acción primero.', 'warn'); return; }
        const nombre = $('#pNombre').val().trim();
        if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warn'); return; }

        const data = {
            id_accion:   accionActivaId,
            nombre,
            apellido:    $('#pApellido').val().trim(),
            cargo:       $('#pCargo').val().trim(),
            institucion: $('#pInstitucion').val().trim(),
            sexo:        $('#pSexo').val(),
        };

        SAG.post(BASE + '/fortalecimiento/participante/add', data, res => {
            SAG.toast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                $('#formParticipante')[0].reset();
                $('#pAccionId').val(accionActivaId);
                cargarParticipantes(accionActivaId);
                tabla.ajax.reload(null, false);
            }
        });
    });

    // Limpiar formulario participante
    $('#btnLimpiarPart').on('click', () => {
        $('#formParticipante')[0].reset();
        $('#pAccionId').val(accionActivaId);
    });

    // Eliminar participante
    $(document).on('click', '.btn-del-part', function () {
        if (!confirm('¿Eliminar este participante?')) return;
        const idPart   = $(this).data('id');
        const idAccion = $(this).data('accion');
        SAG.post(BASE + '/fortalecimiento/participante/delete', {
            id_participante: idPart,
            id_accion:       idAccion,
        }, res => {
            SAG.toast(res.message, res.success ? 'success' : 'error');
            if (res.success) {
                cargarParticipantes(idAccion);
                tabla.ajax.reload(null, false);
            }
        });
    });

});
