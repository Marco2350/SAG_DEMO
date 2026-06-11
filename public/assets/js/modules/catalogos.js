/**
 * catalogos.js — Parametrización (catálogos del programa)
 * Maneja las páginas: /catalogos/tecnicos, /catalogos/temas,
 * /catalogos/cultivos y /catalogos/tiposat.
 * Tablas renderizadas en servidor + DataTables cliente + modales Bootstrap.
 * Endpoints de guardado/eliminación: /mantenimiento/<catalogo>/save|delete.
 */
$(function () {

    const DT_OPTS = {
        language:   { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
        pageLength: 15,
        columnDefs: [{ targets: -1, orderable: false }],
    };

    function recargar() {
        setTimeout(() => location.reload(), 700);
    }

    // ══════════════════════════════════════════════════
    //  PÁGINAS DE CATÁLOGO SIMPLE (técnicos, cultivos, tipos AT)
    //  Comparten #modalCatalogo / #formCatalogo / #btnGuardarCat
    // ══════════════════════════════════════════════════
    const $modalCat = $('#modalCatalogo');
    if ($modalCat.length) {
        const catalogo = $modalCat.data('catalogo'); // tecnicos | cultivos | tiposat
        const modal    = new bootstrap.Modal('#modalCatalogo');

        const CONFIG = {
            tecnicos: {
                tabla:    '#tablaTecnicos',
                saveUrl:  '/mantenimiento/tecnicos/save',
                delUrl:   '/mantenimiento/tecnicos/delete',
                tituloNuevo:  '<i class="fas fa-user-tie me-2"></i>Nuevo Técnico',
                tituloEditar: '<i class="fas fa-pen me-2"></i>Editar Técnico',
                fill: function (r) {
                    $('#catNombre').val(r.nombre_completo);
                    $('#catEspecialidad').val(r.especialidad);
                    $('#catDep').val(r.id_departamento || '');
                    $('#catTelefono').val(SAG.formatTel(r.telefono || ''));
                    $('#catEmail').val(r.email);
                    $('#catActivo').val(r.activo);
                },
            },
            cultivos: {
                tabla:    '#tablaCultivos',
                saveUrl:  '/mantenimiento/cultivos/save',
                delUrl:   '/mantenimiento/cultivos/delete',
                tituloNuevo:  '<i class="fas fa-seedling me-2"></i>Nuevo Cultivo',
                tituloEditar: '<i class="fas fa-pen me-2"></i>Editar Cultivo',
                fill: function (r) {
                    $('#catNombre').val(r.nombre);
                    $('#catTipo').val(r.tipo);
                    $('#catActivo').val(r.activo);
                },
            },
            tiposat: {
                tabla:    '#tablaTiposAt',
                saveUrl:  '/mantenimiento/tipoat/save',
                delUrl:   '/mantenimiento/tipoat/delete',
                tituloNuevo:  '<i class="fas fa-list-check me-2"></i>Nuevo Tipo de Asistencia',
                tituloEditar: '<i class="fas fa-pen me-2"></i>Editar Tipo de Asistencia',
                fill: function (r) {
                    $('#catNombre').val(r.nombre);
                    $('#catIcono').val(r.icono);
                    $('#catActivo').val(r.activo);
                },
            },
        };
        const cfg = CONFIG[catalogo];

        if (cfg) {
            $(cfg.tabla).DataTable(DT_OPTS);

            // Máscara de teléfono (solo técnicos)
            $('#catTelefono').on('input', function () {
                this.value = SAG.formatTel(this.value);
            });

            // Nuevo
            $('#btnNuevoTec, #btnNuevoCultivo, #btnNuevoTipoAt').on('click', function () {
                document.getElementById('formCatalogo').reset();
                $('#catId').val(0);
                $('#modalCatTitulo').html(cfg.tituloNuevo);
                modal.show();
            });

            // Editar
            $(document).on('click', '.btn-editar-cat', function () {
                const r = $(this).data('row');
                document.getElementById('formCatalogo').reset();
                $('#catId').val(r.id);
                cfg.fill(r);
                $('#modalCatTitulo').html(cfg.tituloEditar);
                modal.show();
            });

            // Guardar
            $('#btnGuardarCat').on('click', function () {
                const nombre = ($('#catNombre').val() || '').trim();
                if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
                const tel = ($('#catTelefono').val() || '').replace(/\D/g, '');
                if (tel && tel.length !== 8) {
                    SAG.toast('El teléfono debe tener 8 dígitos (formato Honduras).', 'warning'); return;
                }
                SAG.btnLoading('#btnGuardarCat', true);
                SAG.ajax({
                    url:  cfg.saveUrl,
                    data: $('#formCatalogo').serialize(),
                    success: function (res) {
                        SAG.btnLoading('#btnGuardarCat', false);
                        if (!res.success) { SAG.toast(res.message, 'error'); return; }
                        SAG.toast(res.message);
                        modal.hide();
                        recargar();
                    },
                    error: function () { SAG.btnLoading('#btnGuardarCat', false); },
                });
            });

            // Desactivar
            $(document).on('click', '.btn-desactivar-cat', function () {
                const id     = $(this).data('id');
                const nombre = $(this).data('nombre') || 'este registro';
                SAG.confirm('¿Desactivar "' + nombre + '"? Dejará de aparecer en los formularios, pero los registros históricos se conservan.', function () {
                    SAG.ajax({
                        url:  cfg.delUrl,
                        data: { id },
                        success: function (res) {
                            if (!res.success) { SAG.toast(res.message, 'error'); return; }
                            SAG.toast(res.message);
                            recargar();
                        },
                    });
                });
            });
        }
    }

    // ══════════════════════════════════════════════════
    //  PÁGINA TEMAS Y SUBTEMAS (dos tablas + dos modales)
    // ══════════════════════════════════════════════════
    if ($('#tablaTemas').length) {
        const modalTema = new bootstrap.Modal('#modalTema');
        const modalSub  = new bootstrap.Modal('#modalSubtema');

        const optsCompact = Object.assign({}, DT_OPTS, { pageLength: 10 });
        $('#tablaTemas').DataTable(optsCompact);
        $('#tablaSubtemas').DataTable(optsCompact);

        // ── TEMAS ──
        $('#btnNuevoTema').on('click', function () {
            document.getElementById('formTema').reset();
            $('#temaId').val(0);
            $('#modalTemaTitulo').html('<i class="fas fa-tags me-2"></i>Nuevo Tema');
            modalTema.show();
        });

        $(document).on('click', '.btn-editar-tema', function () {
            const r = $(this).data('row');
            $('#temaId').val(r.id);
            $('#temaNombre').val(r.nombre);
            $('#temaTipo').val(r.tipo);
            $('#temaActivo').val(r.activo);
            $('#modalTemaTitulo').html('<i class="fas fa-pen me-2"></i>Editar Tema');
            modalTema.show();
        });

        $('#btnGuardarTema').on('click', function () {
            const nombre = $('#temaNombre').val().trim();
            if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
            SAG.btnLoading('#btnGuardarTema', true);
            SAG.ajax({
                url:  '/mantenimiento/temas/save',
                data: $('#formTema').serialize(),
                success: function (res) {
                    SAG.btnLoading('#btnGuardarTema', false);
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    modalTema.hide();
                    recargar();
                },
                error: function () { SAG.btnLoading('#btnGuardarTema', false); },
            });
        });

        $(document).on('click', '.btn-desactivar-tema', function () {
            const id     = $(this).data('id');
            const nombre = $(this).data('nombre');
            SAG.confirm('¿Desactivar el tema "' + nombre + '"? Sus subtemas también dejarán de ofrecerse en los formularios.', function () {
                SAG.ajax({
                    url:  '/mantenimiento/temas/delete',
                    data: { id },
                    success: function (res) {
                        if (!res.success) { SAG.toast(res.message, 'error'); return; }
                        SAG.toast(res.message);
                        recargar();
                    },
                });
            });
        });

        // ── SUBTEMAS ──
        $('#btnNuevoSubtema').on('click', function () {
            document.getElementById('formSubtema').reset();
            $('#subId').val(0);
            $('#modalSubtemaTitulo').html('<i class="fas fa-tag me-2"></i>Nuevo Subtema');
            modalSub.show();
        });

        $(document).on('click', '.btn-editar-subtema', function () {
            const r = $(this).data('row');
            $('#subId').val(r.id);
            $('#subNombre').val(r.nombre);
            $('#subTema').val(r.id_tema);
            $('#subActivo').val(r.activo);
            $('#modalSubtemaTitulo').html('<i class="fas fa-pen me-2"></i>Editar Subtema');
            modalSub.show();
        });

        $('#btnGuardarSubtema').on('click', function () {
            const nombre = $('#subNombre').val().trim();
            const idTema = $('#subTema').val();
            if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
            if (!idTema) { SAG.toast('Seleccione el tema padre.', 'warning'); return; }
            SAG.btnLoading('#btnGuardarSubtema', true);
            SAG.ajax({
                url:  '/mantenimiento/subtemas/save',
                data: $('#formSubtema').serialize(),
                success: function (res) {
                    SAG.btnLoading('#btnGuardarSubtema', false);
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    modalSub.hide();
                    recargar();
                },
                error: function () { SAG.btnLoading('#btnGuardarSubtema', false); },
            });
        });

        $(document).on('click', '.btn-desactivar-subtema', function () {
            const id     = $(this).data('id');
            const nombre = $(this).data('nombre');
            SAG.confirm('¿Desactivar el subtema "' + nombre + '"?', function () {
                SAG.ajax({
                    url:  '/mantenimiento/subtemas/delete',
                    data: { id },
                    success: function (res) {
                        if (!res.success) { SAG.toast(res.message, 'error'); return; }
                        SAG.toast(res.message);
                        recargar();
                    },
                });
            });
        });
    }

});
