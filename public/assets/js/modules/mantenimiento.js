/**
 * mantenimiento.js — Módulo de Mantenimiento
 * SAG Programas — sag_programas
 */
$(function () {
    'use strict';

    // ── DATOS INICIALES ───────────────────────────────
    const D = SAG_MANT;
    let pendingAction = null;

    // ── TABS ──────────────────────────────────────────
    document.querySelectorAll('.mode-tab[data-tab]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.mode-tab[data-tab]').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const panel = document.getElementById('tab' + this.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    // ── MODAL HELPERS ─────────────────────────────────
    window.cerrarModal = function (id) {
        document.getElementById(id).classList.remove('show');
    };
    window.cerrarConfirm = function () {
        document.getElementById('confirmOverlay').classList.remove('show');
        pendingAction = null;
    };

    function abrirModal(id) {
        document.getElementById(id).classList.add('show');
    }

    // Cierra modal al hacer clic en el overlay
    document.querySelectorAll('.modal-overlay').forEach(m => {
        m.addEventListener('click', function (e) {
            if (e.target === m) m.classList.remove('show');
        });
    });
    document.getElementById('confirmOverlay').addEventListener('click', function (e) {
        if (e.target === this) cerrarConfirm();
    });

    // ── HELPERS ───────────────────────────────────────
    function escHtml(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function badgeActivo(activo) {
        return activo
            ? '<span class="badge-pill bp-green">Activo</span>'
            : '<span class="badge-pill bp-red">Inactivo</span>';
    }

    function confirmar(titulo, msg, callback) {
        document.getElementById('confirmTitulo').textContent = titulo;
        document.getElementById('confirmMsg').textContent = msg;
        pendingAction = callback;
        document.getElementById('confirmOverlay').classList.add('show');
    }

    document.getElementById('btnConfirmOk').addEventListener('click', function () {
        if (typeof pendingAction === 'function') pendingAction();
        cerrarConfirm();
    });

    function afterSave(res, modalId, renderFn) {
        if (!res.success) { SAG.toast(res.message || 'Error al guardar.', 'error'); return; }
        SAG.toast(res.message || 'Guardado correctamente.', 'success');
        cerrarModal(modalId);
        setTimeout(() => location.reload(), 800);
    }

    // ─────────────────────────────────────────────────
    // TÉCNICOS
    // ─────────────────────────────────────────────────
    function renderTecnicos(query) {
        query = (query || '').toLowerCase();
        const data = query
            ? D.tecnicos.filter(t => (t.nombre + t.email + t.especialidad).toLowerCase().includes(query))
            : D.tecnicos;

        document.getElementById('bodyTecnicos').innerHTML = data.length
            ? data.map((t, i) => `
                <tr>
                  <td>${i + 1}</td>
                  <td><strong>${escHtml(t.nombre)}</strong></td>
                  <td style="font-size:.78rem;color:#666;">${escHtml(t.email)}</td>
                  <td>${escHtml(t.especialidad)}</td>
                  <td>${escHtml(t.telefono)}</td>
                  <td>${badgeActivo(t.activo)}</td>
                  <td>
                    <button class="btn-edit" onclick="editarTecnico(${t.id})"><i class="fas fa-pencil"></i></button>
                    <button class="btn-danger" onclick="eliminarTecnico(${t.id},'${escHtml(t.nombre)}')"><i class="fas fa-ban"></i></button>
                  </td>
                </tr>`).join('')
            : '<tr><td colspan="7" style="text-align:center;padding:20px;color:#aaa;">Sin registros.</td></tr>';
    }

    $('#searchTec').on('input', function () { renderTecnicos(this.value); });

    $('#btnNuevoTecnico').on('click', function () {
        $('#tecId').val(0);
        $('#tecNombre, #tecEspec, #tecTel, #tecEmail').val('');
        $('#tecDpto, #tecActivo').val($('#tecActivo option:first').val());
        $('#tecActivo').val('1');
        document.getElementById('tituloModalTec').innerHTML =
            '<i class="fas fa-user-tie me-2" style="color:var(--primario);"></i>Nuevo técnico';
        abrirModal('modalTecnico');
    });

    window.editarTecnico = function (id) {
        const t = D.tecnicos.find(x => x.id === id);
        if (!t) return;
        $('#tecId').val(t.id);
        $('#tecNombre').val(t.nombre);
        $('#tecEspec').val(t.especialidad);
        $('#tecDpto').val(t.id_departamento);
        $('#tecTel').val(t.telefono);
        $('#tecEmail').val(t.email);
        $('#tecActivo').val(t.activo);
        document.getElementById('tituloModalTec').innerHTML =
            '<i class="fas fa-pencil me-2" style="color:var(--primario);"></i>Editar técnico';
        abrirModal('modalTecnico');
    };

    window.eliminarTecnico = function (id, nombre) {
        confirmar('¿Desactivar técnico?', `"${nombre}" quedará inactivo.`, function () {
            SAG.ajax({ url: '/mantenimiento/tecnicos/delete', data: { id }, success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            }});
        });
    };

    $('#btnGuardarTecnico').on('click', function () {
        const nombre = $('#tecNombre').val().trim();
        if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        const data = {
            id_tecnico:      $('#tecId').val(),
            nombre_completo: nombre,
            especialidad:    $('#tecEspec').val().trim(),
            id_departamento: $('#tecDpto').val(),
            telefono:        $('#tecTel').val().trim(),
            email:           $('#tecEmail').val().trim(),
            activo:          $('#tecActivo').val(),
        };
        SAG.ajax({ url: '/mantenimiento/tecnicos/save', data, success: r => afterSave(r, 'modalTecnico') });
    });

    // ─────────────────────────────────────────────────
    // TEMAS
    // ─────────────────────────────────────────────────
    function renderTemas() {
        document.getElementById('bodyTemas').innerHTML = D.temas.length
            ? D.temas.map((t, i) => `
                <tr>
                  <td>${i + 1}</td>
                  <td><strong>${escHtml(t.nombre)}</strong></td>
                  <td>${badgeActivo(t.activo)}</td>
                  <td>
                    <button class="btn-edit" onclick="editarTema(${t.id})"><i class="fas fa-pencil"></i></button>
                    <button class="btn-danger" onclick="eliminarTema(${t.id},'${escHtml(t.nombre)}')"><i class="fas fa-ban"></i></button>
                  </td>
                </tr>`).join('')
            : '<tr><td colspan="4" style="text-align:center;padding:16px;color:#aaa;">Sin temas.</td></tr>';
    }

    function renderSubtemas() {
        document.getElementById('bodySubtemas').innerHTML = D.subtemas.length
            ? D.subtemas.map((s, i) => `
                <tr>
                  <td>${i + 1}</td>
                  <td>${escHtml(s.nombre)}</td>
                  <td style="font-size:.78rem;color:#666;">${escHtml(s.tema)}</td>
                  <td>${badgeActivo(s.activo)}</td>
                  <td>
                    <button class="btn-edit" onclick="editarSubtema(${s.id})"><i class="fas fa-pencil"></i></button>
                    <button class="btn-danger" onclick="eliminarSubtema(${s.id},'${escHtml(s.nombre)}')"><i class="fas fa-ban"></i></button>
                  </td>
                </tr>`).join('')
            : '<tr><td colspan="5" style="text-align:center;padding:16px;color:#aaa;">Sin subtemas.</td></tr>';
    }

    $('#btnNuevoTema').on('click', function () {
        $('#temaId').val(0); $('#temaNombre').val(''); $('#temaActivo').val('1');
        document.getElementById('tituloModalTema').innerHTML = '<i class="fas fa-tag me-2" style="color:var(--primario);"></i>Nuevo tema';
        abrirModal('modalTema');
    });

    window.editarTema = function (id) {
        const t = D.temas.find(x => x.id === id);
        if (!t) return;
        $('#temaId').val(t.id); $('#temaNombre').val(t.nombre); $('#temaActivo').val(t.activo);
        document.getElementById('tituloModalTema').innerHTML = '<i class="fas fa-pencil me-2" style="color:var(--primario);"></i>Editar tema';
        abrirModal('modalTema');
    };

    window.eliminarTema = function (id, nombre) {
        confirmar('¿Desactivar tema?', `"${nombre}" quedará inactivo.`, function () {
            SAG.ajax({ url: '/mantenimiento/temas/delete', data: { id }, success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            }});
        });
    };

    $('#btnGuardarTema').on('click', function () {
        const nombre = $('#temaNombre').val().trim();
        if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        SAG.ajax({ url: '/mantenimiento/temas/save', data: {
            id_tema: $('#temaId').val(), nombre, activo: $('#temaActivo').val()
        }, success: r => afterSave(r, 'modalTema') });
    });

    $('#btnNuevoSubtema').on('click', function () {
        $('#subId').val(0); $('#subNombre').val(''); $('#subTema').val(''); $('#subActivo').val('1');
        document.getElementById('tituloModalSub').innerHTML = '<i class="fas fa-tag me-2" style="color:var(--primario);"></i>Nuevo subtema';
        abrirModal('modalSubtema');
    });

    window.editarSubtema = function (id) {
        const s = D.subtemas.find(x => x.id === id);
        if (!s) return;
        $('#subId').val(s.id); $('#subNombre').val(s.nombre); $('#subTema').val(s.id_tema); $('#subActivo').val(s.activo);
        document.getElementById('tituloModalSub').innerHTML = '<i class="fas fa-pencil me-2" style="color:var(--primario);"></i>Editar subtema';
        abrirModal('modalSubtema');
    };

    window.eliminarSubtema = function (id, nombre) {
        confirmar('¿Desactivar subtema?', `"${nombre}" quedará inactivo.`, function () {
            SAG.ajax({ url: '/mantenimiento/subtemas/delete', data: { id }, success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            }});
        });
    };

    $('#btnGuardarSubtema').on('click', function () {
        const nombre = $('#subNombre').val().trim();
        const idTema = $('#subTema').val();
        if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        if (!idTema) { SAG.toast('Seleccione el tema padre.', 'warning'); return; }
        SAG.ajax({ url: '/mantenimiento/subtemas/save', data: {
            id_subtema: $('#subId').val(), nombre, id_tema: idTema, activo: $('#subActivo').val()
        }, success: r => afterSave(r, 'modalSubtema') });
    });

    // ─────────────────────────────────────────────────
    // CULTIVOS
    // ─────────────────────────────────────────────────
    function renderCultivos() {
        document.getElementById('bodyCultivos').innerHTML = D.cultivos.length
            ? D.cultivos.map((c, i) => `
                <tr>
                  <td>${i + 1}</td>
                  <td><strong>${escHtml(c.nombre)}</strong></td>
                  <td>${badgeActivo(c.activo)}</td>
                  <td>
                    <button class="btn-edit" onclick="editarCultivo(${c.id})"><i class="fas fa-pencil"></i></button>
                    <button class="btn-danger" onclick="eliminarCultivo(${c.id},'${escHtml(c.nombre)}')"><i class="fas fa-ban"></i></button>
                  </td>
                </tr>`).join('')
            : '<tr><td colspan="4" style="text-align:center;padding:16px;color:#aaa;">Sin cultivos.</td></tr>';
    }

    $('#btnNuevoCultivo').on('click', function () {
        $('#culId').val(0); $('#culNombre').val(''); $('#culActivo').val('1');
        document.getElementById('tituloModalCul').innerHTML = '<i class="fas fa-seedling me-2" style="color:var(--primario);"></i>Nuevo cultivo';
        abrirModal('modalCultivo');
    });

    window.editarCultivo = function (id) {
        const c = D.cultivos.find(x => x.id === id);
        if (!c) return;
        $('#culId').val(c.id); $('#culNombre').val(c.nombre); $('#culActivo').val(c.activo);
        document.getElementById('tituloModalCul').innerHTML = '<i class="fas fa-pencil me-2" style="color:var(--primario);"></i>Editar cultivo';
        abrirModal('modalCultivo');
    };

    window.eliminarCultivo = function (id, nombre) {
        confirmar('¿Desactivar cultivo?', `"${nombre}" quedará inactivo.`, function () {
            SAG.ajax({ url: '/mantenimiento/cultivos/delete', data: { id }, success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            }});
        });
    };

    $('#btnGuardarCultivo').on('click', function () {
        const nombre = $('#culNombre').val().trim();
        if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        SAG.ajax({ url: '/mantenimiento/cultivos/save', data: {
            id_cultivo: $('#culId').val(), nombre, activo: $('#culActivo').val()
        }, success: r => afterSave(r, 'modalCultivo') });
    });

    // ─────────────────────────────────────────────────
    // TIPOS AT
    // ─────────────────────────────────────────────────
    function renderTiposAt() {
        document.getElementById('bodyTiposAt').innerHTML = D.tiposAt.length
            ? D.tiposAt.map((t, i) => `
                <tr>
                  <td>${i + 1}</td>
                  <td><strong>${escHtml(t.nombre)}</strong></td>
                  <td style="font-size:.78rem;color:#555;">${escHtml(t.descripcion)}</td>
                  <td><i class="fas ${escHtml(t.icono)}" style="color:var(--primario);"></i> <code style="font-size:.72rem;">${escHtml(t.icono)}</code></td>
                  <td>${badgeActivo(t.activo)}</td>
                  <td>
                    <button class="btn-edit" onclick="editarTipoAt(${t.id})"><i class="fas fa-pencil"></i></button>
                    <button class="btn-danger" onclick="eliminarTipoAt(${t.id},'${escHtml(t.nombre)}')"><i class="fas fa-ban"></i></button>
                  </td>
                </tr>`).join('')
            : '<tr><td colspan="6" style="text-align:center;padding:16px;color:#aaa;">Sin tipos de AT.</td></tr>';
    }

    $('#btnNuevoTipoAt').on('click', function () {
        $('#atId').val(0); $('#atNombre, #atDesc, #atIcono').val(''); $('#atActivo').val('1');
        document.getElementById('tituloModalTipoAt').innerHTML = '<i class="fas fa-list-check me-2" style="color:var(--primario);"></i>Nuevo tipo de AT';
        abrirModal('modalTipoAt');
    });

    window.editarTipoAt = function (id) {
        const t = D.tiposAt.find(x => x.id === id);
        if (!t) return;
        $('#atId').val(t.id); $('#atNombre').val(t.nombre); $('#atDesc').val(t.descripcion);
        $('#atIcono').val(t.icono); $('#atActivo').val(t.activo);
        document.getElementById('tituloModalTipoAt').innerHTML = '<i class="fas fa-pencil me-2" style="color:var(--primario);"></i>Editar tipo de AT';
        abrirModal('modalTipoAt');
    };

    window.eliminarTipoAt = function (id, nombre) {
        confirmar('¿Desactivar tipo AT?', `"${nombre}" quedará inactivo.`, function () {
            SAG.ajax({ url: '/mantenimiento/tipoat/delete', data: { id }, success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            }});
        });
    };

    $('#btnGuardarTipoAt').on('click', function () {
        const nombre = $('#atNombre').val().trim();
        if (!nombre) { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        SAG.ajax({ url: '/mantenimiento/tipoat/save', data: {
            id_tipo_at:  $('#atId').val(),
            nombre,
            descripcion: $('#atDesc').val().trim(),
            icono:       $('#atIcono').val().trim() || 'fa-circle-check',
            activo:      $('#atActivo').val(),
        }, success: r => afterSave(r, 'modalTipoAt') });
    });

    // ─────────────────────────────────────────────────
    // USUARIOS
    // ─────────────────────────────────────────────────
    const AVATAR_COLORS = ['#445577','#16a34a','#d97706','#9333ea','#0ea5e9','#ef4444','#14b8a6'];
    const ROL_CLASS = {
        'Administrador': 'bp-red',
        'Coordinador': 'bp-purple',
        'Supervisor': 'bp-orange',
        'Técnico': 'bp-blue',
        'Digitador': 'bp-yellow',
        'Consulta': 'bp-gray',
    };

    function rolClass(rol) {
        for (const key in ROL_CLASS) {
            if (rol && rol.includes(key)) return ROL_CLASS[key];
        }
        return 'bp-gray';
    }

    function renderUsuarios() {
        const grid = document.getElementById('gridUsuarios');
        if (!D.usuarios.length) {
            grid.innerHTML = '<div class="col-12" style="text-align:center;color:#aaa;padding:20px;">Sin usuarios registrados.</div>';
            return;
        }
        grid.innerHTML = D.usuarios.map((u, i) => {
            const iniciales = u.nombre.split(' ').map(w => w[0]).filter(Boolean).slice(0, 2).join('');
            return `
            <div class="col-md-6 col-xl-4">
              <div class="user-card">
                <div class="user-avatar-lg" style="background:${AVATAR_COLORS[i % 7]};color:#fff;">${escHtml(iniciales)}</div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:.86rem;font-weight:700;color:#1a1a1a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(u.nombre)}</div>
                  <div style="font-size:.74rem;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(u.email)}</div>
                  <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                    <span class="badge-pill ${rolClass(u.rol)}" style="font-size:.65rem;">${escHtml(u.rol)}</span>
                    ${badgeActivo(u.activo).replace('badge-pill', 'badge-pill').replace('font-size:.7rem', 'font-size:.65rem')}
                  </div>
                </div>
                <div class="d-flex flex-column gap-1">
                  <button class="btn-edit" onclick="editarUsuario(${u.id})"><i class="fas fa-pencil"></i></button>
                  <button class="btn-danger" onclick="eliminarUsuario(${u.id},'${escHtml(u.nombre)}')"><i class="fas fa-ban"></i></button>
                </div>
              </div>
            </div>`;
        }).join('');
    }

    $('#btnNuevoUsuario').on('click', function () {
        $('#usrId').val(0);
        $('#usrNombre, #usrApellido, #usrEmail, #usrUsername, #usrPassword').val('');
        $('#usrRol').val(''); $('#usrActivo').val('1');
        $('#campoPassword').show(); $('#pwReq').show();
        document.getElementById('tituloModalUsr').innerHTML = '<i class="fas fa-user-plus me-2" style="color:var(--primario);"></i>Nuevo usuario';
        abrirModal('modalUsuario');
    });

    window.editarUsuario = function (id) {
        const u = D.usuarios.find(x => x.id === id);
        if (!u) return;
        $('#usrId').val(u.id);
        // Usa los campos nombre_solo / apellido_solo si existen; si no, hace fallback al split
        if (typeof u.nombre_solo !== 'undefined') {
            $('#usrNombre').val(u.nombre_solo || '');
            $('#usrApellido').val(u.apellido_solo || '');
        } else {
            const partes = (u.nombre || '').split(' ');
            $('#usrNombre').val(partes[0] || '');
            $('#usrApellido').val(partes.slice(1).join(' ') || '');
        }
        $('#usrEmail').val(u.email || '');
        $('#usrUsername').val(u.username || '');
        $('#usrRol').val(u.id_rol);
        $('#usrActivo').val(u.activo);
        $('#usrPassword').val('');
        $('#campoPassword').show(); $('#pwReq').hide();
        document.getElementById('tituloModalUsr').innerHTML = '<i class="fas fa-pencil me-2" style="color:var(--primario);"></i>Editar usuario';
        abrirModal('modalUsuario');
    };

    window.eliminarUsuario = function (id, nombre) {
        confirmar('¿Desactivar usuario?', `"${nombre}" perderá acceso al sistema.`, function () {
            SAG.ajax({ url: '/mantenimiento/usuarios/delete', data: { id }, success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            }});
        });
    };

    $('#btnGuardarUsuario').on('click', function () {
        const nombre   = $('#usrNombre').val().trim();
        const apellido = $('#usrApellido').val().trim();
        const email    = $('#usrEmail').val().trim();
        const username = $('#usrUsername').val().trim();
        const idRol    = $('#usrRol').val();
        const password = $('#usrPassword').val();
        const idUsr    = parseInt($('#usrId').val()) || 0;

        if (!nombre)   { SAG.toast('El nombre es obligatorio.', 'warning'); return; }
        if (!apellido) { SAG.toast('El apellido es obligatorio.', 'warning'); return; }
        if (!email)    { SAG.toast('El email es obligatorio.', 'warning'); return; }
        if (!username) { SAG.toast('El nombre de usuario (login) es obligatorio.', 'warning'); return; }
        if (!/^[a-zA-Z0-9._-]{3,60}$/.test(username)) {
            SAG.toast('El usuario solo puede tener letras, números, punto, guion y guion bajo (3-60).', 'warning'); return;
        }
        if (!idRol)    { SAG.toast('Seleccione un rol.', 'warning'); return; }
        if (!idUsr && !password) { SAG.toast('La contraseña es obligatoria para nuevos usuarios.', 'warning'); return; }
        if (password && password.length < 6) { SAG.toast('La contraseña debe tener al menos 6 caracteres.', 'warning'); return; }

        const data = {
            id_usuario: idUsr,
            nombre, apellido, email, username,
            id_rol: idRol,
            activo: $('#usrActivo').val()
        };
        if (password) data.password = password;

        SAG.ajax({ url: '/mantenimiento/usuarios/save', data, success: r => afterSave(r, 'modalUsuario') });
    });

    // ── INIT ──────────────────────────────────────────
    renderTecnicos();
    renderTemas();
    renderSubtemas();
    renderCultivos();
    renderTiposAt();
    renderUsuarios();
});
