/**
 * asistencia_tecnica.js — Módulo de Asistencia Técnica
 * SAG Programas — sag_programas
 */
$(function () {

    let tabla;
    const modalVer  = new bootstrap.Modal('#modalVerAT');
    const modalForm = new bootstrap.Modal('#modalAT');

    // ── DATATABLES ────────────────────────────────────
    function initTabla() {
        tabla = $('#tablaAT').DataTable({
            processing: true,
            ajax: {
                url:  SAG.BASE_URL + '/asistencia/listar',
                type: 'POST',
                data: function (d) {
                    d.id_departamento = $('#filtroDepAT').val();
                    d.id_tipo_at      = $('#filtroTipoAT').val();
                    d.id_tema         = $('#filtroTemaAT').val();
                    d.id_tecnico      = $('#filtroTecAT').val();
                    d.estado          = $('#filtroEstadoAT').val();
                },
                dataSrc: 'data',
            },
            columns: [
                { data: 'id_at',      width: '40px' },
                { data: 'fecha' },
                { data: 'tipo_at' },
                { data: 'productor' },
                { data: 'tema' },
                { data: 'cultivo' },
                { data: 'tecnico' },
                { data: 'ubicacion' },
                { data: 'prox_visita' },
                { data: 'estado',   orderable: false, className: 'text-center' },
                { data: 'acciones', orderable: false, className: 'text-center' },
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
            order:      [[1, 'desc']],
            pageLength: 15,
        });
    }

    initTabla();

    // ── SELECT2 en selects largos del modal ───────────
    SAG.initSelect2('#atMun',     'Seleccione municipio', '#modalAT');
    SAG.initSelect2('#atTema',    'Seleccione tema',      '#modalAT');
    SAG.initSelect2('#atSubtema', 'Seleccione subtema',   '#modalAT');
    SAG.initSelect2('#atTecnico', 'Seleccione técnico',   '#modalAT');
    SAG.initSelect2('#atCultivo', 'Sin especificar',      '#modalAT');
    SAG.initSelect2('#atOrg',     'Sin organización',     '#modalAT');

    $('#btnFiltrarAT').on('click', function () {
        tabla.ajax.reload();
    });
    $('#filtroDepAT, #filtroTipoAT, #filtroTemaAT, #filtroTecAT, #filtroEstadoAT').on('change', function () {
        tabla.ajax.reload();
    });

    // ── ABRIR MODAL NUEVA ─────────────────────────────
    $('#btnNuevaAT').on('click', function () {
        limpiarFormAT();
        $('#modalATTitulo').html('<i class="fas fa-file-medical me-2"></i>Nueva Visita Técnica');
        modalForm.show();
    });

    // ── MÁSCARAS DE ENTRADA ───────────────────────────
    let atDniConsultado = '';

    let atDniTimer;
    $('#atPDni').on('input', function () {
        clearTimeout(atDniTimer);
        this.value = SAG.formatDNI(this.value);
        if (this.value.replace(/\D/g, '') !== atDniConsultado) {
            atDniConsultado = '';
            $('#atDniEstado').hide();
        }
        if (this.value.replace(/\D/g, '').length === 13) {
            atDniTimer = setTimeout(buscarProductorAt, 350);
        }
    });
    $('#btnBuscarAtDni').on('click', buscarProductorAt);

    function buscarProductorAt() {
        const dni = ($('#atPDni').val() || '').replace(/\D/g, '');
        if (dni.length !== 13) {
            SAG.toast('Ingrese los 13 dígitos de la identidad.', 'warning'); return;
        }
        $('#atDniEstado').show().css({ background: '#eef2ff', color: '#3730a3', borderLeft: '4px solid #6366f1' })
            .html('<i class="fas fa-spinner fa-spin"></i> Buscando en productores registrados, entregas y CENSO...');
        $('#btnBuscarAtDni').prop('disabled', true);
        SAG.ajax({
            url: '/api/productores/buscar-dni',
            data: { dni },
            success: function (res) {
                $('#btnBuscarAtDni').prop('disabled', false);
                if (!res.success) {
                    $('#atDniEstado').css({ background: '#fef2f2', color: '#991b1b', borderLeft: '4px solid #dc2626' }).text(res.message);
                    return;
                }
                atDniConsultado = dni;
                const p = res.data?.persona;
                if (p) {
                    $('#atPNombre').val(p.nombre || p.nombres || '');
                    $('#atPApellido').val(p.apellido || p.apellidos || '');
                    $('#atPEdad').val(p.edad || '');
                    $('#atPSexo').val(p.sexo || '');
                    $('#atPTel').val(SAG.formatTel(p.telefono || ''));
                    if (p.id_organizacion) $('#atOrg').val(p.id_organizacion).trigger('change');
                    $('#atDniEstado').css({ background: '#dcfce7', color: '#166534', borderLeft: '4px solid #16a34a' })
                        .html('<i class="fas fa-circle-check"></i> ' + res.message + ' Datos precargados.');
                } else {
                    $('#atDniEstado').css({ background: '#fefce8', color: '#854d0e', borderLeft: '4px solid #ca8a04' })
                        .html('<i class="fas fa-user-plus"></i> ' + res.message);
                    $('#atPNombre').focus();
                }
            },
            error: function () {
                $('#btnBuscarAtDni').prop('disabled', false);
                $('#atDniEstado').css({ background: '#fef2f2', color: '#991b1b', borderLeft: '4px solid #dc2626' }).text('No fue posible consultar la identidad.');
            },
        });
    }
    $('#atPTel').on('input', function () {
        this.value = SAG.formatTel(this.value);
    });

    // ── GUARDAR VISITA (crear / editar) ───────────────
    $('#btnGuardarAT').on('click', function () {
        const tipo   = $('#atTipo').val();
        const dep    = $('#atDep').val();
        const mun    = $('#atMun').val();
        const tema   = $('#atTema').val();
        const tec    = $('#atTecnico').val();
        const fecha  = $('#atFecha').val();
        const nombre = $('#atPNombre').val().trim();
        const dni = ($('#atPDni').val() || '').replace(/\D/g, '');
        const esIndividual = $('#atBloqueIndividual').is(':visible');

        if (!tipo)   { SAG.toast('Seleccione el tipo de asistencia.',       'warning'); return; }
        if (!dep)    { SAG.toast('Seleccione un departamento.',             'warning'); return; }
        if (!mun)    { SAG.toast('Seleccione un municipio.',               'warning'); return; }
        if (!tema)   { SAG.toast('Seleccione el tema técnico.',            'warning'); return; }
        if (!tec)    { SAG.toast('Seleccione el técnico responsable.',     'warning'); return; }
        if (!fecha)  { SAG.toast('Ingrese la fecha de la visita.',         'warning'); return; }
        if (esIndividual && !nombre) { SAG.toast('El nombre del productor es obligatorio.','warning'); return; }
        if (esIndividual && (dni.length !== 13 || dni !== atDniConsultado)) {
            SAG.toast('Busque primero la identidad del productor.', 'warning'); return;
        }

        SAG.btnLoading('#btnGuardarAT', true);
        SAG.ajax({
            url:  '/asistencia/save',
            data: $('#formAT').serialize(),
            success: function (res) {
                if (!res.success) { SAG.btnLoading('#btnGuardarAT', false); SAG.toast(res.message, 'error'); return; }

                const nuevoId   = (res.data && (res.data.id_at || res.data.id)) || $('#atId').val();
                const fileInput = document.getElementById('evATArchivo');
                const fichaPend = $('#evATSinArchivo').is(':visible') && fileInput && fileInput.files.length > 0;

                const cerrar = (msg) => {
                    SAG.btnLoading('#btnGuardarAT', false);
                    SAG.toast(msg || res.message);
                    modalForm.hide();
                    tabla.ajax.reload(null, false); // mantener la página actual
                };

                // Si el usuario adjuntó una ficha técnica pero no la subió aún, súbela automáticamente.
                if (fichaPend && nuevoId && nuevoId != '0') {
                    $('#atId').val(nuevoId);
                    $('#evATIdAt').val(nuevoId);
                    subirFichaAT(nuevoId, fileInput.files[0], $('#evATObs').val(), function (ok) {
                        cerrar(ok ? 'Visita y ficha técnica guardadas correctamente.' : res.message);
                    });
                } else {
                    cerrar();
                }
            },
            error: function () { SAG.btnLoading('#btnGuardarAT', false); },
        });
    });

    // ── LIMPIAR FORMULARIO ────────────────────────────
    function limpiarFormAT() {
        document.getElementById('formAT').reset();
        $('#atId').val(0);
        atDniConsultado = '';
        $('#atDniEstado').hide();
        $('#atMun').html('<option value="">— Seleccione departamento primero —</option>');
        $('#atSubtema').html('<option value="">— Seleccione tema primero —</option>');
        // Resetear bloque de Ficha Técnica al estado "visita nueva"
        // (Antes se deshabilitaba hasta guardar la visita; ahora siempre habilitado)
        $('#evATIdAt').val(0);
        $('#evATArchivo, #evATObs, #btnSubirEvAT').prop('disabled', false);
        $('#evATArchivo').val('');
        $('#evATAviso').show();
        $('#evATSinArchivo').show();
        $('#evATConArchivo').hide();
        $('#evATEstadoBadge').text('PENDIENTE').css({ background: '#f1f5f9', color: '#6b7280' });
        SAG.refreshSelect2('#modalAT');
    }

    // ── CHANGE TIPO → alternar individual vs grupal ────
    $('#atTipo').on('change', function () {
        const $opt = $(this).find('option:selected');
        const esGrupal = $opt.data('grupal') == 1 || $opt.attr('data-grupal') === '1';
        if (esGrupal) {
            $('#atBloqueIndividual').hide();
            $('#atBloqueGrupal').show();
            // Limpiar campos individuales para que no manden datos huérfanos
            $('#atPNombre, #atPApellido, #atPDni, #atPEdad, #atPTel, #atArea').val('');
            $('#atPSexo').val('');
        } else {
            $('#atBloqueGrupal').hide();
            $('#atBloqueIndividual').show();
            $('#atGrTotal, #atGrHombres, #atGrMujeres, #atGrLista').val('');
        }
    });

    // Recalcular total grupo cuando cambien hombres o mujeres
    $('#atGrHombres, #atGrMujeres').on('input', function () {
        const h = parseInt($('#atGrHombres').val()) || 0;
        const m = parseInt($('#atGrMujeres').val()) || 0;
        if (h || m) $('#atGrTotal').val(h + m);
    });

    // ── CHANGE DEPTO ──────────────────────────────────
    $('#atDep').on('change', function () {
        SAG.loadMunicipios($(this).val(), '#atMun');
        $('#atAldeaSelect').hide().html('<option value="">— Seleccione municipio primero —</option>');
        $('#atAldea').val('');
    });

    // ── CHANGE MUNI → cargar aldeas oficiales ─────────
    $('#atMun').on('change', function () {
        const codMuni = $(this).find('option:selected').data('codigo') || '';
        if (codMuni) {
            $('#atAldeaSelect').show();
            SAG.loadAldeas(codMuni, '#atAldeaSelect');
        } else {
            $('#atAldeaSelect').hide();
        }
    });

    $(document).on('change', '#atAldeaSelect', function () {
        const v = $(this).val();
        if (v) $('#atAldea').val(v);
    });

    // ── CHANGE TEMA ───────────────────────────────────
    $('#atTema').on('change', function () {
        SAG.loadSubtemas($(this).val(), '#atSubtema');
    });

    // ── VER DETALLE ───────────────────────────────────
    $(document).on('click', '.btn-ver-at', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url:  '/asistencia/get',
            data: { id },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const a = res.data;

                const estadoHtml = a.estado === 'finalizado'
                    ? '<span class="badge-activo">Finalizado</span>'
                    : '<span class="badge-pendiente">Borrador</span>';

                const sexoTxt = a.productor_sexo === 'M' ? 'Masculino'
                    : (a.productor_sexo === 'F' ? 'Femenino' : '');

                // ── Helpers de presentación ──
                const seccion = (icono, color, titulo) => `
                    <div style="display:flex;align-items:center;gap:8px;padding:7px 12px;border-left:4px solid ${color};background:${color}12;border-radius:4px;margin:18px 0 12px;font-weight:700;font-size:.82rem;color:#374151;">
                        <i class="fas ${icono}" style="color:${color};"></i>${titulo}
                    </div>`;
                const campo = (label, valor, col = 'col-md-4') => `
                    <div class="${col}">
                        <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px;">${label}</div>
                        <div style="font-size:.9rem;color:#111827;">${(valor === 0 || valor) ? valor : '<span style="color:#cbd5e1;">—</span>'}</div>
                    </div>`;

                // ── Resultados ──
                let resultadosHtml;
                if (a.resultados && a.resultados.length > 0) {
                    resultadosHtml = `<ul style="margin:0;padding-left:18px;">${a.resultados.map(r => `<li style="margin-bottom:4px;">${escHtml(r.resultado)}</li>`).join('')}</ul>`;
                } else {
                    resultadosHtml = '<span style="color:#aaa;font-size:.82rem;">Sin resultados registrados</span>';
                }

                // ── Comprobante / Ficha Técnica ──
                const estLabels = {
                    'pendiente': ['PENDIENTE', '#f1f5f9', '#6b7280'],
                    'cargada':   ['CARGADA',   '#dbeafe', '#1e40af'],
                    'validada':  ['VALIDADA',  '#d1fae5', '#065f46'],
                    'rechazada': ['RECHAZADA', '#fee2e2', '#991b1b'],
                };
                let comprobanteHtml;
                if (a.evidencia_archivo) {
                    const est = a.evidencia_estado || 'cargada';
                    const [lbl, bg, fg] = estLabels[est] || estLabels.cargada;
                    const icoMap = {
                        'application/pdf':                                                  ['fa-file-pdf',   '#dc2626'],
                        'application/vnd.ms-excel':                                         ['fa-file-excel', '#15803d'],
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':['fa-file-excel', '#15803d'],
                        'image/jpeg':                                                       ['fa-file-image', '#7c3aed'],
                        'image/png':                                                        ['fa-file-image', '#7c3aed'],
                    };
                    const [ico, color] = icoMap[a.evidencia_mime] || ['fa-file', '#6b7280'];
                    const url   = SAG.BASE_URL + '/asistencia/evidencia?id=' + a.id_at;
                    const tam   = a.evidencia_tamano ? Math.round(a.evidencia_tamano / 1024) + ' KB' : '';
                    const meta  = [tam, a.evidencia_subida_at ? 'subido ' + a.evidencia_subida_at : ''].filter(Boolean).join(' · ');
                    const esPdf = a.evidencia_mime === 'application/pdf';
                    const esImg = (a.evidencia_mime || '').indexOf('image/') === 0;

                    let preview = '';
                    if (esPdf) {
                        preview = `<iframe src="${url}#toolbar=1&view=FitH" title="Comprobante PDF" style="width:100%;height:480px;border:1px solid #e5e7eb;border-radius:8px;margin-top:12px;background:#fff;"></iframe>`;
                    } else if (esImg) {
                        preview = `<div style="margin-top:12px;text-align:center;"><img src="${url}" alt="Comprobante" style="max-width:100%;max-height:480px;border:1px solid #e5e7eb;border-radius:8px;"/></div>`;
                    } else {
                        preview = `<div style="margin-top:10px;font-size:.78rem;color:#6b7280;"><i class="fas fa-circle-info me-1"></i>Vista previa no disponible para este formato. Use el botón para abrir el archivo.</div>`;
                    }

                    comprobanteHtml = `
                        <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;">
                            <div style="font-size:2rem;color:${color};"><i class="fas ${ico}"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:700;font-size:.88rem;word-break:break-word;">${escHtml(a.evidencia_nombre_original || a.evidencia_archivo)}</div>
                                <div style="font-size:.74rem;color:#6b7280;">${escHtml(meta) || '—'}</div>
                                ${a.evidencia_observaciones ? `<div style="font-size:.76rem;color:#555;margin-top:3px;">Obs: ${escHtml(a.evidencia_observaciones)}</div>` : ''}
                            </div>
                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                                <span style="font-size:.66rem;padding:3px 10px;border-radius:12px;font-weight:700;background:${bg};color:${fg};">${lbl}</span>
                                <a href="${url}" target="_blank" rel="noopener" class="btn-primario btn-sm" style="white-space:nowrap;">
                                    <i class="fas fa-up-right-from-square me-1"></i>Ver comprobante
                                </a>
                            </div>
                        </div>
                        ${preview}`;
                } else {
                    comprobanteHtml = `
                        <div style="display:flex;align-items:center;gap:10px;padding:16px;background:#fafafa;border:1.5px dashed #d1d5db;border-radius:8px;color:#9ca3af;font-size:.85rem;">
                            <i class="fas fa-file-circle-xmark" style="font-size:1.3rem;"></i>
                            No se ha adjuntado comprobante / ficha técnica para esta visita.
                        </div>`;
                }

                $('#modalATBody').html(`
                    <!-- Banner -->
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-radius:10px;background:linear-gradient(135deg,#f0fdf4 0%,#eff6ff 100%);border:1px solid #e5e7eb;">
                        <div style="min-width:0;">
                            <div style="font-size:1.05rem;font-weight:800;color:#111827;">
                                <i class="fas fa-tag me-1" style="color:var(--primario-oscuro);"></i>${escHtml(a.tipo_at)}
                            </div>
                            <div style="font-size:.8rem;color:#6b7280;margin-top:2px;">
                                <i class="fas fa-calendar-day me-1"></i>${a.fecha_visita || '—'}${a.hora_visita ? ' · ' + a.hora_visita : ''}
                            </div>
                        </div>
                        <div>${estadoHtml}</div>
                    </div>

                    ${seccion('fa-user', '#d97706', 'Productor / Grupo')}
                    <div class="row g-3">
                        ${campo('Productor', `<strong>${escHtml(a.productor_nombre)} ${escHtml(a.productor_apellido || '')}</strong>`)}
                        ${campo('DNI', a.productor_dni, 'col-md-2')}
                        ${campo('Edad', a.productor_edad, 'col-md-2')}
                        ${campo('Sexo', sexoTxt, 'col-md-2')}
                        ${campo('Teléfono', a.productor_telefono, 'col-md-2')}
                        ${campo('Organización', escHtml(a.organizacion))}
                        ${campo('Área Productiva', a.area_productiva ? a.area_productiva + ' mz' : '')}
                        ${campo('Cultivo / Rubro', escHtml(a.cultivo))}
                    </div>

                    ${seccion('fa-location-dot', '#1e40af', 'Ubicación y Seguimiento')}
                    <div class="row g-3">
                        ${campo('Ubicación', `${escHtml(a.departamento)} / ${escHtml(a.municipio)}${a.aldea ? ' / ' + escHtml(a.aldea) : ''}`, 'col-md-5')}
                        ${campo('Técnico responsable', escHtml(a.tecnico), 'col-md-4')}
                        ${campo('Próxima Visita', a.prox_visita ? `<span style="color:var(--primario-oscuro);font-weight:600;">${a.prox_visita}</span>` : '', 'col-md-3')}
                    </div>

                    ${seccion('fa-seedling', '#16a34a', 'Contenido Técnico')}
                    <div class="row g-3">
                        ${campo('Tema', `${escHtml(a.tema)}${a.subtema ? ' — ' + escHtml(a.subtema) : ''}`, 'col-12')}
                        ${a.descripcion ? campo('Descripción', `<span style="white-space:pre-wrap;">${escHtml(a.descripcion)}</span>`, 'col-12') : ''}
                        <div class="col-12">
                            <div style="font-size:.68rem;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:2px;">
                                <i class="fas fa-list-check me-1" style="color:var(--primario-oscuro);"></i>Resultados / Logros
                            </div>
                            <div style="margin-top:4px;">${resultadosHtml}</div>
                        </div>
                        ${a.observaciones ? campo('Observaciones', `<span style="white-space:pre-wrap;">${escHtml(a.observaciones)}</span>`, 'col-12') : ''}
                    </div>

                    ${seccion('fa-paperclip', '#0d9488', 'Comprobante / Ficha Técnica')}
                    ${comprobanteHtml}
                `);

                // Mostrar/ocultar botón Finalizar
                const $btnFin = $('.btn-finalizar-desde-modal');
                if (a.estado === 'borrador') {
                    $btnFin.show().data('id', id);
                } else {
                    $btnFin.hide();
                }
                $('.btn-editar-desde-modal-at').data('id', id);
                modalVer.show();
            },
        });
    });

    // Editar desde modal
    $(document).on('click', '.btn-editar-desde-modal-at', function () {
        modalVer.hide();
        cargarParaEditar($(this).data('id'));
    });

    // Finalizar desde modal
    $(document).on('click', '.btn-finalizar-desde-modal', function () {
        const id = $(this).data('id');
        modalVer.hide();
        finalizarAT(id);
    });

    // ── EDITAR DESDE TABLA ────────────────────────────
    $(document).on('click', '.btn-editar-at', function () {
        cargarParaEditar($(this).data('id'));
    });

    function cargarParaEditar(id) {
        SAG.ajax({
            url:  '/asistencia/get',
            data: { id },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const a = res.data;
                limpiarFormAT();
                $('#modalATTitulo').html('<i class="fas fa-pen me-2"></i>Editar Visita Técnica');
                $('#atId').val(a.id_at);
                $('#atTipo').val(a.id_tipo_at);
                $('#atFecha').val(a.fecha_visita);
                $('#atHora').val(a.hora_visita);
                $('#atDuracion').val(a.duracion);
                $('#atDep').val(a.id_departamento);
                SAG.loadMunicipios(a.id_departamento, '#atMun', a.id_municipio);
                $('#atAldea').val(a.aldea);
                $('#atPNombre').val(a.productor_nombre);
                $('#atPApellido').val(a.productor_apellido);
                $('#atPDni').val(SAG.formatDNI(a.productor_dni || ''));
                atDniConsultado = (a.productor_dni || '').replace(/\D/g, '');
                $('#atPEdad').val(a.productor_edad);
                $('#atPSexo').val(a.productor_sexo);
                $('#atPTel').val(SAG.formatTel(a.productor_telefono || ''));
                $('#atOrg').val(a.id_organizacion);
                $('#atArea').val(a.area_productiva);
                $('#atTema').val(a.id_tema);
                SAG.loadSubtemas(a.id_tema, '#atSubtema', a.id_subtema);
                $('#atCultivo').val(a.id_cultivo);
                $('#atDescripcion').val(a.descripcion);
                if (a.resultados && a.resultados.length > 0) {
                    $('#atResultados').val(a.resultados.map(r => r.resultado).join('\n'));
                }
                $('#atTecnico').val(a.id_tecnico);
                $('#atProxVisita').val(a.prox_visita);
                $('#atObservaciones').val(a.observaciones);
                renderEvidenciaAT(a);
                SAG.refreshSelect2('#modalAT');
                modalForm.show();
            },
        });
    }

    // ══ R-027: EVIDENCIA DOCUMENTAL ══
    function renderEvidenciaAT(a) {
        if (!a) return;
        $('#evATIdAt').val(a.id_at);
        // Habilitar inputs porque la visita ya existe
        $('#evATArchivo, #evATObs, #btnSubirEvAT').prop('disabled', false);
        $('#evATAviso').hide();

        const estado = a.evidencia_estado || 'pendiente';
        const estLabels = {
            'pendiente': ['PENDIENTE', '#f1f5f9', '#6b7280'],
            'cargada':   ['CARGADA',   '#dbeafe', '#1e40af'],
            'validada':  ['VALIDADA',  '#d1fae5', '#065f46'],
            'rechazada': ['RECHAZADA', '#fee2e2', '#991b1b'],
        };
        const [lbl, bg, fg] = estLabels[estado] || estLabels.pendiente;
        $('#evATEstadoBadge').text(lbl).css({ background: bg, color: fg });

        if (a.evidencia_archivo) {
            $('#evATSinArchivo').hide();
            $('#evATConArchivo').show();

            const icoMap = {
                'application/pdf':                                                  ['fa-file-pdf',   '#dc2626'],
                'application/vnd.ms-excel':                                         ['fa-file-excel', '#15803d'],
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':['fa-file-excel', '#15803d'],
                'image/jpeg':                                                       ['fa-file-image', '#7c3aed'],
                'image/png':                                                        ['fa-file-image', '#7c3aed'],
            };
            const [ico, color] = icoMap[a.evidencia_mime] || ['fa-file', '#6b7280'];
            $('#evATIcono').html(`<i class="fas ${ico}" style="color:${color};"></i>`);
            $('#evATNombre').text(a.evidencia_nombre_original || a.evidencia_archivo);
            const tam = a.evidencia_tamano ? (Math.round(a.evidencia_tamano/1024) + ' KB') : '';
            $('#evATMeta').text(`${tam} · subido ${a.evidencia_subida_at || '—'}`);
            if (a.evidencia_observaciones) {
                $('#evATObsBox').text('Obs: ' + a.evidencia_observaciones).show();
            } else {
                $('#evATObsBox').hide();
            }
            $('#evATVerLink').attr('href', SAG.BASE_URL + '/asistencia/evidencia?id=' + a.id_at);
        } else {
            $('#evATSinArchivo').show();
            $('#evATConArchivo').hide();
        }
    }

    // Sube el archivo de Ficha Técnica de una visita YA guardada (idAt válido).
    // onDone(ok) se invoca al terminar.
    function subirFichaAT(idAt, file, obs, onDone) {
        const fd = new FormData();
        fd.append('id_at', idAt);
        fd.append('archivo', file);
        fd.append('observaciones', obs || '');
        if (window.SAG && SAG.CSRF) fd.append('_csrf', SAG.CSRF);
        $.ajax({
            url: SAG.BASE_URL + '/asistencia/evidencia/subir',
            method: 'POST',
            data: fd, processData: false, contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); if (onDone) onDone(false); return; }
                if (onDone) onDone(true);
            },
            error: function () { SAG.toast('Error al subir el archivo.', 'error'); if (onDone) onDone(false); }
        });
    }

    $(document).on('click', '#btnSubirEvAT', function () {
        const file = $('#evATArchivo')[0].files[0];
        const id   = $('#evATIdAt').val();
        if (!file) { SAG.toast('Seleccione un archivo.', 'warning'); return; }
        const obs  = $('#evATObs').val();

        // Tras subir con éxito: refrescar el bloque de evidencia y la tabla.
        const trasSubir = (idAt) => (ok) => {
            if (!ok) return;
            SAG.toast('Ficha técnica subida correctamente.', 'success');
            cargarParaEditar(idAt);
            tabla.ajax.reload(null, false);
        };

        // Si la visita aún no fue guardada, guardarla primero y luego subir
        if (!id || id == '0') {
            SAG.toast('Guardando visita y luego subiendo Ficha Técnica…', 'info');
            SAG.ajax({
                url:  '/asistencia/save',
                data: $('#formAT').serialize(),
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    const nuevoId = (res.data && (res.data.id_at || res.data.id)) || null;
                    if (!nuevoId) {
                        SAG.toast('Visita guardada pero no se obtuvo el ID. Reintente subir la ficha.', 'warning');
                        return;
                    }
                    $('#atId').val(nuevoId);
                    $('#evATIdAt').val(nuevoId);
                    subirFichaAT(nuevoId, file, obs, trasSubir(nuevoId));
                },
                error: function () { SAG.toast('Error al guardar la visita.', 'error'); }
            });
            return;
        }

        // Visita ya guardada → upload directo
        subirFichaAT(id, file, obs, trasSubir(id));
    });

    $(document).on('click', '#btnReemplazarEvAT', function () {
        $('#evATConArchivo').hide();
        $('#evATSinArchivo').show();
        $('#evATArchivo').val('');
    });

    function cambiarEstadoEvAT(estado) {
        const id  = $('#evATIdAt').val();
        const obs = (estado === 'rechazada') ? (prompt('Motivo de rechazo:') || '') : '';
        if (estado === 'rechazada' && !obs) return;
        SAG.ajax({
            url: '/asistencia/evidencia/validar',
            data: { id_at: id, estado: estado, observaciones: obs },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                cargarParaEditar(id);
            }
        });
    }
    $(document).on('click', '#btnValidarEvAT',  () => cambiarEstadoEvAT('validada'));
    $(document).on('click', '#btnRechazarEvAT', () => cambiarEstadoEvAT('rechazada'));

    // ── FINALIZAR ─────────────────────────────────────
    function finalizarAT(id) {
        SAG.confirm('¿Marcar esta visita como Finalizada?', function () {
            SAG.ajax({
                url:  '/asistencia/finalizar',
                data: { id },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    if (tabla) tabla.ajax.reload();
                },
            });
        });
    }

    // ── ELIMINAR ──────────────────────────────────────
    $(document).on('click', '.btn-eliminar-at', function () {
        const id = $(this).data('id');
        SAG.confirm('¿Desea eliminar esta visita de asistencia técnica?', function () {
            SAG.ajax({
                url:  '/asistencia/delete',
                data: { id },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    tabla.ajax.reload();
                },
            });
        });
    });

    // ── HELPER ────────────────────────────────────────
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

});
