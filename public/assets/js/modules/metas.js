/**
 * metas.js — CRUD de Metas por programa.
 * Patrón SAG_DEMO: SAG.ajax inyecta CSRF automáticamente; sin alert()/confirm().
 */
$(function () {
    const BASE = SAG.BASE_URL;
    const CSRF = () => document.querySelector('meta[name="csrf-token"]').content;
    const modalMeta = new bootstrap.Modal('#modalMeta');

    let tabla;

    // ¿Hay select de Componente en el DOM? (sólo aparece en FPROG)
    const hasComponente = $('#filtroComponente').length > 0;

    // ── DataTable ─────────────────────────────────────────────────
    function initTabla() {
        // Columnas dinámicas: 'componente' sólo se incluye si la vista tiene
        // el header (mismo número de <th> y <td> para todos los programas)
        const columns = [
            { data: 'codigo',         width: '90px' },
            { data: 'nombre' },
        ];
        if (hasComponente) {
            columns.push({ data: 'componente', width: '180px' });
        }
        columns.push(
            { data: 'periodo',        width: '110px' },
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
                data: 'id_meta',
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

        tabla = $('#tablaMetas').DataTable({
            processing: true,
            ajax: {
                url: BASE + '/metas/listar',
                type: 'POST',
                data: d => {
                    d.estado        = $('#filtroEstado').val();
                    d.periodo       = $('#filtroPeriodo').val();
                    d.id_componente = $('#filtroComponente').val() || 0;
                    d.buscar        = $('#filtroBuscar').val();
                    d._csrf         = CSRF();
                },
                dataSrc: 'data',
            },
            columns,
            language:   { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
            order:      [[1, 'asc']],
            pageLength: 15,
        });
    }
    initTabla();

    // ── Filtros (recarga la tabla) ────────────────────────────────
    $('#filtroEstado, #filtroPeriodo, #filtroComponente').on('change', () => tabla.ajax.reload());
    let debounce;
    $('#filtroBuscar').on('keyup', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => tabla.ajax.reload(), 350);
    });

    // ── Nueva ─────────────────────────────────────────────────────
    $('#btnNuevaMeta').on('click', () => {
        $('#formMeta')[0].reset();
        $('#id_meta').val(0);
        $('#modalMetaTitle').text('Nueva Meta');
        modalMeta.show();
    });

    // ── Editar ────────────────────────────────────────────────────
    $(document).on('click', '.btn-editar', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url: '/metas/get',
            data: { id },
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const m = res.data.meta;
                $('#formMeta')[0].reset();
                $('#id_meta').val(m.id_meta);
                $('#formMeta [name=codigo]').val(m.codigo || '');
                $('#formMeta [name=nombre]').val(m.nombre);
                $('#formMeta [name=descripcion]').val(m.descripcion || '');
                $('#formMeta [name=unidad_medida]').val(m.unidad_medida || '');
                $('#formMeta [name=valor_objetivo]').val(m.valor_objetivo);
                $('#formMeta [name=valor_actual]').val(m.valor_actual);
                $('#formMeta [name=periodo]').val(m.periodo);
                $('#formMeta [name=fecha_inicio]').val(m.fecha_inicio || '');
                $('#formMeta [name=fecha_fin]').val(m.fecha_fin || '');
                $('#formMeta [name=responsable]').val(m.responsable || '');
                $('#formMeta [name=fuente_verificacion]').val(m.fuente_verificacion || '');
                $('#formMeta [name=observaciones]').val(m.observaciones || '');
                $('#formMeta [name=id_componente]').val(m.id_componente || 0);
                $('#modalMetaTitle').text('Editar Meta');
                modalMeta.show();
            }
        });
    });

    // ── Guardar ───────────────────────────────────────────────────
    $('#formMeta').on('submit', function (e) {
        e.preventDefault();
        SAG.ajax({
            url: '/metas/save',
            data: $(this).serialize(),
            success: res => {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                modalMeta.hide();
                tabla.ajax.reload(null, false);
            }
        });
    });

    // ── Eliminar (con modal de confirmación reutilizable) ─────────
    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        SAG.confirmar('¿Eliminar esta meta?', 'Esta acción quedará registrada en bitácora.', () => {
            SAG.ajax({
                url: '/metas/delete',
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
