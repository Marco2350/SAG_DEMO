/**
 * organizaciones.js — Módulo de Organizaciones
 * SAG Programas — sag_programas
 */
$(function () {

    let tabla;
    const modal         = new bootstrap.Modal('#modalOrganizacion');
    const modalEstado   = new bootstrap.Modal('#modalEstado');
    const modalMiembros = new bootstrap.Modal('#modalMiembros');

    // ── INICIALIZAR DATATABLE ─────────────────────────
    function initTabla() {
        tabla = $('#tablaOrganizaciones').DataTable({
            processing:  true,
            serverSide:  false,
            ajax: {
                url:    SAG.BASE_URL + '/organizaciones/listar',
                type:   'POST',
                data:   function (d) {
                    d.estado          = $('#filtroEstado').val();
                    d.id_departamento = $('#filtroDep').val();
                },
                dataSrc: 'data',
            },
            columns: [
                { data: 'id_organizacion', width: '40px' },
                { data: 'nombre' },
                { data: 'tipo_organizacion' },
                { data: 'ubicacion' },
                { data: 'representante' },
                { data: 'telefono' },
                { data: 'num_beneficiarios', className: 'text-center' },
                { data: 'estado',   orderable: false },
                { data: 'acciones', orderable: false, className: 'text-center' },
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json',
            },
            order:      [[1, 'asc']],
            pageLength: 15,
            responsive: true,
        });
    }

    initTabla();

    // ── FILTRAR ───────────────────────────────────────
    $('#btnFiltrar').on('click', function () {
        tabla.ajax.reload();
    });

    // ── ABRIR MODAL NUEVA ─────────────────────────────
    $('#btnNueva').on('click', function () {
        resetForm();
        $('#modalOrgTitulo').html('<i class="fas fa-plus me-2"></i>Nueva Organización');
        $('#orgFechaReg').val(new Date().toISOString().split('T')[0]);
        modal.show();
    });

    // ── EDITAR ────────────────────────────────────────
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url:  '/organizaciones/get',
            data: { id },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const o = res.data;
                resetForm();
                $('#modalOrgTitulo').html('<i class="fas fa-pen me-2"></i>Editar Organización');
                $('#orgId').val(o.id_organizacion);
                $('#orgNombre').val(o.nombre);
                $('#orgTipo').val(o.tipo);           // ENUM — no id_tipo
                $('#orgRepresentante').val(o.representante);
                $('#orgTelefono').val(o.telefono);
                $('#orgEmail').val(o.email);
                $('#orgAldea').val(o.aldea);
                $('#orgEstado').val(o.estado);
                $('#orgFechaReg').val(o.fecha_registro);
                $('#orgCoordenadas').val(o.coordenadas);
                $('#orgDep').val(o.id_departamento);
                SAG.loadMunicipios(o.id_departamento, '#orgMun', o.id_municipio);
                modal.show();
            },
        });
    });

    // ── GUARDAR ───────────────────────────────────────
    $('#btnGuardarOrg').on('click', function () {
        const nombre = $('#orgNombre').val().trim();
        const dep    = $('#orgDep').val();
        const mun    = $('#orgMun').val();

        if (!nombre) { SAG.toast('El nombre es obligatorio.',       'warning'); return; }
        if (!dep)    { SAG.toast('Seleccione un departamento.',     'warning'); return; }
        if (!mun)    { SAG.toast('Seleccione un municipio.',        'warning'); return; }

        SAG.btnLoading('#btnGuardarOrg', true);
        SAG.ajax({
            url:  '/organizaciones/save',
            data: $('#formOrganizacion').serialize(),
            success: function (res) {
                SAG.btnLoading('#btnGuardarOrg', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message);
                modal.hide();
                tabla.ajax.reload();
            },
            error: function () { SAG.btnLoading('#btnGuardarOrg', false); },
        });
    });

    // ── CAMBIAR ESTADO ────────────────────────────────
    $(document).on('click', '.btn-estado', function () {
        const id     = $(this).data('id');
        const estado = $(this).data('estado');
        $('#estadoOrgId').val(id);
        $('#nuevoEstado').val(estado);
        modalEstado.show();
    });

    $('#btnConfirmarEstado').on('click', function () {
        const id     = $('#estadoOrgId').val();
        const estado = $('#nuevoEstado').val();

        SAG.ajax({
            url:  '/organizaciones/estado',
            data: { id, estado },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message);
                modalEstado.hide();
                tabla.ajax.reload();
            },
        });
    });

    // ── ELIMINAR ──────────────────────────────────────
    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        SAG.confirm('¿Está seguro de eliminar esta organización? Esta acción no se puede deshacer.', function () {
            SAG.ajax({
                url:  '/organizaciones/delete',
                data: { id },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    tabla.ajax.reload();
                },
            });
        });
    });

    // ── VER MIEMBROS ──────────────────────────────────
    $(document).on('click', '.btn-miembros', function () {
        const id     = $(this).data('id');
        const nombre = $(this).data('nombre');

        $('#modalMiembrosTitulo').html('<i class="fas fa-users me-2"></i>Miembros — ' + nombre);
        $('#miembrosTotal').text('…');
        $('#miembrosActivos').text('…');
        $('#miembrosInactivos').text('…');
        $('#miembrosLoader').show();
        $('#miembrosVacio').hide();
        $('#tablaMiembros').hide();
        $('#miembrosTbody').empty();
        modalMiembros.show();

        SAG.ajax({
            url:  '/organizaciones/miembros',
            data: { id },
            success: function (res) {
                $('#miembrosLoader').hide();
                if (!res.success) { SAG.toast(res.message, 'error'); return; }

                const rows    = res.data;
                const activos = rows.filter(r => r.estado.includes('Activo')).length;

                $('#miembrosTotal').text(rows.length);
                $('#miembrosActivos').text(activos);
                $('#miembrosInactivos').text(rows.length - activos);

                if (rows.length === 0) {
                    $('#miembrosVacio').show();
                    return;
                }

                let html = '';
                rows.forEach(function (b, i) {
                    html += `<tr>
                        <td>${i + 1}</td>
                        <td><strong>${b.nombre}</strong></td>
                        <td>${b.dni}</td>
                        <td>${b.sexo}</td>
                        <td>${b.ubicacion}</td>
                        <td>${b.telefono}</td>
                        <td>${b.cultivo}</td>
                        <td>${b.estado}</td>
                    </tr>`;
                });
                $('#miembrosTbody').html(html);
                $('#tablaMiembros').show();
            },
            error: function () {
                $('#miembrosLoader').hide();
                SAG.toast('Error al cargar los miembros.', 'error');
            },
        });
    });

    // ── RESET FORM ────────────────────────────────────
    function resetForm() {
        document.getElementById('formOrganizacion').reset();
        $('#orgId').val(0);
        $('#orgMun').html('<option value="">— Seleccione departamento primero —</option>');
        $('#orgEstado').val('pendiente');
        $('#orgTipo').val('cooperativa');
    }

    // ── CHANGE DEPTO (modal) ──────────────────────────
    $('#orgDep').on('change', function () {
        SAG.loadMunicipios($(this).val(), '#orgMun');
    });

});
