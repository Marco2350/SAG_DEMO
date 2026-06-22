/**
 * catalogos.js — Parametrización (catálogos del programa)
 * Páginas: /catalogos/tecnicos, /temas, /cultivos, /tiposat,
 *          /proveedores, /productos, /bodegas
 */
$(function () {

    const DT_OPTS = {
        language:   { url: (document.querySelector('meta[name="base-url"]')?.content || '') + '/public/assets/js/datatables/es-MX.json' },
        pageLength: 15,
        columnDefs: [{ targets: -1, orderable: false }],
    };

    function recargar() {
        setTimeout(() => location.reload(), 700);
    }

    // ═══ CATÁLOGOS SIMPLES (modal #modalCatalogo) ═══
    const $modalCat = $('#modalCatalogo');
    if ($modalCat.length) {
        const catalogo = $modalCat.data('catalogo');
        const modal    = new bootstrap.Modal('#modalCatalogo');

        function cargarMunicipios(idDep, idMuniSel) {
            const $muni = $('#catMuni');
            if (!$muni.length) return;
            $muni.html('<option value="">— Cargando… —</option>');
            if (!idDep) { $muni.html('<option value="">— Seleccione un depto. primero —</option>'); return; }
            SAG.ajax({
                url:  '/mantenimiento/municipios',
                type: 'GET',
                data: { id_departamento: idDep },
                success: function (res) {
                    let opts = '<option value="">— Seleccione municipio —</option>';
                    (res.data || []).forEach(m => {
                        opts += '<option value="' + m.id_municipio + '">' + m.nombre + '</option>';
                    });
                    $muni.html(opts);
                    if (idMuniSel) $muni.val(idMuniSel);
                },
                error: function () { $muni.html('<option value="">— Error al cargar —</option>'); },
            });
        }

        $(document).on('change', '#catDep', function () {
            if ($modalCat.data('catalogo') === 'bodegas') cargarMunicipios(this.value, null);
        });

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
            proveedores: {
                tabla:    '#tablaProveedores',
                saveUrl:  '/mantenimiento/proveedores/save',
                delUrl:   '/mantenimiento/proveedores/delete',
                tituloNuevo:  '<i class="fas fa-truck me-2"></i>Nuevo Proveedor',
                tituloEditar: '<i class="fas fa-pen me-2"></i>Editar Proveedor',
                fill: function (r) {
                    $('#catNombre').val(r.nombre);
                    $('#catRtn').val(r.rtn);
                    $('#catContacto').val(r.contacto);
                    $('#catTelefono').val(SAG.formatTel(r.telefono || ''));
                    $('#catEmail').val(r.email);
                    $('#catDireccion').val(r.direccion);
                    $('#catActivo').val(r.activo);
                },
            },
            productos: {
                tabla:    '#tablaProductos',
                saveUrl:  '/mantenimiento/productos/save',
                delUrl:   '/mantenimiento/productos/delete',
                tituloNuevo:  '<i class="fas fa-boxes-stacked me-2"></i>Nuevo Producto',
                tituloEditar: '<i class="fas fa-pen me-2"></i>Editar Producto',
                fill: function (r) {
                    $('#catCodigo').val(r.codigo);
                    $('#catNombre').val(r.nombre);
                    $('#catDescripcion').val(r.descripcion);
                    $('#catUnidad').val(r.unidad || 'unidad');
                    $('#catPresentacion').val(r.presentacion);
                    $('#catCategoria').val(r.categoria);
                    $('#catPrecio').val(r.precio_unitario);
                    $('#catActivo').val(r.activo);
                },
            },
            bodegas: {
                tabla:    '#tablaBodegas',
                saveUrl:  '/mantenimiento/bodegas/save',
                delUrl:   '/mantenimiento/bodegas/delete',
                tituloNuevo:  '<i class="fas fa-warehouse me-2"></i>Nueva Bodega',
                tituloEditar: '<i class="fas fa-pen me-2"></i>Editar Bodega',
                fill: function (r) {
                    $('#catCodigo').val(r.codigo);
                    $('#catNombre').val(r.nombre);
                    $('#catDep').val(r.id_departamento || '');
                    cargarMunicipios(r.id_departamento, r.id_municipio);
                    $('#catDireccion').val(r.direccion);
                    $('#catResponsable').val(r.responsable);
                    $('#catTelefono').val(SAG.formatTel(r.telefono || ''));
                    $('#catCapacidad').val(r.capacidad);
                    $('#catCoordenadas').val(r.coordenadas);
                    $('#catActivo').val(r.activo);
                },
            },
        };

        const cfg = CONFIG[catalogo];

        if (cfg) {
            $(cfg.tabla).DataTable(DT_OPTS);

            $('#catTelefono').on('input', function () {
                this.value = SAG.formatTel(this.value);
            });

            $('#btnNuevoTec, #btnNuevoCultivo, #btnNuevoTipoAt, #btnNuevoProveedor, #btnNuevoProducto, #btnNuevaBodega').on('click', function () {
                document.getElementById('formCatalogo').reset();
                $('#catId').val(0);
                $('#catActivo').val(1);
                if (catalogo === 'bodegas') {
                    $('#catMuni').html('<option value="">— Seleccione un depto. primero —</option>');
                }
                $('#modalCatTitulo').html(cfg.tituloNuevo);
                modal.show();
            });

            $(document).on('click', '.btn-editar-cat', function () {
                const r = $(this).data('row');
                document.getElementById('formCatalogo').reset();
                $('#catId').val(r.id);
                cfg.fill(r);
                $('#modalCatTitulo').html(cfg.tituloEditar);
                modal.show();
            });

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

    // ═══ PÁGINA TEMAS Y SUBTEMAS ═══
    if ($('#tablaTemas').length) {
        const modalTema = new bootstrap.Modal('#modalTema');
        const modalSub  = new bootstrap.Modal('#modalSubtema');

        const optsCompact = Object.assign({}, DT_OPTS, { pageLength: 10 });
        $('#tablaTemas').DataTable(optsCompact);
        $('#tablaSubtemas').DataTable(optsCompact);

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
