/**
 * SAG Honduras Sin Hambre — main.js
 * Utilidades globales compartidas por todos los módulos
 */
const SAG = (function () {
  'use strict';

  const BASE = (function () {
    const m = document.querySelector('meta[name="base-url"]');
    return m ? m.content : window.BASE_URL || '';
  })();

  // ──────────────────────────────────────────────────
  //  AJAX helper
  // ──────────────────────────────────────────────────
  function ajax(url, data, callback, method) {
    // Soporta dos formas de llamada:
    // 1) SAG.ajax('/ruta', data, callback)
    // 2) SAG.ajax({ url, data, success, error, method })
    if (url && typeof url === 'object') {
      var opts = url;
      method   = opts.method   || 'POST';
      callback = opts.success  || null;
      var errCb = opts.error   || null;
      data     = opts.data;
      url      = opts.url;
      return $.ajax({
        url:     BASE + url,
        type:    method,
        data:    data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function (res) { if (typeof callback === 'function') callback(res); },
        error:   function () {
          toast('Error de conexión. Intente nuevamente.', 'error');
          if (typeof errCb  === 'function') errCb();
          if (typeof callback === 'function') callback({ success: false, message: 'Error de conexión.' });
        }
      });
    }
    method = method || 'POST';
    $.ajax({
      url:     BASE + url,
      type:    method,
      data:    data,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success: function (res) {
        if (typeof callback === 'function') callback(res);
      },
      error: function () {
        toast('Error de conexión. Intente nuevamente.', 'error');
        if (typeof callback === 'function') callback({ success: false, message: 'Error de conexión.' });
      }
    });
  }

  function ajaxGet(url, data, callback) {
    // Soporta también forma de objeto: SAG.ajaxGet('/ruta', callback)
    if (typeof data === 'function') { callback = data; data = {}; }
    ajax(url, data, callback, 'GET');
  }

  // ──────────────────────────────────────────────────
  //  TOAST
  // ──────────────────────────────────────────────────
  let _toastTimer;
  function toast(msg, tipo) {
    tipo = tipo || 'success';
    let el = document.getElementById('sag-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'sag-toast';
      document.body.appendChild(el);
    }
    el.className = 'sag-toast sag-toast-' + tipo;
    el.innerHTML =
      '<i class="fas ' + (tipo === 'success' ? 'fa-circle-check' : tipo === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark') + '"></i>' +
      '<span>' + msg + '</span>';
    el.classList.add('show');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(function () { el.classList.remove('show'); }, 3800);
  }

  // ──────────────────────────────────────────────────
  //  CONFIRM overlay
  // ──────────────────────────────────────────────────
  function confirm(msg, onYes, onNo) {
    let overlay = document.getElementById('sag-confirm');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'sag-confirm';
      overlay.innerHTML =
        '<div class="sc-box">' +
          '<div class="sc-icon"><i class="fas fa-triangle-exclamation"></i></div>' +
          '<p class="sc-msg"></p>' +
          '<div class="sc-btns">' +
            '<button class="sc-no">Cancelar</button>' +
            '<button class="sc-yes">Confirmar</button>' +
          '</div>' +
        '</div>';
      document.body.appendChild(overlay);
    }
    overlay.querySelector('.sc-msg').textContent = msg;
    overlay.style.display = 'flex';

    const yesBtn = overlay.querySelector('.sc-yes');
    const noBtn  = overlay.querySelector('.sc-no');

    function cleanup() { overlay.style.display = 'none'; yesBtn.onclick = null; noBtn.onclick = null; }
    yesBtn.onclick = function () { cleanup(); if (typeof onYes === 'function') onYes(); };
    noBtn.onclick  = function () { cleanup(); if (typeof onNo  === 'function') onNo();  };
  }

  // ──────────────────────────────────────────────────
  //  Botón loading
  // ──────────────────────────────────────────────────
  function btnLoading(btn, on) {
    // Acepta selector string ('#btnGuardar'), elemento jQuery o elemento DOM
    const el = (typeof btn === 'string')
      ? document.querySelector(btn)
      : (btn && btn.jquery ? btn[0] : btn);
    if (!el) return;
    if (on) {
      el.dataset.orig = el.innerHTML;
      el.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Procesando...';
      el.disabled = true;
    } else {
      el.innerHTML = el.dataset.orig || el.innerHTML;
      el.disabled = false;
    }
  }

  // ──────────────────────────────────────────────────
  //  Cargar municipios por departamento
  // ──────────────────────────────────────────────────
  // Tercer argumento: puede ser un ID a preseleccionar (número/string) o texto placeholder
  function loadMunicipios(deptoId, target, selected) {
    const useJq = typeof $ !== 'undefined';
    const sel   = typeof target === 'string'
        ? (useJq ? $(target)[0] : document.getElementById(target.replace('#', '')))
        : target;
    if (!sel) return;
    sel.innerHTML = '<option value="">Cargando...</option>';
    sel.disabled  = true;

    ajaxGet('/api/municipios', { depto_id: deptoId }, function (res) {
      sel.innerHTML = '<option value="">— Seleccione municipio —</option>';
      if (res.success && res.data) {
        res.data.forEach(function (m) {
          const opt      = document.createElement('option');
          opt.value      = m.id_municipio;
          opt.textContent = m.nombre;
          // Preseleccionar si selected es numérico/string de ID
          if (selected && String(m.id_municipio) === String(selected)) {
            opt.selected = true;
          }
          sel.appendChild(opt);
        });
      }
      sel.disabled = false;
      if (useJq && $(sel).data('select2')) $(sel).trigger('change');
    });
  }

  // ──────────────────────────────────────────────────
  //  Cargar subtemas por tema (3er arg = ID a preseleccionar)
  // ──────────────────────────────────────────────────
  function loadSubtemas(temaId, target, selected) {
    const useJq = typeof $ !== 'undefined';
    const sel   = typeof target === 'string'
        ? (useJq ? $(target)[0] : document.getElementById(target.replace('#', '')))
        : target;
    if (!sel) return;
    sel.innerHTML = '<option value="">Cargando...</option>';
    sel.disabled  = true;

    ajaxGet('/api/subtemas', { tema_id: temaId }, function (res) {
      sel.innerHTML = '<option value="">— Seleccione subtema —</option>';
      if (res.success && res.data) {
        res.data.forEach(function (s) {
          const opt       = document.createElement('option');
          opt.value       = s.id_subtema;
          opt.textContent = s.nombre;
          if (selected && String(s.id_subtema) === String(selected)) {
            opt.selected = true;
          }
          sel.appendChild(opt);
        });
      }
      sel.disabled = false;
    });
  }

  // ──────────────────────────────────────────────────
  //  Formatear DNI hondureño (0801-AAAA-NNNNN)
  // ──────────────────────────────────────────────────
  function formatDNI(val) {
    val = val.replace(/\D/g, '').substring(0, 13);
    if (val.length > 8) return val.substring(0, 4) + '-' + val.substring(4, 8) + '-' + val.substring(8);
    if (val.length > 4) return val.substring(0, 4) + '-' + val.substring(4);
    return val;
  }

  // ──────────────────────────────────────────────────
  //  Formatear teléfono (####-####)
  // ──────────────────────────────────────────────────
  function formatTel(val) {
    val = val.replace(/\D/g, '').substring(0, 8);
    if (val.length > 4) return val.substring(0, 4) + '-' + val.substring(4);
    return val;
  }

  // ──────────────────────────────────────────────────
  //  DataTable helper
  // ──────────────────────────────────────────────────
  function dataTable(selector, options) {
    const defaults = {
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
      },
      pageLength: 10,
      dom: '<"row"<"col-sm-4"l><"col-sm-4"f><"col-sm-4 text-end"B>>rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
      responsive: true
    };
    return $(selector).DataTable($.extend(true, defaults, options || {}));
  }

  // ──────────────────────────────────────────────────
  //  Init Select2 helper
  // ──────────────────────────────────────────────────
  function initSelect2(selector, placeholder) {
    $(selector).select2({
      theme: 'bootstrap-5',
      placeholder: placeholder || 'Seleccione...',
      allowClear: true,
      width: '100%'
    });
  }

  // Exponer API pública
  return {
    ajax: ajax,
    ajaxGet: ajaxGet,
    toast: toast,
    confirm: confirm,
    btnLoading: btnLoading,
    loadMunicipios: loadMunicipios,
    loadSubtemas: loadSubtemas,
    formatDNI: formatDNI,
    formatTel: formatTel,
    dataTable: dataTable,
    initSelect2: initSelect2,
    BASE: BASE,
    BASE_URL: BASE
  };
})();
