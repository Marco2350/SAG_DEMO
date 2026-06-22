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
  //  CSRF token — leído del meta del header
  // ──────────────────────────────────────────────────
  const CSRF = (function () {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.content : '';
  })();

  // Configurar jQuery para enviar siempre el header X-CSRF-Token en peticiones unsafe
  if (typeof $ !== 'undefined' && CSRF) {
    $.ajaxSetup({
      headers: { 'X-CSRF-Token': CSRF }
    });
  }

  // Helper: agregar el token a un objeto/string/FormData de payload
  function withCsrf(data) {
    if (!CSRF) return data;
    // FormData: usar append
    if (typeof FormData !== 'undefined' && data instanceof FormData) {
      if (!data.has('_csrf')) data.append('_csrf', CSRF);
      return data;
    }
    // Objeto plano
    if (data && typeof data === 'object') {
      if (data._csrf === undefined) data._csrf = CSRF;
      return data;
    }
    // String tipo "a=1&b=2"
    if (typeof data === 'string') {
      if (data.indexOf('_csrf=') === -1) {
        return (data ? data + '&' : '') + '_csrf=' + encodeURIComponent(CSRF);
      }
      return data;
    }
    // null/undefined → crear objeto
    return { _csrf: CSRF };
  }

  // ──────────────────────────────────────────────────
  //  AJAX helper (con CSRF automático en POST)
  //  Los fallos se traducen a mensajes accionables según el estado HTTP.
  // ──────────────────────────────────────────────────
  function ajaxErrorMsg(xhr) {
    // Si el servidor envió un mensaje JSON (p.ej. 419 CSRF), usarlo
    const srv = xhr && xhr.responseJSON && xhr.responseJSON.message;
    switch (xhr && xhr.status) {
      case 0:   return 'Sin conexión con el servidor. Verifique su red e intente de nuevo.';
      case 401: return srv || 'Su sesión expiró. Vuelva a iniciar sesión.';
      case 403: return srv || 'No tiene permisos para realizar esta acción.';
      case 404: return 'No se encontró el recurso solicitado. Recargue la página.';
      case 419: return srv || 'Su sesión de seguridad caducó. Recargue la página (F5) e intente de nuevo.';
      case 500: return 'Ocurrió un error en el servidor. Si persiste, contacte al administrador.';
      default:  return 'Error de conexión. Intente nuevamente.';
    }
  }

  function handleAjaxError(xhr) {
    toast(ajaxErrorMsg(xhr), 'error');
    // Sesión vencida: llevar al login tras dar tiempo de leer el aviso
    if (xhr && xhr.status === 401) {
      setTimeout(function () { window.location.href = BASE + '/auth/login?timeout=1'; }, 1800);
    }
  }

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
      // Inyectar CSRF en peticiones que cambian estado
      if (method.toUpperCase() !== 'GET') data = withCsrf(data);
      return $.ajax({
        url:     BASE + url,
        type:    method,
        data:    data,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF },
        success: function (res) { if (typeof callback === 'function') callback(res); },
        error:   function (xhr) {
          handleAjaxError(xhr);
          if (typeof errCb  === 'function') errCb(xhr);
          if (typeof callback === 'function') callback({ success: false, message: ajaxErrorMsg(xhr) });
        }
      });
    }
    method = method || 'POST';
    if (method.toUpperCase() !== 'GET') data = withCsrf(data);
    $.ajax({
      url:     BASE + url,
      type:    method,
      data:    data,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF },
      success: function (res) {
        if (typeof callback === 'function') callback(res);
      },
      error: function (xhr) {
        handleAjaxError(xhr);
        if (typeof callback === 'function') callback({ success: false, message: ajaxErrorMsg(xhr) });
      }
    });
  }

  function ajaxGet(url, data, callback) {
    // Soporta también forma de objeto: SAG.ajaxGet('/ruta', callback)
    if (typeof data === 'function') { callback = data; data = {}; }
    ajax(url, data, callback, 'GET');
  }

  function post(url, data, callback) {
    if (BASE && url.indexOf(BASE) === 0) url = url.slice(BASE.length);
    ajax(url, data, callback, 'POST');
  }

  function formData(selector) {
    const form = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!form) return {};
    return Object.fromEntries(new FormData(form).entries());
  }

  // ──────────────────────────────────────────────────
  //  TOAST
  //  Éxitos se ocultan rápido; errores y advertencias dan
  //  más tiempo de lectura y siempre pueden cerrarse a mano.
  // ──────────────────────────────────────────────────
  let _toastTimer;
  const TOAST_MS = { success: 3500, warning: 5500, error: 7000 };

  function toast(msg, tipo) {
    tipo = TOAST_MS[tipo] ? tipo : 'success';
    let el = document.getElementById('sag-toast');
    if (!el) {
      el = document.createElement('div');
      el.id = 'sag-toast';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      document.body.appendChild(el);
    }
    el.className = 'sag-toast ' + tipo;
    el.innerHTML =
      '<i class="fas ' + (tipo === 'success' ? 'fa-circle-check' : tipo === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark') + '"></i>' +
      '<span></span>' +
      '<button type="button" class="st-close" aria-label="Cerrar"><i class="fas fa-xmark"></i></button>';
    el.querySelector('span').textContent = msg;
    el.querySelector('.st-close').onclick = function () {
      clearTimeout(_toastTimer);
      el.classList.remove('show');
    };
    el.classList.add('show');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(function () { el.classList.remove('show'); }, TOAST_MS[tipo]);
  }

  // ──────────────────────────────────────────────────
  //  CONFIRM overlay
  // ──────────────────────────────────────────────────
  function confirm(msg, onYes, onNo) {
    let overlay = document.getElementById('sag-confirm');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'sag-confirm';
      overlay.setAttribute('role', 'alertdialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.innerHTML =
        '<div class="sc-box">' +
          '<div class="sc-icon"><i class="fas fa-triangle-exclamation"></i></div>' +
          '<p class="sc-msg"></p>' +
          '<div class="sc-btns">' +
            '<button type="button" class="sc-no">Cancelar</button>' +
            '<button type="button" class="sc-yes">Confirmar</button>' +
          '</div>' +
        '</div>';
      document.body.appendChild(overlay);
    }
    overlay.querySelector('.sc-msg').textContent = msg;
    overlay.classList.add('show');

    const yesBtn = overlay.querySelector('.sc-yes');
    const noBtn  = overlay.querySelector('.sc-no');
    const prevFocus = document.activeElement;

    function cleanup() {
      overlay.classList.remove('show');
      yesBtn.onclick = null; noBtn.onclick = null; overlay.onclick = null;
      document.removeEventListener('keydown', onKey);
      if (prevFocus && typeof prevFocus.focus === 'function') prevFocus.focus();
    }
    function onKey(e) {
      if (e.key === 'Escape') { cleanup(); if (typeof onNo === 'function') onNo(); }
      if (e.key === 'Enter' && document.activeElement !== noBtn) {
        cleanup(); if (typeof onYes === 'function') onYes();
      }
    }

    yesBtn.onclick = function () { cleanup(); if (typeof onYes === 'function') onYes(); };
    noBtn.onclick  = function () { cleanup(); if (typeof onNo  === 'function') onNo();  };
    // Clic en el fondo = cancelar (el clic dentro de la caja no cierra)
    overlay.onclick = function (e) {
      if (e.target === overlay) { cleanup(); if (typeof onNo === 'function') onNo(); }
    };
    document.addEventListener('keydown', onKey);
    // Foco inicial en Cancelar: la acción destructiva exige intención
    noBtn.focus();
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
          // Guarda codigo del municipio para que se puedan cargar aldeas oficiales
          if (m.codigo) opt.setAttribute('data-codigo', m.codigo);
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
  //  Cargar aldeas oficiales del catálogo nacional
  //  Args: codigoMunicipio (ej '0101'), targetSelect, selected (opcional)
  //  Si el select de municipio NO tiene 'data-codigo' (catálogo no migrado),
  //  el caller debe hacer fallback a input libre.
  // ──────────────────────────────────────────────────
  function loadAldeas(codigoMuni, target, selected) {
    const useJq = typeof $ !== 'undefined';
    const sel   = typeof target === 'string'
        ? (useJq ? $(target)[0] : document.getElementById(target.replace('#', '')))
        : target;
    if (!sel) return;
    sel.innerHTML = '<option value="">Cargando aldeas...</option>';
    sel.disabled  = true;

    if (!codigoMuni) {
      sel.innerHTML = '<option value="">— Seleccione municipio primero —</option>';
      sel.disabled  = false;
      return;
    }

    ajaxGet('/api/aldeas', { codigo_municipio: codigoMuni }, function (res) {
      sel.innerHTML = '<option value="">— Seleccione aldea —</option>';
      if (res.success && res.data && res.data.length) {
        res.data.forEach(function (a) {
          const opt = document.createElement('option');
          opt.value = a.nombre;            // valor = nombre (compatible con campo texto antiguo)
          opt.textContent = a.nombre;
          opt.setAttribute('data-codigo', a.codigo);
          if (selected && (String(a.nombre).toUpperCase() === String(selected).toUpperCase()
                        || String(a.codigo) === String(selected))) {
            opt.selected = true;
          }
          sel.appendChild(opt);
        });
      } else {
        // Sin aldeas en catálogo para este municipio
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = '(Sin aldeas en catálogo — escriba en el campo libre)';
        sel.appendChild(opt);
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
      if (useJq && $(sel).data('select2')) $(sel).trigger('change');
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
        url: BASE + '/public/assets/js/datatables/es-MX.json'
      },
      pageLength: 10,
      dom: '<"row"<"col-sm-4"l><"col-sm-4"f><"col-sm-4 text-end"B>>rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
      responsive: true
    };
    return $(selector).DataTable($.extend(true, defaults, options || {}));
  }

  // ──────────────────────────────────────────────────
  //  Init Select2 helper
  //  parent: selector del modal contenedor — obligatorio dentro de
  //  modales Bootstrap para que el dropdown no quede atrapado/recortado.
  // ──────────────────────────────────────────────────
  function initSelect2(selector, placeholder, parent) {
    if (typeof $.fn.select2 === 'undefined') return; // CDN no disponible: el select nativo sigue funcionando
    const opts = {
      theme: 'bootstrap-5',
      placeholder: placeholder || 'Seleccione...',
      allowClear: true,
      width: '100%',
      language: {
        noResults:  function () { return 'Sin resultados'; },
        searching:  function () { return 'Buscando…'; },
      },
    };
    if (parent) opts.dropdownParent = $(parent);
    $(selector).select2(opts);
  }

  // Refresca la vista de los select2 de un contenedor tras setear
  // valores por código (.val() no actualiza el control visualmente).
  function refreshSelect2(container) {
    $(container).find('select').each(function () {
      if ($(this).data('select2')) $(this).trigger('change');
    });
  }

  // ── Cards de resumen (mini-stats) en vivo ──────────────────────
  // Repinta los cards superiores de cada módulo a partir del objeto
  // `resumen` que ahora devuelven los endpoints /listar. Cada card lleva
  // data-stat="clave" (qué valor mostrar) y, opcionalmente, data-stat-fmt
  // para el formato. El formateo replica number_format() de PHP (miles ','
  // y decimal '.', estilo en-US).
  function formatStat(v, fmt) {
    var n = Number(v) || 0;
    switch (fmt) {
      case 'pct1':     return n.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
      case 'money2':   return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      case 'moneyL0':  return 'L. ' + n.toLocaleString('en-US', { maximumFractionDigits: 0 });
      case 'intgroup': return n.toLocaleString('en-US', { maximumFractionDigits: 0 });
      case 'int':
      default:         return String(Math.round(n));
    }
  }
  function updateStats(resumen) {
    if (!resumen || typeof resumen !== 'object') return;
    document.querySelectorAll('[data-stat]').forEach(function (el) {
      var key = el.getAttribute('data-stat');
      if (!(key in resumen)) return;
      el.textContent = formatStat(resumen[key], el.getAttribute('data-stat-fmt') || 'int');
    });
  }
  // Listener global: el evento xhr.dt de DataTables propaga hasta document,
  // así que un solo handler cubre todos los módulos. Cada vez que una tabla
  // recarga su Ajax (tras agregar/editar/eliminar/filtrar) repinta los cards.
  $(document).on('xhr.dt', function (e, settings, json) {
    if (json && json.resumen) updateStats(json.resumen);
  });

  // Exponer API pública
  return {
    ajax: ajax,
    ajaxGet: ajaxGet,
    post: post,
    formData: formData,
    toast: toast,
    confirm: confirm,
    btnLoading: btnLoading,
    loadMunicipios: loadMunicipios,
    loadAldeas:     loadAldeas,
    loadSubtemas: loadSubtemas,
    formatDNI: formatDNI,
    formatTel: formatTel,
    dataTable: dataTable,
    initSelect2: initSelect2,
    refreshSelect2: refreshSelect2,
    updateStats: updateStats,
    BASE: BASE,
    BASE_URL: BASE,
    CSRF: CSRF,
    withCsrf: withCsrf,
    // ── Borradores / autoguardado ──
    autosaveAttach:  autosaveAttach,
    autosaveClear:   autosaveClear,
    autosaveRestore: autosaveRestore
  };

  // ════════════════════════════════════════════════════════════
  //  BORRADORES — autoguardado en localStorage
  //
  //  Uso (HTML):
  //    <form id="formAT" data-sag-autosave="form-at"> ... </form>
  //
  //  Uso (JS):
  //    SAG.autosaveAttach('#formAT', 'form-at');
  //    SAG.autosaveRestore('#formAT', 'form-at'); // restaura
  //    SAG.autosaveClear('form-at');              // limpia tras submit
  //
  //  Características:
  //    - Guarda cada 2.5s mientras el usuario teclea
  //    - Llave: 'sag_draft_' + key (por usuario implícito en el navegador)
  //    - Persiste en localStorage del navegador (no envía a servidor)
  //    - Excluye campos password y file
  // ════════════════════════════════════════════════════════════
  function autosaveAttach(selector, key) {
    if (typeof window === 'undefined' || !window.localStorage) return;
    const $form = (typeof $ !== 'undefined') ? $(selector) : null;
    if (!$form || !$form.length) return;
    let t;

    function guardarBorrador() {
      try {
        const data = {};
        let tieneContenido = false;
        $form.find(':input').each(function () {
          const name = this.name || this.id;
          if (!name || name === '_csrf') return;
          const tipo = (this.type || '').toLowerCase();
          if (['password', 'file', 'hidden', 'submit', 'button'].includes(tipo)) return;
          if (tipo === 'checkbox' || tipo === 'radio') {
            if (this.checked) {
              data[name] = this.value;
              tieneContenido = true;
            }
          } else {
            data[name] = this.value;
            if (String(this.value || '').trim() !== '') tieneContenido = true;
          }
        });
        if (!tieneContenido) {
          autosaveClear(key);
          return;
        }
        data.__ts = Date.now();
        localStorage.setItem('sag_draft_' + key, JSON.stringify(data));
      } catch (e) { /* localStorage lleno / modo privado */ }
    }

    $form.on('input change', function () {
      clearTimeout(t);
      t = setTimeout(guardarBorrador, 700);
    });
    window.addEventListener('beforeunload', guardarBorrador);
  }

  function autosaveClear(key) {
    try { localStorage.removeItem('sag_draft_' + key); } catch (e) { /* no-op */ }
  }

  /**
   * Devuelve true si se restauró algo, false si no había nada.
   * Si recibe onPrompt(antiguedadMs) → función que pregunta al usuario;
   * si onPrompt devuelve false, NO restaura.
   */
  function autosaveRestore(selector, key, onPrompt) {
    if (typeof window === 'undefined' || !window.localStorage) return false;
    let raw;
    try { raw = localStorage.getItem('sag_draft_' + key); } catch (e) { return false; }
    if (!raw) return false;
    let data;
    try { data = JSON.parse(raw); } catch (e) { return false; }
    const ts = data.__ts || 0;
    const antiguedadMs = Date.now() - ts;
    if (typeof onPrompt === 'function') {
      if (onPrompt(antiguedadMs) === false) return false;
    }
    const $form = (typeof $ !== 'undefined') ? $(selector) : null;
    if (!$form || !$form.length) return false;
    $form.find(':input').each(function () {
      const name = this.name || this.id;
      if (!name || !(name in data)) return;
      const tipo = (this.type || '').toLowerCase();
      if (tipo === 'password' || tipo === 'file' || tipo === 'hidden') return;
      if (tipo === 'checkbox' || tipo === 'radio') {
        this.checked = (this.value === data[name]);
      } else {
        this.value = data[name];
      }
    });
    $form.find('select').trigger('change');
    return true;
  }
})();
