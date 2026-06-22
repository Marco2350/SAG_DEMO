/**
 * indicadores.js — CRUD de Indicadores por programa.
 */
$(function () {
    const BASE = SAG.BASE_URL;
    const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
    const modalInd = new bootstrap.Modal('#modalIndicador');

    let tabla;

    // ¿Hay select de Componente en el DOM? (sólo aparece en FPROG)
    const hasComponente = $('#filtroComponente').length > 0;

    function initTabla() {
        const columns = [
            { data: 'codigo',         width: '90px' },
            { data: 'nombre' },
            { data: 'tipo',           width: '100px' },
        ];
        if (hasComponente) {
            columns.push({ data: 'componente', width: '180px' });
        }
        columns.push(
            { data: 'meta_nombre' },
            { data: 'valor_objetivo', className: 'text-end' },
            { data: 'valor_actual',   className: 'text-end' },
            {
                data: 'avance_pct',
                className: 'text-center',
                render: pct => {
                    const color = pct >= 100 ? '#16a34a'
                                : pct >= 50  ? '#f59e0b'
                                : '#dc2626';
                    return `<span style="color:${color};font-weight:600;">${pct}%</span>`;
                },
            },
            {
                data: 'estado',
                className: 'text-center',
                render: e => `<span class="badge badge-estado">${e}</span>`,
            },
            {
                data: 'id_indicador',
                orderable: false,
                className: 'text-center',
                width: '110px',
                render: id => `
                    <button class="btn-outline btn-sm-icon btn-editar" data-id="${id}" title="Editar">
                        <i class="fas fa-pen"></i></button>
                    <button class="btn-danger-sm ms-1 btn-eliminar" data-id="${id}" title="Eliminar">
                        <i class="fas fa-trash"></i></button>`,
            }
        );

        tabla = $('#tablaIndicadores').DataTable({
            processing: true,
            ajax: {
                url: BASE + '/indicadores/listar',
                type: 'POST',
                data: d => {
                    d.tipo          = $('#filtroTipo').val();
                    d.estado        = $('#filtroEstado').val();
                    d.id_meta       = $('#filtroMeta').val();
                    d.id_componente = $('#filtroComponente').val() || 0;
                    d.buscar        = $('#filtroBuscar').val();
                    d._csrf         = CSRF();
                },
                dataSrc: 'data',
            },
            columns,
            language:   { url: (document.querySelector('meta[name="base-url"]')?.content || '') + '/public/assets/js/datatables/es-MX.json' },
            order:      [[1, 'asc']],
            pageLength: 15,
        });
    }
    initTabla();

    $('#filtroTipo, #filtroEstado, #filtroMeta, #filtroComponente').on('change', () => tabla.ajax.reload());
    let debounce;
    $('#filtroBuscar').on('keyup', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => tabla.ajax.reload(), 350);
    });

    $('#btnNuevoIndicador').on('click', () => {
        $('#formIndicador')[0].reset();
        $('#id_indicador').val(0);
        $('#modalIndTitle').text('Nuevo Indicador');
        modalInd.show();
    });

    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url: '/indicadores/get',
            data: { id },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const i = res.data.indicador;
                $('#formIndicador')[0].reset();
                $('#id_indicador').val(i.id_indicador);
                $('#formIndicador [name=codigo]').val(i.codigo || '');
                $('#formIndicador [name=nombre]').val(i.nombre);
                $('#formIndicador [name=descripcion]').val(i.descripcion || '');
                $('#formIndicador [name=tipo]').val(i.tipo);
                $('#formIndicador [name=id_meta]').val(i.id_meta || 0);
                $('#formIndicador [name=unidad_medida]').val(i.unidad_medida || '');
                $('#formIndicador [name=linea_base]').val(i.linea_base ?? '');
                $('#formIndicador [name=valor_objetivo]').val(i.valor_objetivo);
                $('#formIndicador [name=valor_actual]').val(i.valor_actual);
                $('#formIndicador [name=frecuencia_medicion]').val(i.frecuencia_medicion);
                $('#formIndicador [name=ultima_medicion]').val(i.ultima_medicion || '');
                $('#formIndicador [name=responsable]').val(i.responsable || '');
                $('#formIndicador [name=fuente_datos]').val(i.fuente_datos || '');
                $('#formIndicador [name=formula]').val(i.formula || '');
                $('#formIndicador [name=observaciones]').val(i.observaciones || '');
                $('#formIndicador [name=id_componente]').val(i.id_componente || 0);
                $('#modalIndTitle').text('Editar Indicador');
                modalInd.show();
            }
        });
    });

    $('#formIndicador').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/indicadores/save',
            data: $(this).serialize(),
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                modalInd.hide();
                tabla.ajax.reload(null, false);
            }
        });
    });

    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        SAG.confirmar('¿Eliminar este indicador?', 'Esta acción quedará registrada en bitácora.', () => {
            SAG.ajax({
                url: '/indicadores/delete',
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
