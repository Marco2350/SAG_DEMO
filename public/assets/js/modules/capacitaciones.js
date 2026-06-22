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

    // ── SELECT2 en selects largos ─────────────────────
    SAG.initSelect2('#cMun',     'Seleccione municipio', '#modalCapacitacion');
    SAG.initSelect2('#cTema',    'Seleccione tema',      '#modalCapacitacion');
    SAG.initSelect2('#cSubtema', 'Seleccione subtema',   '#modalCapacitacion');
    SAG.initSelect2('#cTecnico', 'Seleccione técnico',   '#modalCapacitacion');
    SAG.initSelect2('#pOrg',     'Sin organización'); // panel de participantes (fuera de modal)

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
        SAG.refreshSelect2('#modalCapacitacion');
    }

    // ── CHANGE DEPTO ──────────────────────────────────
    $('#cDep').on('change', function () {
        SAG.loadMunicipios($(this).val(), '#cMun');
        $('#cAldeaSelect').hide().html('<option value="">— Seleccione municipio primero —</option>');
        $('#cAldea').val('');
    });

    // ── CHANGE MUNI → cargar aldeas oficiales ─────────
    $('#cMun').on('change', function () {
        const codMuni = $(this).find('option:selected').data('codigo') || '';
        if (codMuni) {
            $('#cAldeaSelect').show();
            SAG.loadAldeas(codMuni, '#cAldeaSelect');
        } else {
            $('#cAldeaSelect').hide();
        }
    });

    $(document).on('change', '#cAldeaSelect', function () {
        const v = $(this).val();
        if (v) $('#cAldea').val(v);
    });

    // ── CHANGE TEMA ───────────────────────────────────
    $('#cTema').on('change', function () {
        SAG.loadSubtemas($(this).val(), '#cSubtema');
    });

    // ── MÁSCARA DNI participante ──────────────────────
    let participanteDniTimer;
    $('#pDni').on('input', function () {
        clearTimeout(participanteDniTimer);
        this.value = SAG.formatDNI(this.value);
        const dni = this.value.replace(/\D/g, '');
        if (dni !== participanteDniConsultado) bloquearDatosParticipante();
        if (dni.length === 13) {
            participanteDniTimer = setTimeout(buscarParticipantePorDni, 350);
        }
    });

    let participanteDniConsultado = '';

    $('#btnBuscarPartDni').on('click', buscarParticipantePorDni);
    $('#pDni').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarParticipantePorDni();
        }
    });

    function bloquearDatosParticipante() {
        participanteDniConsultado = '';
        $('#pIdentidadFuente').val('');
        $('#pNombre, #pApellido, #pEdad, #pSexo, #pOrg, #btnAgregarPart').prop('disabled', true);
        $('#pDniEstado').hide();
    }

    function habilitarDatosParticipante() {
        $('#pNombre, #pApellido, #pEdad, #pSexo, #pOrg, #btnAgregarPart').prop('disabled', false);
    }

    function estadoDniParticipante(tipo, html) {
        const estilos = {
            buscando: ['#eef2ff', '#3730a3', '#6366f1'],
            encontrado: ['#dcfce7', '#166534', '#16a34a'],
            manual: ['#fefce8', '#854d0e', '#ca8a04'],
            error: ['#fef2f2', '#991b1b', '#dc2626'],
        };
        const e = estilos[tipo];
        $('#pDniEstado').show().css({
            background: e[0], color: e[1], borderLeft: '4px solid ' + e[2],
            padding: '8px 10px', borderRadius: '7px'
        }).html(html);
    }

    function buscarParticipantePorDni() {
        const dni = ($('#pDni').val() || '').replace(/\D/g, '');
        if (dni.length !== 13) {
            estadoDniParticipante('error', '<i class="fas fa-circle-xmark"></i> Ingrese los 13 dígitos de la identidad.');
            return;
        }

        estadoDniParticipante('buscando', '<i class="fas fa-spinner fa-spin"></i> Buscando en productores registrados, entregas y CENSO...');
        $('#btnBuscarPartDni').prop('disabled', true);
        SAG.ajax({
            url: '/api/productores/buscar-dni',
            data: { dni },
            success: function (res) {
                $('#btnBuscarPartDni').prop('disabled', false);
                if (!res.success) {
                    estadoDniParticipante('error', '<i class="fas fa-circle-xmark"></i> ' + res.message);
                    return;
                }

                participanteDniConsultado = dni;
                const resultado = res.data || {};
                const p = resultado.persona;
                $('#pIdentidadFuente').val(resultado.source || 'ninguno');
                habilitarDatosParticipante();

                if (p) {
                    $('#pNombre').val(p.nombre || p.nombres || '');
                    $('#pApellido').val(p.apellido || p.apellidos || '');
                    $('#pEdad').val(p.edad || '');
                    $('#pSexo').val(p.sexo || '');
                    if (p.id_organizacion) $('#pOrg').val(p.id_organizacion).trigger('change');
                    const faltantes = [];
                    if (!(p.apellido || p.apellidos)) faltantes.push('apellido');
                    if (!p.edad) faltantes.push('edad');
                    if (!p.sexo) faltantes.push('sexo');
                    if (!p.id_organizacion) faltantes.push('organización');
                    const aviso = faltantes.length
                        ? ' Complete manualmente: <strong>' + faltantes.join(', ') + '</strong>.'
                        : ' Todos los datos disponibles fueron precargados.';
                    estadoDniParticipante('encontrado', '<i class="fas fa-circle-check"></i> ' + res.message + aviso);
                } else {
                    $('#pNombre, #pApellido, #pEdad').val('');
                    $('#pSexo, #pOrg').val('');
                    estadoDniParticipante('manual', '<i class="fas fa-user-plus"></i> ' + res.message);
                    $('#pNombre').focus();
                }
            },
            error: function () {
                $('#btnBuscarPartDni').prop('disabled', false);
                estadoDniParticipante('error', '<i class="fas fa-triangle-exclamation"></i> No fue posible consultar la identidad.');
            },
        });
    }

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
            const sexoTexto = p.sexo === 'M' ? 'Masculino' : (p.sexo === 'F' ? 'Femenino' : '—');
            const fila = `<tr>
                <td>${i + 1}</td>
                <td>${escHtml(p.nombre)} ${escHtml(p.apellido || '')}</td>
                <td>${p.dni || '—'}</td>
                <td style="text-align:center;">${p.edad || '—'}</td>
                <td style="text-align:center;">${sexoTexto}</td>
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
        const apellido = $('#pApellido').val().trim();
        const edad = $('#pEdad').val();
        const sexo = $('#pSexo').val();
        const dni = ($('#pDni').val() || '').replace(/\D/g, '');
        if (dni.length !== 13 || dni !== participanteDniConsultado) {
            SAG.toast('Busque primero la identidad del participante.', 'warning'); return;
        }
        if (!nombre)      { SAG.toast('El nombre del participante es obligatorio.', 'warning'); return; }
        if (!apellido)    { SAG.toast('El apellido del participante es obligatorio.', 'warning'); return; }
        if (!edad)        { SAG.toast('Ingrese la edad del participante.', 'warning'); return; }
        if (!sexo)        { SAG.toast('Seleccione el sexo del participante.', 'warning'); return; }
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
        bloquearDatosParticipante();
        $('#pDni').focus();
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

                // ── Tabla de participantes ──
                let partsHtml = '<p style="color:#aaa;font-size:.82rem;">Sin participantes registrados</p>';
                if (p && p.length > 0) {
                    const filas = p.map((pp, i) => {
                        const sx = pp.sexo === 'M' ? 'Masculino' : (pp.sexo === 'F' ? 'Femenino' : '—');
                        return `
                        <tr>
                            <td>${i + 1}</td>
                            <td>${escHtml(pp.nombre)} ${escHtml(pp.apellido || '')}</td>
                            <td>${pp.dni || '—'}</td>
                            <td>${pp.edad || '—'}</td>
                            <td>${sx}</td>
                            <td>${escHtml(pp.organizacion || '—')}</td>
                            <td>${pp.telefono || '—'}</td>
                        </tr>`;
                    }).join('');
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

                // ── Comprobante / Evidencia documental ──
                const estLabels = {
                    'pendiente': ['PENDIENTE', '#f1f5f9', '#6b7280'],
                    'cargada':   ['CARGADA',   '#dbeafe', '#1e40af'],
                    'validada':  ['VALIDADA',  '#d1fae5', '#065f46'],
                    'rechazada': ['RECHAZADA', '#fee2e2', '#991b1b'],
                };
                let comprobanteHtml;
                if (c.evidencia_archivo) {
                    const est = c.evidencia_estado || 'cargada';
                    const [lbl, bg, fg] = estLabels[est] || estLabels.cargada;
                    const icoMap = {
                        'application/pdf':                                                  ['fa-file-pdf',   '#dc2626'],
                        'application/vnd.ms-excel':                                         ['fa-file-excel', '#15803d'],
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':['fa-file-excel', '#15803d'],
                        'image/jpeg':                                                       ['fa-file-image', '#7c3aed'],
                        'image/png':                                                        ['fa-file-image', '#7c3aed'],
                    };
                    const [ico, color] = icoMap[c.evidencia_mime] || ['fa-file', '#6b7280'];
                    const url   = SAG.BASE_URL + '/capacitaciones/evidencia?id=' + c.id_capacitacion;
                    const tam   = c.evidencia_tamano ? Math.round(c.evidencia_tamano / 1024) + ' KB' : '';
                    const meta  = [tam, c.evidencia_subida_at ? 'subido ' + c.evidencia_subida_at : ''].filter(Boolean).join(' · ');
                    const esPdf = c.evidencia_mime === 'application/pdf';
                    const esImg = (c.evidencia_mime || '').indexOf('image/') === 0;

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
                                <div style="font-weight:700;font-size:.88rem;word-break:break-word;">${escHtml(c.evidencia_nombre_original || c.evidencia_archivo)}</div>
                                <div style="font-size:.74rem;color:#6b7280;">${escHtml(meta) || '—'}</div>
                                ${c.evidencia_observaciones ? `<div style="font-size:.76rem;color:#555;margin-top:3px;">Obs: ${escHtml(c.evidencia_observaciones)}</div>` : ''}
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
                            No se ha adjuntado comprobante / evidencia para esta capacitación.
                        </div>`;
                }

                $('#modalCapBody').html(`
                    <!-- Banner -->
                    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;border-radius:10px;background:linear-gradient(135deg,#f0fdf4 0%,#eff6ff 100%);border:1px solid #e5e7eb;">
                        <div style="min-width:0;">
                            <div style="font-size:1.05rem;font-weight:800;color:#111827;">
                                <i class="fas fa-chalkboard-user me-1" style="color:var(--primario-oscuro);"></i>${escHtml(c.tema)}${c.subtema ? ' — ' + escHtml(c.subtema) : ''}
                            </div>
                            <div style="font-size:.8rem;color:#6b7280;margin-top:2px;">
                                <i class="fas fa-calendar-day me-1"></i>${c.fecha_capacitacion || '—'}${c.duracion_horas ? ' · ' + c.duracion_horas + ' h' : ''}
                            </div>
                        </div>
                        <div>${estadoHtml}</div>
                    </div>

                    ${seccion('fa-location-dot', '#1e40af', 'Ubicación y Detalle')}
                    <div class="row g-3">
                        ${campo('Ubicación', `${escHtml(c.departamento)} / ${escHtml(c.municipio)}${c.aldea ? ' / ' + escHtml(c.aldea) : ''}`, 'col-md-5')}
                        ${campo('Lugar específico', escHtml(c.lugar_especifico), 'col-md-4')}
                        ${campo('Técnico responsable', escHtml(c.tecnico), 'col-md-3')}
                        ${c.descripcion ? campo('Descripción', `<span style="white-space:pre-wrap;">${escHtml(c.descripcion)}</span>`, 'col-12') : ''}
                    </div>

                    ${seccion('fa-users', '#d97706', 'Participantes (' + (c.num_participantes || 0) + ')')}
                    ${partsHtml}

                    ${seccion('fa-paperclip', '#0d9488', 'Comprobante / Evidencia')}
                    ${comprobanteHtml}
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
                SAG.refreshSelect2('#modalCapacitacion');
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
