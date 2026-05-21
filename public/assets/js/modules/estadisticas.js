/**
 * estadisticas.js — Módulo de Estadísticas
 * SAG Programas — sag_programas
 */
$(function () {
    'use strict';

    const MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const COLORES = ['#54668E','#7B8FB8','#14b8a6','#f5a623','#8b5cf6','#ef4444','#22c55e','#3b82f6','#ec4899','#f97316'];

    let charts = {};

    // ── TABS ────────────────────────────────────────
    document.querySelectorAll('.mode-tab[data-sec]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.mode-tab[data-sec]').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const id = 'panel' + this.dataset.sec.charAt(0).toUpperCase() + this.dataset.sec.slice(1);
            const panel = document.getElementById(id);
            if (panel) panel.classList.add('active');
        });
    });

    // ── CARGAR DATOS ─────────────────────────────────
    function cargarDatos() {
        const anio = $('#filtAnio').val();
        $('#lblActualizacion').text('Cargando...');

        SAG.ajaxGet('/estadisticas/datos', { anio: anio }, function (res) {
            if (!res.success) { SAG.toast('Error al cargar estadísticas.', 'error'); return; }

            const r = res.resumen || {};
            const anioSel = res.anio || anio;

            // KPI cards
            $('#kpiBeneficiarios').text(numFmt(r.beneficiarios || 0));
            $('#kpiOrganizaciones').text(numFmt(r.organizaciones || 0));
            $('#kpiCapacitaciones').text(numFmt(r.capacitaciones || 0));
            $('#kpiAsistencias').text(numFmt(r.asistencias || 0));

            // Beneficiarios panel
            $('#bTotalReg').text(numFmt(r.beneficiarios || 0));
            $('#bTotalActivos').text(numFmt(r.beneficiarios || 0));
            $('#bTotalDptos').text((res.porDepto || []).length);

            // Capacitaciones panel
            const totalPart = res.participantes_cap || sumarCampo(res.capMes, 'total_participantes');
            $('#cTotalEventos').text(numFmt(r.capacitaciones || 0));
            $('#cTotalPart').text(numFmt(totalPart));
            const prom = (r.capacitaciones > 0) ? Math.round(totalPart / r.capacitaciones) : 0;
            $('#cPromPart').text(numFmt(prom));
            $('#anioCapLabel, #anioCapPart').text(anioSel);

            // AT panel
            $('#aTotalAt').text(numFmt(r.asistencias || 0));
            $('#aTiposCount').text((res.atTipo || []).filter(t => parseInt(t.total) > 0).length);
            $('#anioAtLabel').text(anioSel);

            // Charts
            renderBar('chartActividad',   MESES, fillMeses(res.capMes,'total_eventos'), '#54668E', 'Capacitaciones');
            renderDoughnut('chartSexoResumen',  sexoLabels(res.sexo), sexoDatos(res.sexo), ['#3b82f6','#ec4899']);
            renderDoughnut('chartBeneSexo',     sexoLabels(res.sexo), sexoDatos(res.sexo), ['#3b82f6','#ec4899']);
            renderBarH('chartBeneDpto', (res.porDepto||[]).map(d=>d.departamento), (res.porDepto||[]).map(d=>d.total), COLORES);
            renderDoughnut('chartOrgTipoResumen', (res.orgTipo||[]).map(o=>o.tipo||'Sin tipo'), (res.orgTipo||[]).map(o=>o.total), COLORES);
            renderDoughnut('chartOrgTipo',        (res.orgTipo||[]).map(o=>o.tipo||'Sin tipo'), (res.orgTipo||[]).map(o=>o.total), COLORES);
            renderDoughnut('chartOrgEstado', (res.orgEstado||[]).map(o=>cap(o.estado||'otro')), (res.orgEstado||[]).map(o=>o.total), estadoColors(res.orgEstado||[]));
            renderBar('chartCapMes',  MESES, fillMeses(res.capMes,'total_eventos'),        '#14b8a6', 'Eventos');
            renderBar('chartCapPart', MESES, fillMeses(res.capMes,'total_participantes'),  '#3b82f6', 'Participantes');
            renderBar('chartAtMes',   MESES, fillMeses(res.atMes,'total'),                 '#8b5cf6', 'Asistencias');
            renderBarH('chartAtTipo', (res.atTipo||[]).map(t=>t.tipo), (res.atTipo||[]).map(t=>t.total), '#8b5cf6');

            renderRanking('rankingDptos',    res.porDepto || []);
            renderRanking('rankingDptosOrg', res.porDepto || []);

            $('#lblActualizacion').text('Actualizado: ' + new Date().toLocaleTimeString('es-HN'));
        });
    }

    // ── CHART HELPERS ────────────────────────────────
    function destruir(id) { if (charts[id]) { charts[id].destroy(); delete charts[id]; } }

    function renderBar(id, labels, data, color, label) {
        destruir(id);
        const ctx = document.getElementById(id);
        if (!ctx) return;
        charts[id] = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{ label, data, backgroundColor: color, borderRadius: 4 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ y:{beginAtZero:true} } }
        });
    }

    function renderBarH(id, labels, data, colors) {
        destruir(id);
        const ctx = document.getElementById(id);
        if (!ctx) return;
        charts[id] = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{ data, backgroundColor: Array.isArray(colors)?colors:colors, borderRadius: 3 }] },
            options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true}} }
        });
    }

    function renderDoughnut(id, labels, data, colors) {
        destruir(id);
        const ctx = document.getElementById(id);
        if (!ctx) return;
        charts[id] = new Chart(ctx, {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 2 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{position:'bottom', labels:{font:{size:10}}} } }
        });
    }

    // ── DATA HELPERS ──────────────────────────────────
    function numFmt(n) { return Number(n || 0).toLocaleString('es-HN'); }
    function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    function fillMeses(arr, campo) {
        const out = Array(12).fill(0);
        (arr || []).forEach(row => {
            const m = parseInt(row.mes);
            if (m >= 1 && m <= 12) out[m-1] = parseInt(row[campo]) || 0;
        });
        return out;
    }

    function sumarCampo(arr, campo) {
        return (arr || []).reduce((s, r) => s + (parseInt(r[campo]) || 0), 0);
    }

    function sexoLabels(sexo) { return ['Masculino','Femenino']; }
    function sexoDatos(sexo) {
        const m = (sexo||[]).find(s=>s.sexo==='M');
        const f = (sexo||[]).find(s=>s.sexo==='F');
        return [m?parseInt(m.total):0, f?parseInt(f.total):0];
    }

    function estadoColors(orgEstado) {
        const map = { activa:'#22c55e', inactiva:'#ef4444', pendiente:'#f5a623' };
        return (orgEstado||[]).map(o => map[o.estado] || '#94a3b8');
    }

    function renderRanking(containerId, porDepto) {
        const cont = document.getElementById(containerId);
        if (!cont) return;
        if (!porDepto.length) { cont.innerHTML = '<p style="color:#aaa;font-size:.82rem;padding:8px 0;">Sin datos disponibles.</p>'; return; }
        const max = Math.max(...porDepto.map(d => parseInt(d.total) || 0));
        const top = porDepto.slice(0, 8);
        cont.innerHTML = top.map((d, i) => `
            <div class="rank-row">
              <div class="rank-num">${i+1}</div>
              <div class="rank-label">${escHtml(d.departamento)}</div>
              <div class="prog-bar-wrap"><div class="prog-bar-fill" style="width:${max>0?Math.round(d.total/max*100):0}%"></div></div>
              <div class="rank-val">${numFmt(d.total)}</div>
            </div>`).join('');
    }

    function escHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── EVENTOS ───────────────────────────────────────
    $('#btnFiltrar, #btnActualizar').on('click', cargarDatos);

    // ── INIT ──────────────────────────────────────────
    cargarDatos();
});
