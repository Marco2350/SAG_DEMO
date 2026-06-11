/**
 * capacitaciones.js — Módulo de Capacitaciones
 * SAG Programas — sag_programas
 */
$(function () {

    let tabla;
    let capActivaId   = 0;
    let capActivaData = null;
    const modalVer = new bootstrap.Modal('#modalVerCap');
    const modalCap = new bootstrap.Modal('#modalCapacitacion');

    // ── TABS (listado | participantes) ────────────────
    window.switchTab = function (tab) {
        ['listado', 'participantes'].forEach(t => {
            document.getElementById('tab-' + t).style.display = (t === tab) ? 'block' : 'none';
        });
        document.querySelectorAll('.mode-tab').forEach((btn, i) => {
            btn.classList.toggle('active',
                (tab === 'listado'       && i === 0) ||
                (tab === 'participantes' && i === 1)
            );
        });
        if (tab === 'participantes') actualizarBanner();
    };

    // ── DATATABLES ────────────────────────────────────
    function initTabla() {
        tabla = $('#tablaCapacitaciones').DataTable({
            processing: true,
            ajax: {
                url:  SAG.BASE_URL + '/capacitaciones/listar',
                type: 'POST',
                data: function (d) {
                    d.id_departamento = $('#filtroDepCap').val();
                    d.id_tema         = $('#filtroTemaCap').val();
                    d.id_tecnico      = $('#filtroTecCap').val();
                    d.estado          = $('#filtroEstadoCap').val();
                },
                dataSrc: 'data',
            },
            columns: [
                { data: 'id_capacitacion', width: '40px' },
                { data: 'fecha' },
                { data: 'tema' },
                { data: 'subtema' },
                { data: 'tecnico' },
                { data: 'ubicacion' },
                { data: 'lugar' },
                { data: 'participantes', orderable: false, className: 'text-center' },
                { data: 'duracion',      className: 'text-center' },
                { data: 'estado',        orderable: false, className: 'text-center' },
                { data: 'acciones',      orderable: false, className: 'text-center' },
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
            order:      [[1, 'desc']],
            pageLength: 15,
        });
    }

    initTabla();

    $('#btnFiltrarCap').on('click', function () {
        tabla.ajax.reload();
    });
    $('#filtroDepCap, #filtroTemaCap, #filtroTecCap, #filtroEstadoCap').on('change', function () {
        tabla.ajax.reload();
    });

    // ── ABRIR MODAL NUEVA ─────────────────────────────
    $('#btnNuevaCap, #btnCrearDesdeParticipantes').on('click', function () {
        limpiarFormCap();
        $('#modalCapTitulo').html('<i class="fas fa-plus-circle me-2"></i>Nueva Capacitación');
        modalCap.show();
    });

    // ── GUARDAR CAPACITACIÓN (crear / editar) ─────────
    $('#btnGuardarCap').on('click', function () {
        const esNueva = parseInt($('#capId').val() || '0') === 0;
        const dep   = $('#cDep').val();
        const mun   = $('#cMun').val();
        const tema  = $('#cTema').val();
        const tec   = $('#cTecnico').val();
        const fecha = $('#cFecha').val();

        if (!dep)   { SAG.toast('Seleccione un departamento.',        'warning'); return; }
        if (!mun)   { SAG.toast('Seleccione un municipio.',           'warning'); return; }
        if (!tema)  { SAG.toast('Seleccione el tema.',                'warning'); return; }
        if (!tec)   { SAG.toast('Seleccione el técnico responsable.', 'warning'); return; }
        if (!fecha) { SAG.toast('Ingrese la fecha de capacitación.',  'warning'); return; }

        SAG.btnLoading('#btnGuardarCap', true);
        SAG.ajax({
            url:  '/capacitaciones/save',
            data: $('#formCapacitacion').serialize(),
            success: function (res) {
                SAG.btnLoading('#btnGuardarCap', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message);
                modalCap.hide();
                tabla.ajax.reload(null, false);
                activarCapacitacion(res.data.id);
                // Tras crear, ir directo a agregar participantes
                if (esNueva) switchTab('participantes');
            },
            error: function () { SAG.btnLoading('#btnGuardarCap', false); },
        });
    });

    // ── LIMPIAR FORMULARIO ────────────────────────────
    function limpiarFormCap() {
        document.getElementById('formCapacitacion').reset();
        $('#capId').val(0);
        $('#cMun').html('<option value="">— Seleccione departamento primero —</option>');
        $('#cSubtema').html('<option value="">— Seleccione tema primero —</option>');
    }

    // ── CHANGE DEPTO ──────────────────────────────────
    $('#cDep').on('change', function () {
        SAG.loadMunicipios($(this).val(), '#cMun');
    });

    // ── CHANGE TEMA ───────────────────────────────────
    $('#cTema').on('change', function () {
        SAG.loadSubtemas($(this).val(), '#cSubtema');
    });

    // ── MÁSCARA DNI participante ──────────────────────
    $('#pDni').on('input', function () {
        this.value = SAG.formatDNI(this.value);
    });

    // ── ACTIVAR CAPACITACIÓN (para tab participantes) ─
    function activarCapacitacion(id) {
        capActivaId = id;
        SAG.ajax({
            url:  '/capacitaciones/get',
            data: { id: id },
            success: function (res) {
                if (!res.success) return;
                capActivaData = res.data.capacitacion;
                $('#pCapId').val(id);
                actualizarBanner();
                cargarParticipantes(id);
                renderEvidenciaCap(capActivaData);
                $('#sinCapActiva').hide();
                $('#panelParticipantes').show();
                $('#capActivaBanner').show();
            },
        });
    }

    // ── R-028: Render del bloque de evidencia para la capacitación activa
    function renderEvidenciaCap(c) {
        if (!c) return;
        $('#evCapIdCap').val(c.id_capacitacion);
        const estado = c.evidencia_estado || 'pendiente';

        // Badge de estado
        const estLabels = {
            'pendiente': ['PENDIENTE', '#f1f5f9', '#6b7280'],
            'cargada':   ['CARGADA',   '#dbeafe', '#1e40af'],
            'validada':  ['VALIDADA',  '#d1fae5', '#065f46'],
            'rechazada': ['RECHAZADA', '#fee2e2', '#991b1b'],
        };
        const [lbl, bg, fg] = estLabels[estado] || estLabels.pendiente;
        $('#evCapEstadoBadge').text(lbl).css({ background: bg, color: fg });

        if (c.evidencia_archivo) {
            // Tiene archivo subido
            $('#evCapSinArchivo').hide();
            $('#evCapConArchivo').show();

            const icoMap = {
                'application/pdf':                                              ['fa-file-pdf',   '#dc2626'],
                'application/vnd.ms-excel':                                     ['fa-file-excel', '#15803d'],
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': ['fa-file-excel','#15803d'],
                'image/jpeg':                                                   ['fa-file-image', '#7c3aed'],
                'image/png':                                                    ['fa-file-image', '#7c3aed'],
            };
            const [ico, color] = icoMap[c.evidencia_mime] || ['fa-file', '#6b7280'];
            $('#evCapIcono').html(`<i class="fas ${ico}" style="color:${color};"></i>`);
            $('#evCapNombre').text(c.evidencia_nombre_original || c.evidencia_archivo);
            const tam = c.evidencia_tamano ? (Math.round(c.evidencia_tamano/1024) + ' KB') : '';
            $('#evCapMeta').text(`${tam} · subido ${c.evidencia_subida_at || '—'}`);
            if (c.evidencia_observaciones) {
                $('#evCapObsBox').text('Obs: ' + c.evidencia_observaciones).show();
            } else {
                $('#evCapObsBox').hide();
            }
            $('#evCapVerLink').attr('href', SAG.BASE_URL + '/capacitaciones/evidencia?id=' + c.id_capacitacion);
        } else {
            $('#evCapSinArchivo').show();
            $('#evCapConArchivo').hide();
        }
    }

    // Subir evidencia
    $(document).on('click', '#btnSubirEvCap', function () {
        const file = $('#evCapArchivo')[0].files[0];
        if (!file) { SAG.toast('Seleccione un archivo.', 'warning'); return; }
        if (!capActivaId) { SAG.toast('Sin capacitación activa.', 'warning'); return; }

        const fd = new FormData();
        fd.append('id_capacitacion', capActivaId);
        fd.append('archivo', file);
        fd.append('observaciones', $('#evCapObs').val());
        if (window.SAG && SAG.CSRF) fd.append('_csrf', SAG.CSRF);

        $.ajax({
            url: SAG.BASE_URL + '/capacitaciones/evidencia/subir',
            method: 'POST',
            data: fd, processData: false, contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                activarCapacitacion(capActivaId); // recarga
            },
            error: function () { SAG.toast('Error al subir el archivo.', 'error'); }
        });
    });

    // Reemplazar archivo
    $(document).on('click', '#btnReemplazarEvCap', function () {
        $('#evCapConArchivo').hide();
        $('#evCapSinArchivo').show();
        $('#evCapArchivo').val('');
    });

    // Validar / Rechazar
    function cambiarEstadoEvCap(estado) {
        const obs = (estado === 'rechazada') ? (prompt('Motivo de rechazo:') || '') : '';
        if (estado === 'rechazada' && !obs) return;
        SAG.ajax({
            url: '/capacitaciones/evidencia/validar',
            data: { id_capacitacion: capActivaId, estado: estado, observaciones: obs },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                activarCapacitacion(capActivaId);
            }
        });
    }
    $(document).on('click', '#btnValidarEvCap',  () => cambiarEstadoEvCap('validada'));
    $(document).on('click', '#btnRechazarEvCap', () => cambiarEstadoEvCap('rechazada'));

    function actualizarBanner() {
        if (!capActivaData) return;
        const c = capActivaData;
        $('#bannerCapNombre').text(c.tema + (c.subtema ? ' — ' + c.subtema : ''));
        $('#bannerCapInfo').text(
            (c.departamento || '') + ' / ' + (c.municipio || '') +
            (c.lugar_especifico ? ' · ' + c.lugar_especifico : '') +
            ' · ' + (c.fecha_capacitacion || '')
        );
    }

    // ── FINALIZAR CAPACITACIÓN ────────────────────────
    $('#btnFinalizarCap').on('click', function () {
        if (!capActivaId) return;
        SAG.confirm('¿Marcar esta capacitación como Finalizada? No podrá editarse después.', function () {
            SAG.ajax({
                url:  '/capacitaciones/finalizar',
                data: { id: capActivaId },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    if (tabla) tabla.ajax.reload();
                },
            });
        });
    });

    // ── PARTICIPANTES — CARGAR ────────────────────────
    function cargarParticipantes(idCap) {
        SAG.ajax({
            url:  '/capacitaciones/get',
            data: { id: idCap },
            success: function (res) {
                if (!res.success) return;
                renderParticipantes(res.data.participantes);
            },
        });
    }

    function renderParticipantes(lista) {
        const tbody = $('#tbodyParticipantes');
        tbody.empty();
        if (!lista || lista.length === 0) {
            tbody.html('<tr id="trSinParticipantes"><td colspan="7" style="text-align:center;color:#aaa;padding:20px;font-size:.82rem;">Sin participantes registrados</td></tr>');
            $('#bannerPartCount').text('0 participantes');
            $('#labelTotalPart').text('0 registrados');
            return;
        }
        lista.forEach(function (p, i) {
            const sexoIcon = p.sexo === 'M'
                ? '<span style="color:#2563eb;"><i class="fas fa-mars"></i></span>'
                : (p.sexo === 'F'
                    ? '<span style="color:#db2777;"><i class="fas fa-venus"></i></span>'
                    : '—');
            const fila = `<tr>
                <td>${i + 1}</td>
                <td>${escHtml(p.nombre)} ${escHtml(p.apellido || '')}</td>
                <td>${p.dni || '—'}</td>
                <td style="text-align:center;">${p.edad || '—'}</td>
                <td style="text-align:center;">${sexoIcon}</td>
                <td>${escHtml(p.organizacion || '—')}</td>
                <td style="text-align:center;">
                    <button class="btn-danger-sm btn-del-part" data-id="${p.id_participante}" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
            tbody.append(fila);
        });
        const total = lista.length;
        $('#bannerPartCount').text(total + ' participante' + (total !== 1 ? 's' : ''));
        $('#labelTotalPart').text(total + ' registrado' + (total !== 1 ? 's' : ''));
    }

    // ── PARTICIPANTES — AGREGAR ───────────────────────
    $('#btnAgregarPart').on('click', function () {
        const nombre = $('#pNombre').val().trim();
        if (!nombre)      { SAG.toast('El nombre del participante es obligatorio.', 'warning'); return; }
        if (!capActivaId) { SAG.toast('Primero guarde una capacitación.',           'warning'); return; }

        SAG.btnLoading('#btnAgregarPart', true);
        SAG.ajax({
            url:  '/capacitaciones/participante/add',
            data: $('#formParticipante').serialize(),
            success: function (res) {
                SAG.btnLoading('#btnAgregarPart', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message);
                limpiarFormPart();
                cargarParticipantes(capActivaId);
                if (tabla) tabla.ajax.reload();
            },
            error: function () { SAG.btnLoading('#btnAgregarPart', false); },
        });
    });

    // ── PARTICIPANTES — ELIMINAR ──────────────────────
    $(document).on('click', '.btn-del-part', function () {
        const id = $(this).data('id');
        SAG.confirm('¿Eliminar este participante de la lista?', function () {
            SAG.ajax({
                url:  '/capacitaciones/participante/delete',
                data: { id },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    cargarParticipantes(capActivaId);
                    if (tabla) tabla.ajax.reload();
                },
            });
        });
    });

    $('#btnLimpiarPart').on('click', limpiarFormPart);

    function limpiarFormPart() {
        document.getElementById('formParticipante').reset();
        $('#pCapId').val(capActivaId);
    }

    // ── VER DETALLE DESDE TABLA ───────────────────────
    $(document).on('click', '.btn-ver-cap', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url:  '/capacitaciones/get',
            data: { id },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const c = res.data.capacitacion;
                const p = res.data.participantes;

                const estadoHtml = c.estado === 'finalizado'
                    ? '<span class="badge-activo">Finalizado</span>'
                    : '<span class="badge-pendiente">Borrador</span>';

                let partsHtml = '<p style="color:#aaa;font-size:.82rem;">Sin participantes registrados</p>';
                if (p && p.length > 0) {
                    const filas = p.map((pp, i) => `
                        <tr>
                            <td>${i + 1}</td>
                            <td>${escHtml(pp.nombre)} ${escHtml(pp.apellido || '')}</td>
                            <td>${pp.dni || '—'}</td>
                            <td>${pp.edad || '—'}</td>
                            <td>${pp.sexo || '—'}</td>
                            <td>${escHtml(pp.organizacion || '—')}</td>
                            <td>${pp.telefono || '—'}</td>
                        </tr>`).join('');
                    partsHtml = `
                        <div style="overflow-x:auto;">
                        <table class="sag-table" style="width:100%;margin-top:0;">
                            <thead><tr>
                                <th>#</th><th>Nombre</th><th>DNI</th>
                                <th>Edad</th><th>Sexo</th><th>Organización</th><th>Teléfono</th>
                            </tr></thead>
                            <tbody>${filas}</tbody>
                        </table></div>`;
                }

                $('#modalCapBody').html(`
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><label class="form-label-b">Fecha</label>
                            <p>${c.fecha_capacitacion}</p></div>
                        <div class="col-md-4"><label class="form-label-b">Duración</label>
                            <p>${c.duracion_horas ? c.duracion_horas + ' horas' : '—'}</p></div>
                        <div class="col-md-4"><label class="form-label-b">Estado</label>
                            <p>${estadoHtml}</p></div>
                        <div class="col-md-4"><label class="form-label-b">Tema</label>
                            <p><strong>${escHtml(c.tema)}</strong></p></div>
                        <div class="col-md-4"><label class="form-label-b">Subtema</label>
                            <p>${escHtml(c.subtema || '—')}</p></div>
                        <div class="col-md-4"><label class="form-label-b">Técnico</label>
                            <p>${escHtml(c.tecnico)}</p></div>
                        <div class="col-md-6"><label class="form-label-b">Ubicación</label>
                            <p>${escHtml(c.departamento)} / ${escHtml(c.municipio)}${c.aldea ? ' / ' + escHtml(c.aldea) : ''}</p></div>
                        <div class="col-md-6"><label class="form-label-b">Lugar específico</label>
                            <p>${escHtml(c.lugar_especifico || '—')}</p></div>
                        ${c.descripcion ? `<div class="col-12"><label class="form-label-b">Descripción</label><p>${escHtml(c.descripcion)}</p></div>` : ''}
                    </div>
                    <div class="form-section-title" style="margin-top:0;">
                        <i class="fas fa-users me-1"></i>Participantes
                        <span class="badge-count ms-2">${c.num_participantes || 0}</span>
                    </div>
                    ${partsHtml}
                `);

                $('.btn-editar-desde-modal-cap, .btn-participantes-desde-modal').data('id', id);
                modalVer.show();
            },
        });
    });

    // Editar desde modal
    $(document).on('click', '.btn-editar-desde-modal-cap', function () {
        modalVer.hide();
        cargarParaEditar($(this).data('id'));
    });

    // Ir a participantes desde modal
    $(document).on('click', '.btn-participantes-desde-modal', function () {
        modalVer.hide();
        activarCapacitacion($(this).data('id'));
        switchTab('participantes');
    });

    // ── EDITAR DESDE TABLA ────────────────────────────
    $(document).on('click', '.btn-editar-cap', function () {
        cargarParaEditar($(this).data('id'));
    });

    function cargarParaEditar(id) {
        SAG.ajax({
            url:  '/capacitaciones/get',
            data: { id },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const c = res.data.capacitacion;
                limpiarFormCap();
                $('#modalCapTitulo').html('<i class="fas fa-pen me-2"></i>Editar Capacitación');
                $('#capId').val(c.id_capacitacion);
                $('#cDep').val(c.id_departamento);
                SAG.loadMunicipios(c.id_departamento, '#cMun', c.id_municipio);
                $('#cAldea').val(c.aldea);
                $('#cLugar').val(c.lugar_especifico);
                $('#cFecha').val(c.fecha_capacitacion);
                $('#cTema').val(c.id_tema);
                SAG.loadSubtemas(c.id_tema, '#cSubtema', c.id_subtema);
                $('#cDuracion').val(c.duracion_horas);
                $('#cDescripcion').val(c.descripcion);
                $('#cTecnico').val(c.id_tecnico);
                modalCap.show();
            },
        });
    }

    // ── ELIMINAR ──────────────────────────────────────
    $(document).on('click', '.btn-eliminar-cap', function () {
        const id = $(this).data('id');
        SAG.confirm('¿Desea eliminar esta capacitación y todos sus participantes?', function () {
            SAG.ajax({
                url:  '/capacitaciones/delete',
                data: { id },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    tabla.ajax.reload();
                    if (parseInt(capActivaId) === parseInt(id)) {
                        capActivaId   = 0;
                        capActivaData = null;
                        $('#panelParticipantes').hide();
                        $('#capActivaBanner').hide();
                        $('#sinCapActiva').show();
                    }
                },
            });
        });
    });

    // ── HELPER: escape HTML ───────────────────────────
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

});
