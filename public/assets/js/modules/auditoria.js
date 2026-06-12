/**
 * auditoria.js — Visor de la bitácora del sistema
 * DataTables en modo server-side: la búsqueda, paginación y filtros
 * se resuelven en el servidor (sag_logs puede crecer mucho).
 */
$(function () {

    const tabla = $('#tablaAuditoria').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url:  SAG.BASE_URL + '/auditoria/listar',
            type: 'POST',
            data: function (d) {
                d.id_usuario  = $('#filtroUsuario').val();
                d.modulo      = $('#filtroModulo').val();
                d.accion      = $('#filtroAccion').val();
                d.fecha_desde = $('#filtroDesde').val();
                d.fecha_hasta = $('#filtroHasta').val();
                d._csrf       = SAG.CSRF;
            },
        },
        columns: [
            { data: 'fecha' },
            { data: 'usuario' },
            { data: 'programa', className: 'text-center' },
            { data: 'accion' },
            { data: 'modulo' },
            { data: 'detalle' },
            { data: 'ip' },
        ],
        // El orden lo fija el servidor (fecha DESC); ordenar por otras
        // columnas server-side no aporta en una bitácora cronológica.
        ordering:   false,
        language:   { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
        pageLength: 25,
        lengthMenu: [25, 50, 100],
    });

    $('#filtroUsuario, #filtroModulo, #filtroAccion, #filtroDesde, #filtroHasta').on('change', function () {
        tabla.ajax.reload();
    });

    $('#btnLimpiarFiltros').on('click', function () {
        $('#filtroUsuario, #filtroModulo, #filtroAccion').val('');
        $('#filtroDesde, #filtroHasta').val('');
        tabla.ajax.reload();
    });

});
