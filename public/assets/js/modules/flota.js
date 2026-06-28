/**
 * flota.js — Módulo Flota Vehicular
 *
 * Maneja 3 tabs (Vehículos / Viajes / Mantenimientos) y 4 modales:
 *   - modalVehiculo       → CRUD vehículo
 *   - modalViaje          → Nuevo viaje
 *   - modalCerrarViaje    → Cerrar viaje con km final
 *   - modalManto          → Nuevo mantenimiento
 */
$(function () {

    // ── Tabs ─────────────────────────────────────────────────
    $('.flota-tab').on('click', function () {
        const tab = $(this).data('tab');
        $('.flota-tab').removeClass('active');
        $(this).addClass('active');
        $('.flota-panel').removeClass('active');
        $('#panel-' + tab).addClass('active');
    });

    // ── DataTables ───────────────────────────────────────────
    const dtOpts = {
        language: { url: (document.querySelector('meta[name="base-url"]')?.content || '') + '/public/assets/js/datatables/es-MX.json' },
        pageLength: 25,
        order: [],
        columnDefs: [{ targets: -1, orderable: false }],
    };
    if ($('#tblVehiculos').length) $('#tblVehiculos').DataTable(dtOpts);
    if ($('#tblViajes').length)    $('#tblViajes').DataTable(dtOpts);
    if ($('#tblMantos').length)    $('#tblMantos').DataTable(dtOpts);
    if ($('#tblCombustible').length) $('#tblCombustible').DataTable(dtOpts);

    // ── Helper: leer JSON del data-row ───────────────────────
    function rowData($btn) {
        try { return JSON.parse($btn.closest('tr').attr('data-row') || '{}'); }
        catch (e) { return {}; }
    }

    // ════════════════════════════════════════════════════════
    //  VEHÍCULO
    // ════════════════════════════════════════════════════════

    $('#btnNuevoVehiculo').on('click', function () {
        $('#formVehiculo')[0].reset();
        $('#vehId').val(0);
        $('#vehTitulo').text('Nuevo vehículo');
        actualizarProximos(true);
        new bootstrap.Modal('#modalVehiculo').show();
    });

    function actualizarProximos(forzar) {
        const km = parseInt($('#vehKm').val(), 10) || 0;
        $('.veh-proximo-manto').each(function () {
            if (forzar || !$(this).val()) {
                $(this).val(km + (parseInt($(this).data('intervalo'), 10) || 0));
            }
            $(this).attr('min', km);
        });
    }

    $('#vehKm').on('change', function () {
        actualizarProximos($('#vehId').val() === '0');
    });

    $(document).on('click', '.btn-edit-veh', function () {
        const v = rowData($(this));
        $('#formVehiculo')[0].reset();
        $('#vehId').val(v.id_vehiculo || 0);
        $('#vehPlaca').val(v.placa || '');
        $('#vehMarca').val(v.marca || '');
        $('#vehModelo').val(v.modelo || '');
        $('#vehAnio').val(v.anio || '');
        $('#vehColor').val(v.color || '');
        $('#vehVin').val(v.vin || '');
        $('#vehKm').val(v.km_actual || 0);
        $('#vehCombustible').val(v.tipo_combustible || 'gasolina');
        $('#vehEstado').val(v.estado || 'activo');
        $('#vehVenceSeguro').val(v.vence_seguro || '');
        $('#vehObs').val(v.observaciones || '');
        $('.veh-proximo-manto').val('');
        Object.entries(v.programacion || {}).forEach(([tipo, plan]) => {
            $('#vehProximo_' + tipo).val(plan.proximo_km || '');
        });
        actualizarProximos(false);
        $('#vehTitulo').text('Editar vehículo · ' + (v.placa || ''));
        new bootstrap.Modal('#modalVehiculo').show();
    });

    $('#formVehiculo').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/flota/guardarVehiculo',
            data: $(this).serialize(),
            success: r => {
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalVehiculo'))?.hide();
                setTimeout(() => window.location.reload(), 800);
            },
            error: () => SAG.toast('Error al guardar', 'error'),
        });
    });

    $(document).on('click', '.btn-del-veh', function () {
        const v = rowData($(this));
        if (!confirm('¿Dar de baja el vehículo ' + (v.placa || '') + '?')) return;
        SAG.ajax({
            url: '/flota/eliminarVehiculo',
            data: { id: v.id_vehiculo, csrf_token: $('input[name=csrf_token]').first().val() },
            success: r => {
                SAG.toast(r.message, r.success ? 'success' : 'error');
                if (r.success) setTimeout(() => window.location.reload(), 800);
            },
        });
    });

    // ════════════════════════════════════════════════════════
    //  VIAJE
    // ════════════════════════════════════════════════════════

    $(document).on('click', '.btn-add-viaje', function () {
        const v = rowData($(this));
        $('#formViaje')[0].reset();
        $('#viajeIdVehiculo').val(v.id_vehiculo);
        $('#viajeKmInicial').val(v.km_actual || 0);
        $('#viajeVehInfo').text('Vehículo: ' + (v.placa || '') + ' · Km actual: ' + (v.km_actual || 0));
        $('input[name=fecha]', '#formViaje').val(new Date().toISOString().slice(0, 10));
        new bootstrap.Modal('#modalViaje').show();
    });

    $('#formViaje').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/flota/guardarViaje',
            data: $(this).serialize(),
            success: r => {
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalViaje'))?.hide();
                setTimeout(() => window.location.reload(), 800);
            },
        });
    });

    $(document).on('click', '.btn-close-viaje', function () {
        const v = rowData($(this));
        $('#formCerrarViaje')[0].reset();
        $('#cerrarIdViaje').val(v.id_viaje);
        $('#cerrarKmFinal').val(v.km_inicial || 0).attr('min', v.km_inicial || 0);
        $('#cerrarInfo').text('Viaje #' + v.id_viaje + ' · ' + (v.placa || '') + ' · Km inicial: ' + (v.km_inicial || 0));
        new bootstrap.Modal('#modalCerrarViaje').show();
    });

    $('#formCerrarViaje').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/flota/cerrarViaje',
            data: $(this).serialize(),
            success: r => {
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalCerrarViaje'))?.hide();
                setTimeout(() => window.location.reload(), 800);
            },
        });
    });

    $(document).on('click', '.btn-del-viaje', function () {
        const v = rowData($(this));
        if (!confirm('¿Eliminar viaje #' + v.id_viaje + '?')) return;
        SAG.ajax({
            url: '/flota/eliminarViaje',
            data: { id: v.id_viaje, csrf_token: $('input[name=csrf_token]').first().val() },
            success: r => {
                SAG.toast(r.message, r.success ? 'success' : 'error');
                if (r.success) setTimeout(() => window.location.reload(), 800);
            },
        });
    });

    // ════════════════════════════════════════════════════════
    //  MANTENIMIENTO
    // ════════════════════════════════════════════════════════

    $(document).on('click', '.btn-add-manto', function () {
        const v = rowData($(this));
        $('#formManto')[0].reset();
        $('#mantoIdVehiculo').val(v.id_vehiculo);
        $('#mantoKmRealizar').val(v.km_actual || 0);
        $('#mantoVehInfo').text('Vehículo: ' + (v.placa || '') + ' · Km actual: ' + (v.km_actual || 0));
        $('input[name=fecha]', '#formManto').val(new Date().toISOString().slice(0, 10));
        new bootstrap.Modal('#modalManto').show();
    });

    $('#formManto').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/flota/guardarMantenimiento',
            data: $(this).serialize(),
            success: r => {
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalManto'))?.hide();
                setTimeout(() => window.location.reload(), 800);
            },
        });
    });

    $(document).on('click', '.btn-del-manto', function () {
        const id = $(this).data('id');
        if (!confirm('¿Eliminar este mantenimiento?')) return;
        SAG.ajax({
            url: '/flota/eliminarMantenimiento',
            data: { id: id, csrf_token: $('input[name=csrf_token]').first().val() },
            success: r => {
                SAG.toast(r.message, r.success ? 'success' : 'error');
                if (r.success) setTimeout(() => window.location.reload(), 800);
            },
        });
    });

    $('#btnNuevaCarga').on('click', function () {
        $('#formCombustible')[0].reset();
        $('input[name=fecha]', '#formCombustible').val(new Date().toISOString().slice(0, 10));
        new bootstrap.Modal('#modalCombustible').show();
    });

    $('#cargaVehiculo').on('change', function () {
        const km = parseInt($(this).find(':selected').data('km'), 10) || 0;
        $('#cargaKm').val(km).attr('min', km);
    });

    $('.calc-carga').on('input', function () {
        const galones = parseFloat($('input[name=galones]', '#formCombustible').val()) || 0;
        const precio = parseFloat($('input[name=precio_galon]', '#formCombustible').val()) || 0;
        if (galones > 0 && precio > 0) $('#cargaMonto').val((galones * precio).toFixed(2));
    });

    $('#formCombustible').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/flota/guardarCombustible',
            data: $(this).serialize(),
            success: r => {
                if (!r.success) { SAG.toast(r.message, 'error'); return; }
                SAG.toast(r.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalCombustible'))?.hide();
                setTimeout(() => window.location.reload(), 800);
            },
        });
    });

    $(document).on('click', '.btn-del-carga', function () {
        if (!confirm('¿Eliminar esta carga de combustible?')) return;
        SAG.ajax({
            url: '/flota/eliminarCombustible',
            data: { id: $(this).data('id'), csrf_token: $('input[name=csrf_token]').first().val() },
            success: r => {
                SAG.toast(r.message, r.success ? 'success' : 'error');
                if (r.success) setTimeout(() => window.location.reload(), 800);
            },
        });
    });

});
