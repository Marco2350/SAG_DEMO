/**
 * cronograma_fp.js — Cronograma de actividades FPROG (Jun–Dic 2026).
 * Cada actividad tiene 7 marcas booleanas (jun..dic) que se renderizan
 * como celdas verdes (✓) o vacías (—) en una vista tipo Gantt simple.
 */
$(function () {
    const BASE = SAG.BASE_URL;
    const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
    const modal = new bootstrap.Modal('#modalActividad');
    let tabla;

    function mesCell(v) {
        return v ? `<span style="display:inline-block;background:#16a34a;color:#fff;border-radius:3px;padding:2px 6px;">✓</span>`
                 : `<span style="color:#d1d5db;">—</span>`;
    }

    const colorEstado = {
        pendiente:   '#f59e0b',
        en_curso:    '#3b82f6',
        completada:  '#16a34a',
        retrasada:   '#dc2626',
        cancelada:   '#6b7280',
    };
    const estadoBadge = e => `<span class="badge" style="background:${colorEstado[e]||'#6b7280'};color:#fff;">${e.replace('_',' ')}</span>`;

    tabla = $('#tablaCronograma').DataTable({
        processing: true,
        ajax: {
            url: BASE + '/cronograma_fp/listar',
            type: 'POST',
            data: d => {
                d.estado        = $('#filtroEstado').val();
                d.id_componente = $('#filtroComponente').val() || 0;
                d.buscar        = $('#filtroBuscar').val();
                d._csrf         = CSRF();
            },
            dataSrc: 'data',
        },
        columns: [
            { data: 'numero_orden', width: '40px', className: 'text-center' },
            { data: 'actividad' },
            { data: 'componente',   width: '160px' },
            { data: 'mes_jun',      width: '40px', className: 'text-center', orderable:false, render: mesCell },
            { data: 'mes_jul',      width: '40px', className: 'text-center', orderable:false, render: mesCell },
            { data: 'mes_ago',      width: '40px', className: 'text-center', orderable:false, render: mesCell },
            { data: 'mes_sep',      width: '40px', className: 'text-center', orderable:false, render: mesCell },
            { data: 'mes_oct',      width: '40px', className: 'text-center', orderable:false, render: mesCell },
            { data: 'mes_nov',      width: '40px', className: 'text-center', orderable:false, render: mesCell },
            { data: 'mes_dic',      width: '40px', className: 'text-center', orderable:false, render: mesCell },
            { data: 'responsable',  width: '140px' },
            { data: 'estado',       width: '110px', className: 'text-center', render: estadoBadge },
            {
                data: 'id_actividad', orderable: false, className: 'text-center', width: '90px',
                render: id => `
                    <button class="btn-outline btn-sm-icon btn-editar" data-id="${id}" title="Editar">
                        <i class="fas fa-pen"></i></button>
                    <button class="btn-danger-sm ms-1 btn-eliminar" data-id="${id}" title="Eliminar">
                        <i class="fas fa-trash"></i></button>`,
            },
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
        order:    [[0, 'asc']],
        pageLength: 20,
    });

    $('#filtroEstado, #filtroComponente').on('change', () => tabla.ajax.reload());
    let debounce;
    $('#filtroBuscar').on('keyup', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => tabla.ajax.reload(), 350);
    });

    $('#btnNuevaActividad').on('click', () => {
        $('#formActividad')[0].reset();
        $('#id_actividad').val(0);
        $('#formActividad [name=numero_orden]').val(0);
        $('#modalActTitle').text('Nueva Actividad');
        modal.show();
    });

    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url: '/cronograma_fp/get',
            data: { id },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const a = res.data.actividad;
                $('#formActividad')[0].reset();
                $('#id_actividad').val(a.id_actividad);
                $('#formActividad [name=numero_orden]').val(a.numero_orden);
                $('#formActividad [name=actividad]').val(a.actividad);
                $('#formActividad [name=descripcion]').val(a.descripcion || '');
                $('#formActividad [name=id_componente]').val(a.id_componente || 0);
                $('#formActividad [name=responsable]').val(a.responsable || '');
                $('#formActividad [name=observaciones]').val(a.observaciones || '');
                ['jun','jul','ago','sep','oct','nov','dic'].forEach(m => {
                    $(`#formActividad [name=mes_${m}]`).prop('checked', !!a['mes_'+m]);
                });
                $('#modalActTitle').text('Editar Actividad');
                modal.show();
            }
        });
    });

    $('#formActividad').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/cronograma_fp/save',
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
        SAG.confirmar('¿Eliminar esta actividad?', 'La acción quedará en bitácora.', () => {
            SAG.ajax({
                url: '/cronograma_fp/delete',
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
