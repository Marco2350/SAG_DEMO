/**
 * exportar.js — Módulo de Exportación de Datos
 * SAG Programas — sag_programas
 */
$(function () {
    'use strict';

    let historial = [];

    const NOMBRES = {
        beneficiarios: 'Beneficiarios',
        organizaciones: 'Organizaciones',
        capacitaciones: 'Capacitaciones',
        asistencias: 'Asistencia Técnica'
    };

    // Estados válidos por módulo. Cada módulo usa su propio enum, así que el
    // filtro de estado debe ofrecer exactamente esos valores; de lo contrario
    // (p.ej. 'activo' contra organizaciones que usa 'activa') la consulta no
    // encuentra nada y la exportación sale vacía.
    const ESTADOS = {
        beneficiarios:  [['activo', 'Activo'], ['inactivo', 'Inactivo']],
        organizaciones: [['activa', 'Activa'], ['pendiente', 'Pendiente'], ['inactiva', 'Inactiva']],
        capacitaciones: [['borrador', 'Borrador'], ['finalizado', 'Finalizado']],
        asistencias:    [['borrador', 'Borrador'], ['finalizado', 'Finalizado']]
    };

    // El filtro de año solo aplica a módulos con fecha propia. Organizaciones
    // no filtra por año en el backend, así que se deshabilita para no confundir.
    const MODULOS_SIN_ANIO = ['organizaciones'];

    // ── ESTADO/AÑO DEPENDIENTES DEL MÓDULO ────────────
    $('#expModulo').on('change', function () {
        const modulo = $(this).val();

        // Reconstruir opciones de estado para el módulo elegido
        const $est = $('#expEstado');
        $est.html('<option value="">Todos los estados</option>');
        (ESTADOS[modulo] || []).forEach(function (e) {
            $est.append('<option value="' + e[0] + '">' + e[1] + '</option>');
        });
        $est.val('');

        // Año: deshabilitar donde no aplica
        const $anio = $('#expAnio');
        if (MODULOS_SIN_ANIO.indexOf(modulo) !== -1) {
            $anio.val('').prop('disabled', true);
        } else {
            $anio.prop('disabled', false);
        }
    });

    // ── EXPORTAR CSV RÁPIDO (sin filtros) ─────────────
    window.exportarCSV = function (modulo) {
        lanzarDescarga(modulo, '', '', '');
    };

    // ── EXPORTAR FILTRADO ─────────────────────────────
    $('#btnExportarFiltrado').on('click', function () {
        const modulo = $('#expModulo').val();
        if (!modulo) { SAG.toast('Selecciona un módulo primero.', 'warning'); return; }
        const dpto   = $('#expDpto').val();
        const estado = $('#expEstado').val();
        const anio   = $('#expAnio').val();
        lanzarDescarga(modulo, dpto, estado, anio);
    });

    // ── EXPORTAR TODO ─────────────────────────────────
    $('#btnExportarTodo').on('click', function () {
        const modulos = ['beneficiarios', 'organizaciones', 'capacitaciones', 'asistencias'];
        modulos.forEach(m => lanzarDescarga(m, '', '', ''));
        SAG.toast('Iniciando descarga de 4 archivos CSV…', 'success');
    });

    // ── LANZAR DESCARGA ───────────────────────────────
    function lanzarDescarga(modulo, dpto, estado, anio) {
        // Pre-chequeo: contar registros que coinciden ANTES de descargar.
        // Si no hay ninguno, avisamos y no descargamos un archivo vacío.
        SAG.ajax({
            url:  '/exportar/contar',
            data: { modulo: modulo, id_departamento: dpto, estado: estado, anio: anio },
            success: function (res) {
                if (!res.success) {
                    SAG.toast(res.message || 'No se pudo verificar los registros.', 'error');
                    return;
                }
                const total = (res.data && res.data.total) || 0;
                if (total === 0) {
                    SAG.toast('No hay registros que coincidan con esos filtros.', 'warning');
                    return;
                }

                // Hay datos → enviar el formulario para la descarga real.
                // Refrescar el token CSRF desde el meta vivo antes de enviar.
                if (window.SAG && SAG.CSRF) $('#fCsrf').val(SAG.CSRF);
                $('#fModulo').val(modulo);
                $('#fDpto').val(dpto);
                $('#fEstado').val(estado);
                $('#fAnio').val(anio);
                document.getElementById('formExportar').submit();
                registrarHistorial(modulo, dpto, estado, anio);
            }
        });
    }

    // ── LIMPIAR FILTROS ───────────────────────────────
    $('#btnLimpiarFiltros').on('click', function () {
        $('#expModulo, #expDpto, #expEstado, #expAnio').val('');
        $('#expEstado').html('<option value="">Todos los estados</option>');
        $('#expAnio').prop('disabled', false);
    });

    // ── HISTORIAL ─────────────────────────────────────
    function registrarHistorial(modulo, dpto, estado, anio) {
        const ahora = new Date().toLocaleString('es-HN');
        const filtros = [dpto ? 'Depto' : '', estado, anio ? anio : ''].filter(Boolean).join(' · ') || 'Sin filtros';
        historial.unshift({
            modulo: NOMBRES[modulo] || modulo,
            filtros,
            fecha: ahora
        });
        renderHistorial();
    }

    function renderHistorial() {
        const cont = document.getElementById('historialLog');
        if (!historial.length) {
            cont.innerHTML = `<div style="text-align:center;padding:20px;color:#aaa;font-size:.84rem;">
                <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                Aún no se han realizado exportaciones en esta sesión.
            </div>`;
            return;
        }
        cont.innerHTML = historial.map(h => `
            <div class="log-row">
              <div class="log-icon csv"><i class="fas fa-file-csv"></i></div>
              <div class="log-info">
                <strong>${h.modulo}</strong>
                <span>CSV · ${h.filtros}</span>
              </div>
              <div style="font-size:.74rem;color:#aaa;flex-shrink:0;">${h.fecha}</div>
            </div>`).join('');
    }

    $('#btnLimpiarHistorial').on('click', function () {
        historial = [];
        renderHistorial();
        SAG.toast('Historial limpiado.', 'success');
    });

});
