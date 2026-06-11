/**
 * organizaciones.js — Módulo de Organizaciones
 * SAG Programas — sag_programas
 */
$(function () {

    let tabla;
    const modal         = new bootstrap.Modal('#modalOrganizacion');
    const modalEstado   = new bootstrap.Modal('#modalEstado');
    const modalMiembros = new bootstrap.Modal('#modalMiembros');

    const HOY = new Date().toISOString().split('T')[0];
    $('#orgFechaReg').attr('max', HOY); // la fecha de registro no puede ser futura

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
                    d.tipo            = $('#filtroTipo').val();
                    d._csrf           = SAG.CSRF;
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

    // ── FILTRAR (botón + recarga automática al cambiar) ──
    $('#btnFiltrar').on('click', function () {
        tabla.ajax.reload();
    });
    $('#filtroEstado, #filtroDep, #filtroTipo').on('change', function () {
        tabla.ajax.reload();
    });

    // ── MÁSCARAS DE ENTRADA ───────────────────────────
    $('#orgRepDni').on('input', function () {
        this.value = SAG.formatDNI(this.value);
    });
    $('#orgTelefono').on('input', function () {
        this.value = SAG.formatTel(this.value);
    });

    // ── ABRIR MODAL NUEVA ─────────────────────────────
    $('#btnNueva').on('click', function () {
        resetForm();
        $('#modalOrgTitulo').html('<i class="fas fa-plus me-2"></i>Nueva Organización');
        $('#orgFechaReg').val(HOY);
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
                $('#orgTelefono').val(SAG.formatTel(o.telefono || ''));
                $('#orgEmail').val(o.email);
                $('#orgAldea').val(o.aldea);
                $('#orgEstado').val(o.estado);
                $('#orgFechaReg').val(o.fecha_registro);
                $('#orgRepDni').val(SAG.formatDNI(o.representante_dni || ''));
                // R-017: lat/lon separadas (con fallback al campo coordenadas legacy)
                if (o.latitud !== null && o.latitud !== undefined && o.latitud !== '') {
                    $('#orgLatitud').val(o.latitud);
                    $('#orgLongitud').val(o.longitud);
                } else if (o.coordenadas) {
                    const parts = String(o.coordenadas).split(',').map(s => s.trim());
                    if (parts.length === 2) {
                        $('#orgLatitud').val(parts[0]);
                        $('#orgLongitud').val(parts[1]);
                    }
                }
                $('#orgDep').val(o.id_departamento);
                SAG.loadMunicipios(o.id_departamento, '#orgMun', o.id_municipio);
                modal.show();
            },
        });
    });

    // R-012: Validación en vivo del nombre (al perder foco)
    $(document).on('blur', '#orgNombre', function () {
        const nombre = $(this).val().trim();
        const id     = $('#orgId').val() || 0;
        const $err   = $('#orgNombreErr');
        if (!nombre) { $err.hide(); return; }
        SAG.ajax({
            url: '/organizaciones/checkNombre',
            data: { nombre: nombre, id_organizacion: id },
            success: function (res) {
                if (res.success && res.data && res.data.existe) {
                    $err.show();
                    $('#orgNombre').addClass('is-invalid').css('border-color', '#dc2626');
                } else {
                    $err.hide();
                    $('#orgNombre').removeClass('is-invalid').css('border-color', '');
                }
            }
        });
    });

    // ── GUARDAR ───────────────────────────────────────
    $('#btnGuardarOrg').on('click', function () {
        const nombre = $('#orgNombre').val().trim();
        const tipo   = $('#orgTipo').val();
        const dep    = $('#orgDep').val();
        const mun    = $('#orgMun').val();
        const repDni = ($('#orgRepDni').val() || '').replace(/\D/g, '');
        const rep    = $('#orgRepresentante').val().trim();
        const tel    = ($('#orgTelefono').val() || '').replace(/\D/g, '');
        const email  = $('#orgEmail').val().trim();
        const lat    = $('#orgLatitud').val();
        const lon    = $('#orgLongitud').val();
        const fecha  = $('#orgFechaReg').val();

        if (!nombre)              { SAG.toast('El nombre es obligatorio.',                'warning'); return; }
        if (nombre.length < 3)    { SAG.toast('El nombre debe tener al menos 3 caracteres.', 'warning'); return; }
        if (!tipo)                { SAG.toast('Seleccione el tipo de organización.',      'warning'); return; }
        if (!dep)                 { SAG.toast('Seleccione un departamento.',              'warning'); return; }
        if (!mun)                 { SAG.toast('Seleccione un municipio.',                 'warning'); return; }
        if (!rep)                 { SAG.toast('Ingrese el nombre del representante legal.', 'warning'); return; }
        if (!repDni)              { SAG.toast('Ingrese el DNI del representante.',        'warning'); return; }
        if (repDni.length !== 13) { SAG.toast('El DNI debe tener 13 dígitos.',            'warning'); return; }
        if ($('#orgNombreErr').is(':visible')) { SAG.toast('El nombre ya está registrado.', 'warning'); return; }
        if (tel && tel.length !== 8) {
            SAG.toast('El teléfono debe tener 8 dígitos (formato Honduras).', 'warning'); return;
        }
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
            SAG.toast('El correo electrónico no es válido.', 'warning'); return;
        }
        // R-017: lat/lon van en pareja y dentro del rango de Honduras
        if ((lat && !lon) || (!lat && lon)) {
            SAG.toast('Ingrese latitud y longitud juntas, o deje ambas vacías.', 'warning'); return;
        }
        if (lat && (parseFloat(lat) < 12.9 || parseFloat(lat) > 16.5)) {
            SAG.toast('Latitud fuera del rango de Honduras (12.9 – 16.5).', 'warning'); return;
        }
        if (lon && (parseFloat(lon) < -89.4 || parseFloat(lon) > -83.1)) {
            SAG.toast('Longitud fuera del rango de Honduras (-89.4 – -83.1).', 'warning'); return;
        }
        if (fecha && fecha > HOY) {
            SAG.toast('La fecha de registro no puede ser futura.', 'warning'); return;
        }

        SAG.btnLoading('#btnGuardarOrg', true);
        SAG.ajax({
            url:  '/organizaciones/save',
            data: $('#formOrganizacion').serialize(),
            success: function (res) {
                SAG.btnLoading('#btnGuardarOrg', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message);
                modal.hide();
                tabla.ajax.reload(null, false); // mantener la página actual
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
                tabla.ajax.reload(null, false);
            },
        });
    });

    // ── ELIMINAR ──────────────────────────────────────
    $(document).on('click', '.btn-eliminar', function () {
        const id     = $(this).data('id');
        const nombre = $(this).data('nombre') || 'esta organización';
        SAG.confirm('¿Está seguro de eliminar "' + nombre + '"? Esta acción no se puede deshacer.', function () {
            SAG.ajax({
                url:  '/organizaciones/delete',
                data: { id },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    tabla.ajax.reload(null, false);
                },
            });
        });
    });

    // ── VER MIEMBROS ──────────────────────────────────
    // R-010: Agregar productor directamente desde la fila de la organización
    $(document).on('click', '.btn-agregar-productor', function () {
        const id     = $(this).data('id');
        const nombre = $(this).data('nombre');
        // Navega al módulo de productores con la org preseleccionada
        // El módulo de productores debe leer ?id_organizacion=N para pre-llenar
        window.location.href = SAG.BASE_URL + '/beneficiarios?id_organizacion=' + encodeURIComponent(id)
                             + '&accion=nuevo&org_nombre=' + encodeURIComponent(nombre);
    });

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
        $('#orgNombre').removeClass('is-invalid').css('border-color', '');
        $('#orgNombreErr').hide();
    }

    // ── CHANGE DEPTO (modal) ──────────────────────────
    $('#orgDep').on('change', function () {
        SAG.loadMunicipios($(this).val(), '#orgMun');
    });

});
