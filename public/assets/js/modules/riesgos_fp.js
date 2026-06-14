/**
 * riesgos_fp.js — Matriz de riesgos FPROG.
 */
$(function () {
    const BASE = SAG.BASE_URL;
    const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
    const modal = new bootstrap.Modal('#modalRiesgo');
    let tabla;

    const colorNivel = {
        critico: '#dc2626',
        alto:    '#f59e0b',
        medio:   '#3b82f6',
        bajo:    '#16a34a',
    };

    function nivelBadge(n) {
        const c = colorNivel[n] || '#6b7280';
        return `<span class="badge" style="background:${c};color:#fff;">${n.toUpperCase()}</span>`;
    }

    function estadoBadge(e) {
        return `<span class="badge badge-estado">${e.replace('_',' ')}</span>`;
    }

    tabla = $('#tablaRiesgos').DataTable({
        processing: true,
        ajax: {
            url: BASE + '/riesgos_fp/listar',
            type: 'POST',
            data: d => {
                d.categoria    = $('#filtroCategoria').val();
                d.probabilidad = $('#filtroProb').val();
                d.impacto      = $('#filtroImpacto').val();
                d.estado       = $('#filtroEstado').val();
                d.buscar       = $('#filtroBuscar').val();
                d._csrf        = CSRF();
            },
            dataSrc: 'data',
        },
        columns: [
            { data: 'categoria',         width: '100px' },
            { data: 'descripcion' },
            { data: 'probabilidad',      width: '70px',  className: 'text-center' },
            { data: 'impacto',           width: '70px',  className: 'text-center' },
            { data: 'nivel',             width: '90px',  className: 'text-center', render: nivelBadge },
            { data: 'medida_mitigacion' },
            { data: 'responsable',       width: '140px' },
            { data: 'estado',            width: '110px', className: 'text-center', render: estadoBadge },
            {
                data: 'id_riesgo', orderable: false, className: 'text-center', width: '90px',
                render: id => `
                    <button class="btn-outline btn-sm-icon btn-editar" data-id="${id}" title="Editar">
                        <i class="fas fa-pen"></i></button>
                    <button class="btn-danger-sm ms-1 btn-eliminar" data-id="${id}" title="Eliminar">
                        <i class="fas fa-trash"></i></button>`,
            },
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
        order:    [[4, 'asc']],  // por nivel
        pageLength: 15,
    });

    $('#filtroCategoria, #filtroProb, #filtroImpacto, #filtroEstado').on('change', () => tabla.ajax.reload());
    let debounce;
    $('#filtroBuscar').on('keyup', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => tabla.ajax.reload(), 350);
    });

    $('#btnNuevoRiesgo').on('click', () => {
        $('#formRiesgo')[0].reset();
        $('#id_riesgo').val(0);
        $('#formRiesgo [name=categoria]').val('operativo');
        $('#formRiesgo [name=probabilidad]').val('media');
        $('#formRiesgo [name=impacto]').val('medio');
        $('#modalRiesgoTitle').text('Nuevo Riesgo');
        modal.show();
    });

    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url: '/riesgos_fp/get',
            data: { id },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const r = res.data.riesgo;
                $('#formRiesgo')[0].reset();
                $('#id_riesgo').val(r.id_riesgo);
                $('#formRiesgo [name=categoria]').val(r.categoria);
                $('#formRiesgo [name=descripcion]').val(r.descripcion);
                $('#formRiesgo [name=probabilidad]').val(r.probabilidad);
                $('#formRiesgo [name=impacto]').val(r.impacto);
                $('#formRiesgo [name=medida_mitigacion]').val(r.medida_mitigacion || '');
                $('#formRiesgo [name=responsable]').val(r.responsable || '');
                $('#formRiesgo [name=observaciones]').val(r.observaciones || '');
                $('#modalRiesgoTitle').text('Editar Riesgo');
                modal.show();
            }
        });
    });

    $('#formRiesgo').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/riesgos_fp/save',
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
        SAG.confirmar('¿Eliminar este riesgo?', 'La acción quedará en bitácora.', () => {
            SAG.ajax({
                url: '/riesgos_fp/delete',
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
