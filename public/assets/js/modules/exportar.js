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
        $('#fModulo').val(modulo);
        $('#fDpto').val(dpto);
        $('#fEstado').val(estado);
        $('#fAnio').val(anio);
        document.getElementById('formExportar').submit();
        registrarHistorial(modulo, dpto, estado, anio);
    }

    // ── LIMPIAR FILTROS ───────────────────────────────
    $('#btnLimpiarFiltros').on('click', function () {
        $('#expModulo, #expDpto, #expEstado, #expAnio').val('');
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
