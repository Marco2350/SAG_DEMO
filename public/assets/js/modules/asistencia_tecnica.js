/**
 * asistencia_tecnica.js — Módulo de Asistencia Técnica
 * SAG Programas — sag_programas
 */
$(function () {

    let tabla;
    const modalVer = new bootstrap.Modal('#modalVerAT');

    // ── TABS ──────────────────────────────────────────
    window.switchTab = function (tab) {
        ['nueva', 'listado'].forEach(t => {
            document.getElementById('tab-' + t).style.display = (t === tab) ? 'block' : 'none';
        });
        document.querySelectorAll('.mode-tab').forEach((btn, i) => {
            btn.classList.toggle('active',
                (tab === 'nueva'   && i === 0) ||
                (tab === 'listado' && i === 1)
            );
        });
        if (tab === 'listado' && !tabla) initTabla();
    };

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

    $('#btnFiltrarAT').on('click', function () {
        if (tabla) tabla.ajax.reload();
        else { initTabla(); switchTab('listado'); }
    });

    // ── GUARDAR VISITA ────────────────────────────────
    $('#btnGuardarAT').on('click', function () {
        const tipo   = $('#atTipo').val();
        const dep    = $('#atDep').val();
        const mun    = $('#atMun').val();
        const tema   = $('#atTema').val();
        const tec    = $('#atTecnico').val();
        const fecha  = $('#atFecha').val();
        const nombre = $('#atPNombre').val().trim();

        if (!tipo)   { SAG.toast('Seleccione el tipo de asistencia.',       'warning'); return; }
        if (!dep)    { SAG.toast('Seleccione un departamento.',             'warning'); return; }
        if (!mun)    { SAG.toast('Seleccione un municipio.',               'warning'); return; }
        if (!tema)   { SAG.toast('Seleccione el tema técnico.',            'warning'); return; }
        if (!tec)    { SAG.toast('Seleccione el técnico responsable.',     'warning'); return; }
        if (!fecha)  { SAG.toast('Ingrese la fecha de la visita.',         'warning'); return; }
        if (!nombre) { SAG.toast('El nombre del productor es obligatorio.','warning'); return; }

        SAG.btnLoading('#btnGuardarAT', true);
        SAG.ajax({
            url:  '/asistencia/save',
            data: $('#formAT').serialize(),
            success: function (res) {
                SAG.btnLoading('#btnGuardarAT', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message);
                limpiarFormAT();
                if (tabla) tabla.ajax.reload();
            },
            error: function () { SAG.btnLoading('#btnGuardarAT', false); },
        });
    });

    // ── LIMPIAR FORMULARIO ────────────────────────────
    $('#btnLimpiarAT').on('click', limpiarFormAT);

    function limpiarFormAT() {
        document.getElementById('formAT').reset();
        $('#atId').val(0);
        $('#atMun').html('<option value="">— Seleccione departamento primero —</option>');
        $('#atSubtema').html('<option value="">— Seleccione tema primero —</option>');
    }

    // ── CHANGE DEPTO ──────────────────────────────────
    $('#atDep').on('change', function () {
        SAG.loadMunicipios($(this).val(), '#atMun');
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

                const sexoHtml = a.productor_sexo === 'M'
                    ? '<span style="color:#2563eb;"><i class="fas fa-mars"></i> Masculino</span>'
                    : (a.productor_sexo === 'F'
                        ? '<span style="color:#db2777;"><i class="fas fa-venus"></i> Femenino</span>'
                        : '—');

                let resultadosHtml = '';
                if (a.resultados && a.resultados.length > 0) {
                    const items = a.resultados.map(r =>
                        `<li style="margin-bottom:4px;">${escHtml(r.resultado)}</li>`
                    ).join('');
                    resultadosHtml = `<ul style="margin:0;padding-left:18px;">${items}</ul>`;
                } else {
                    resultadosHtml = '<span style="color:#aaa;font-size:.82rem;">Sin resultados registrados</span>';
                }

                const proxVisita = a.prox_visita
                    ? `<span style="color:var(--primario-oscuro);font-weight:600;">${a.prox_visita}</span>`
                    : '—';

                $('#modalATBody').html(`
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label-b">Tipo de Asistencia</label>
                            <p><i class="fas fa-tag me-1" style="color:var(--primario-oscuro);"></i>${escHtml(a.tipo_at)}</p></div>
                        <div class="col-md-3"><label class="form-label-b">Fecha</label>
                            <p>${a.fecha_visita}${a.hora_visita ? ' — ' + a.hora_visita : ''}</p></div>
                        <div class="col-md-2"><label class="form-label-b">Duración</label>
                            <p>${escHtml(a.duracion || '—')}</p></div>
                        <div class="col-md-3"><label class="form-label-b">Estado</label>
                            <p>${estadoHtml}</p></div>

                        <div class="col-md-4"><label class="form-label-b">Productor</label>
                            <p><strong>${escHtml(a.productor_nombre)} ${escHtml(a.productor_apellido || '')}</strong></p></div>
                        <div class="col-md-2"><label class="form-label-b">DNI</label>
                            <p>${a.productor_dni || '—'}</p></div>
                        <div class="col-md-2"><label class="form-label-b">Edad</label>
                            <p>${a.productor_edad || '—'}</p></div>
                        <div class="col-md-2"><label class="form-label-b">Sexo</label>
                            <p>${sexoHtml}</p></div>
                        <div class="col-md-2"><label class="form-label-b">Teléfono</label>
                            <p>${a.productor_telefono || '—'}</p></div>

                        <div class="col-md-4"><label class="form-label-b">Organización</label>
                            <p>${escHtml(a.organizacion || '—')}</p></div>
                        <div class="col-md-4"><label class="form-label-b">Área Productiva</label>
                            <p>${a.area_productiva ? a.area_productiva + ' mz' : '—'}</p></div>
                        <div class="col-md-4"><label class="form-label-b">Cultivo / Rubro</label>
                            <p>${escHtml(a.cultivo || '—')}</p></div>

                        <div class="col-md-6"><label class="form-label-b">Ubicación</label>
                            <p>${escHtml(a.departamento)} / ${escHtml(a.municipio)}${a.aldea ? ' / ' + escHtml(a.aldea) : ''}</p></div>
                        <div class="col-md-3"><label class="form-label-b">Técnico</label>
                            <p>${escHtml(a.tecnico)}</p></div>
                        <div class="col-md-3"><label class="form-label-b">Próxima Visita</label>
                            <p>${proxVisita}</p></div>

                        <div class="col-md-6"><label class="form-label-b">Tema</label>
                            <p>${escHtml(a.tema)}${a.subtema ? ' — ' + escHtml(a.subtema) : ''}</p></div>

                        ${a.descripcion ? `<div class="col-12"><label class="form-label-b">Descripción</label>
                            <p style="white-space:pre-wrap;">${escHtml(a.descripcion)}</p></div>` : ''}

                        <div class="col-12">
                            <label class="form-label-b">
                                <i class="fas fa-list-check me-1" style="color:var(--primario-oscuro);"></i>Resultados / Logros
                            </label>
                            <div style="margin-top:4px;">${resultadosHtml}</div>
                        </div>

                        ${a.observaciones ? `<div class="col-12"><label class="form-label-b">Observaciones</label>
                            <p style="white-space:pre-wrap;">${escHtml(a.observaciones)}</p></div>` : ''}
                    </div>
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
                switchTab('nueva');
                setTimeout(function () {
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
                    $('#atPDni').val(a.productor_dni);
                    $('#atPEdad').val(a.productor_edad);
                    $('#atPSexo').val(a.productor_sexo);
                    $('#atPTel').val(a.productor_telefono);
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
                    window.scrollTo(0, 0);
                    SAG.toast('Visita cargada para edición.', 'warning');
                }, 100);
            },
        });
    }

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
