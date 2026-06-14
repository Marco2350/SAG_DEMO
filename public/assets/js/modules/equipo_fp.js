/**
 * equipo_fp.js — Equipo técnico FPROG.
 */
$(function () {
    const BASE = SAG.BASE_URL;
    const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
    const modal = new bootstrap.Modal('#modalEquipo');
    let tabla;

    const nfmt = (v, d=0) => (Number(v)||0).toLocaleString('es-HN',
        { minimumFractionDigits: d, maximumFractionDigits: d });

    const colorEstado = {
        vacante:    '#f59e0b',
        en_proceso: '#3b82f6',
        contratado: '#16a34a',
        baja:       '#6b7280',
    };
    const estadoBadge = e => `<span class="badge" style="background:${colorEstado[e]||'#6b7280'};color:#fff;">${e.replace('_',' ')}</span>`;

    tabla = $('#tablaEquipo').DataTable({
        processing: true,
        ajax: {
            url: BASE + '/equipo_fp/listar',
            type: 'POST',
            data: d => {
                d.estado = $('#filtroEstado').val();
                d.buscar = $('#filtroBuscar').val();
                d._csrf  = CSRF();
            },
            dataSrc: 'data',
        },
        columns: [
            { data: 'rol' },
            { data: 'cantidad',               width: '60px', className: 'text-center' },
            { data: 'ambito',                 width: '140px' },
            { data: 'presupuesto_asignado',   className: 'text-end',
              render: v => v == null ? '—' : 'L. ' + nfmt(v, 2) },
            { data: 'porcentaje_presupuesto', className: 'text-end',
              render: v => v == null ? '—' : nfmt(v, 2) + '%' },
            { data: 'responsable',            width: '160px' },
            { data: 'estado',                 width: '110px', className: 'text-center', render: estadoBadge },
            {
                data: 'id_equipo', orderable: false, className: 'text-center', width: '90px',
                render: id => `
                    <button class="btn-outline btn-sm-icon btn-editar" data-id="${id}" title="Editar">
                        <i class="fas fa-pen"></i></button>
                    <button class="btn-danger-sm ms-1 btn-eliminar" data-id="${id}" title="Eliminar">
                        <i class="fas fa-trash"></i></button>`,
            },
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
        order:    [[0, 'asc']],
        pageLength: 15,
    });

    $('#filtroEstado').on('change', () => tabla.ajax.reload());
    let debounce;
    $('#filtroBuscar').on('keyup', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => tabla.ajax.reload(), 350);
    });

    $('#btnNuevoRol').on('click', () => {
        $('#formEquipo')[0].reset();
        $('#id_equipo').val(0);
        $('#formEquipo [name=cantidad]').val(1);
        $('#modalEquipoTitle').text('Nuevo Rol');
        modal.show();
    });

    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url: '/equipo_fp/get',
            data: { id },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const r = res.data.equipo;
                $('#formEquipo')[0].reset();
                $('#id_equipo').val(r.id_equipo);
                $('#formEquipo [name=rol]').val(r.rol);
                $('#formEquipo [name=cantidad]').val(r.cantidad);
                $('#formEquipo [name=descripcion]').val(r.descripcion || '');
                $('#formEquipo [name=ambito]').val(r.ambito || '');
                $('#formEquipo [name=presupuesto_asignado]').val(r.presupuesto_asignado ?? '');
                $('#formEquipo [name=porcentaje_presupuesto]').val(r.porcentaje_presupuesto ?? '');
                $('#formEquipo [name=responsable]').val(r.responsable || '');
                $('#formEquipo [name=observaciones]').val(r.observaciones || '');
                $('#modalEquipoTitle').text('Editar Rol');
                modal.show();
            }
        });
    });

    $('#formEquipo').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/equipo_fp/save',
            data: $(this).serialize(),
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                modal.hide();
                tabla.ajax.reload(null, false);
            }
        });
    });

    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        SAG.confirmar('¿Eliminar este rol?', 'La acción quedará en bitácora.', () => {
            SAG.ajax({
                url: '/equipo_fp/delete',
                data: { id },
                success: res => {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message, 'success');
                    tabla.ajax.reload(null, false);
                }
            });
        });
    });
});
