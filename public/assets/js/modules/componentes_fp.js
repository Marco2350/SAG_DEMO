/**
 * componentes_fp.js — CRUD de Componentes del programa FPROG 2026.
 * Patrón SAG_DEMO: SAG.ajax inyecta CSRF auto; sin alert()/confirm() del navegador.
 */
$(function () {
    const BASE = SAG.BASE_URL;
    const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
    const modalComp = new bootstrap.Modal('#modalComponente');

    let tabla;

    function nfmt(v, d = 2) {
        const n = Number(v) || 0;
        return n.toLocaleString('es-HN', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    function pctBadge(pct) {
        const color = pct >= 100 ? '#16a34a'
                    : pct >= 50  ? '#f59e0b'
                    : '#dc2626';
        return `<span style="color:${color};font-weight:600;">${pct}%</span>`;
    }

    function estadoBadge(estado) {
        const map = {
            planificado:  '#f59e0b',
            en_ejecucion: '#3b82f6',
            completado:   '#16a34a',
            suspendido:   '#6b7280',
            cancelado:    '#dc2626',
        };
        const c = map[estado] || '#6b7280';
        return `<span class="badge" style="background:${c};color:#fff;">${estado.replace('_', ' ')}</span>`;
    }

    function initTabla() {
        tabla = $('#tablaComponentes').DataTable({
            processing: true,
            ajax: {
                url: BASE + '/componentes_fp/listar',
                type: 'POST',
                data: d => {
                    d.estado    = $('#filtroEstado').val();
                    d.categoria = $('#filtroCategoria').val();
                    d.buscar    = $('#filtroBuscar').val();
                    d._csrf     = CSRF();
                },
                dataSrc: 'data',
            },
            columns: [
                { data: 'numero_romano',         width: '50px', className: 'text-center' },
                { data: 'nombre' },
                { data: 'categoria',             width: '110px' },
                { data: 'presupuesto_asignado',  className: 'text-end', render: v => 'L. ' + nfmt(v) },
                { data: 'presupuesto_ejecutado', className: 'text-end', render: v => 'L. ' + nfmt(v) },
                { data: 'pct_ejecucion',         className: 'text-center', render: pctBadge },
                { data: 'meta_valor',            className: 'text-end',
                  render: (v, _t, row) => nfmt(v, 0) + (row.meta_unidad ? ' ' + row.meta_unidad : '') },
                { data: 'avance_valor',          className: 'text-end', render: v => nfmt(v, 0) },
                { data: 'pct_avance',            className: 'text-center', render: pctBadge },
                { data: 'estado',                className: 'text-center', render: estadoBadge },
                {
                    data: 'id_componente',
                    orderable: false,
                    className: 'text-center',
                    width: '110px',
                    render: id => `
                        <button class="btn-outline btn-sm-icon btn-editar" data-id="${id}" title="Editar">
                            <i class="fas fa-pen"></i></button>
                        <button class="btn-danger-sm ms-1 btn-eliminar" data-id="${id}" title="Eliminar">
                            <i class="fas fa-trash"></i></button>`,
                },
            ],
            language:   { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
            order:      [[0, 'asc']],
            pageLength: 15,
        });
    }
    initTabla();

    $('#filtroEstado, #filtroCategoria').on('change', () => tabla.ajax.reload());
    let debounce;
    $('#filtroBuscar').on('keyup', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => tabla.ajax.reload(), 350);
    });

    $('#btnNuevoComponente').on('click', () => {
        $('#formComponente')[0].reset();
        $('#id_componente').val(0);
        $('#formComponente [name=moneda]').val('HNL');
        $('#formComponente [name=categoria]').val('otro');
        $('#modalCompTitle').text('Nuevo Componente');
        modalComp.show();
    });

    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url: '/componentes_fp/get',
            data: { id },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const c = res.data.componente;
                $('#formComponente')[0].reset();
                $('#id_componente').val(c.id_componente);
                $('#formComponente [name=numero_romano]').val(c.numero_romano || '');
                $('#formComponente [name=codigo]').val(c.codigo || '');
                $('#formComponente [name=nombre]').val(c.nombre);
                $('#formComponente [name=descripcion]').val(c.descripcion || '');
                $('#formComponente [name=categoria]').val(c.categoria);
                $('#formComponente [name=presupuesto_asignado]').val(c.presupuesto_asignado);
                $('#formComponente [name=presupuesto_ejecutado]').val(c.presupuesto_ejecutado);
                $('#formComponente [name=moneda]').val(c.moneda || 'HNL');
                $('#formComponente [name=meta_unidad]').val(c.meta_unidad || '');
                $('#formComponente [name=meta_valor]').val(c.meta_valor);
                $('#formComponente [name=avance_valor]').val(c.avance_valor);
                $('#formComponente [name=medio_verificacion]').val(c.medio_verificacion || '');
                $('#formComponente [name=fecha_inicio]').val(c.fecha_inicio || '');
                $('#formComponente [name=fecha_fin]').val(c.fecha_fin || '');
                $('#formComponente [name=responsable]').val(c.responsable || '');
                $('#formComponente [name=observaciones]').val(c.observaciones || '');
                $('#modalCompTitle').text('Editar Componente');
                modalComp.show();
            }
        });
    });

    $('#formComponente').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/componentes_fp/save',
            data: $(this).serialize(),
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                modalComp.hide();
                tabla.ajax.reload(null, false);
            }
        });
    });

    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        SAG.confirmar('¿Eliminar este componente?', 'La acción quedará registrada en bitácora.', () => {
            SAG.ajax({
                url: '/componentes_fp/delete',
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
