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

    // Alcance de proyectos en el modal de usuario
    function toggleProyBox() {
        const todos = $('#usrTodosProy').is(':checked');
        $('.usrProyChk').prop('disabled', todos);
        $('#usrProyectosBox').css('opacity', todos ? '.45' : '1');
    }
    $('#usrTodosProy').on('change', toggleProyBox);

    function setProyectosUsuario(todos, proyectos) {
        $('#usrTodosProy').prop('checked', !!todos);
        const set = new Set((proyectos || []).map(Number));
        $('.usrProyChk').each(function () {
            this.checked = set.has(parseInt(this.value));
        });
        toggleProyBox();
    }

    $('#btnNuevoUsuario').on('click', function () {
        $('#usrId').val(0);
        $('#usrNombre, #usrApellido, #usrEmail, #usrUsername, #usrPassword').val('');
        $('#usrRol').val(''); $('#usrActivo').val('1');
        $('#campoPassword').show(); $('#pwReq').show();
        setProyectosUsuario(false, []);
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
        setProyectosUsuario(u.todos_proyectos, u.proyectos);
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
        if (password && password.length < 8) { SAG.toast('La contraseña debe tener al menos 8 caracteres.', 'warning'); return; }

        const todos = $('#usrTodosProy').is(':checked') ? 1 : 0;
        const proyectos = $('.usrProyChk:checked').map((_, el) => el.value).get();
        if (!todos && proyectos.length === 0) {
            SAG.toast('Asigne al menos un proyecto o marque «Acceso a todos los proyectos».', 'warning'); return;
        }

        const data = {
            id_usuario: idUsr,
            nombre, apellido, email, username,
            id_rol: idRol,
            activo: $('#usrActivo').val(),
            todos_proyectos: todos,
            proyectos: proyectos
        };
        if (password) data.password = password;

        SAG.ajax({ url: '/mantenimiento/usuarios/save', data, success: r => afterSave(r, 'modalUsuario') });
    });

    // ─────────────────────────────────────────────────
    // ROLES Y PRIVILEGIOS  (solo administradores)
    // ─────────────────────────────────────────────────
    let privRolId = 0;

    function renderRoles() {
        if (!D.esAdminRoles) return;
        const body = document.getElementById('bodyRoles');
        if (!body) return;
        body.innerHTML = (D.roles && D.roles.length)
            ? D.roles.map((r, i) => {
                const tipo = r.es_admin
                    ? '<span class="badge-pill bp-purple">Administrador</span>'
                    : '<span class="badge-pill bp-blue">Personalizado</span>';
                const btnPriv = r.es_admin
                    ? `<button class="btn-sec" style="padding:5px 10px;font-size:.74rem;" disabled title="Acceso total"><i class="fas fa-shield-halved"></i></button>`
                    : `<button class="btn-edit" onclick="privilegiosRol(${r.id})" title="Privilegios"><i class="fas fa-shield-halved"></i></button>`;
                const btnDel = r.es_admin
                    ? ''
                    : `<button class="btn-danger" onclick="eliminarRol(${r.id})" title="Desactivar"><i class="fas fa-ban"></i></button>`;
                return `
                <tr>
                  <td>${i + 1}</td>
                  <td><strong>${escHtml(r.nombre)}</strong></td>
                  <td><code style="font-size:.72rem;">${escHtml(r.slug)}</code></td>
                  <td style="font-size:.76rem;color:#666;max-width:260px;">${escHtml(r.descripcion)}</td>
                  <td>${tipo}</td>
                  <td>${badgeActivo(r.activo)}</td>
                  <td style="white-space:nowrap;">
                    ${btnPriv}
                    <button class="btn-edit" onclick="editarRol(${r.id})" title="Editar"><i class="fas fa-pencil"></i></button>
                    ${btnDel}
                  </td>
                </tr>`;
            }).join('')
            : '<tr><td colspan="7" style="text-align:center;padding:20px;color:#aaa;">Sin roles.</td></tr>';
    }

    $('#btnNuevoRol').on('click', function () {
        $('#rolId').val(0);
        $('#rolNombre, #rolSlug, #rolDesc').val('');
        $('#rolActivo').val('1');
        $('#rolSlugCampo').show();
        document.getElementById('tituloModalRol').innerHTML = '<i class="fas fa-user-shield me-2" style="color:var(--primario);"></i>Nuevo rol';
        abrirModal('modalRol');
    });

    window.editarRol = function (id) {
        const r = D.roles.find(x => x.id === id);
        if (!r) return;
        $('#rolId').val(r.id);
        $('#rolNombre').val(r.nombre);
        $('#rolSlug').val(r.slug);
        $('#rolDesc').val(r.descripcion);
        $('#rolActivo').val(r.activo);
        $('#rolSlugCampo').hide(); // el slug no se cambia al editar
        document.getElementById('tituloModalRol').innerHTML = '<i class="fas fa-pencil me-2" style="color:var(--primario);"></i>Editar rol';
        abrirModal('modalRol');
    };

    window.eliminarRol = function (id) {
        const r = D.roles.find(x => x.id === id);
        if (!r) return;
        confirmar('¿Desactivar rol?', `El rol "${r.nombre}" quedará inactivo.`, function () {
            SAG.ajax({ url: '/mantenimiento/roles/delete', data: { id_rol: id }, success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            }});
        });
    };

    $('#btnGuardarRol').on('click', function () {
        const nombre = $('#rolNombre').val().trim();
        const id     = parseInt($('#rolId').val()) || 0;
        if (!nombre) { SAG.toast('El nombre del rol es obligatorio.', 'warning'); return; }
        const data = {
            id_rol: id,
            nombre,
            descripcion: $('#rolDesc').val().trim(),
            activo: $('#rolActivo').val(),
        };
        if (!id) data.slug = $('#rolSlug').val().trim();
        SAG.ajax({ url: '/mantenimiento/roles/save', data, success: r => afterSave(r, 'modalRol') });
    });

    // ── Matriz de privilegios ─────────────────────────
    window.privilegiosRol = function (id) {
        const r = D.roles.find(x => x.id === id);
        if (!r) return;
        privRolId = id;
        document.getElementById('tituloModalPriv').innerHTML =
            `<i class="fas fa-shield-halved me-2" style="color:var(--primario);"></i>Privilegios — ${escHtml(r.nombre)}`;

        const esAdmin = !!r.es_admin;
        $('#privAdminAviso').toggle(esAdmin);
        $('#btnGuardarPriv').prop('disabled', esAdmin).css('opacity', esAdmin ? '.5' : '1');

        // Cabecera
        let head = '<th>Módulo</th>';
        D.acciones.forEach(a => { head += `<th style="text-align:center;">${escHtml(a.nombre)}</th>`; });
        document.getElementById('privHead').innerHTML = head;

        // Set de privilegios actuales del rol: "idModulo:idAccion"
        const actuales = new Set(
            (D.privilegios || []).filter(p => p[0] === id).map(p => p[1] + ':' + p[2])
        );

        // Cuerpo
        document.getElementById('privBody').innerHTML = D.modulos.map(m => {
            let row = `<td><strong>${escHtml(m.nombre)}</strong></td>`;
            D.acciones.forEach(a => {
                const key = m.id + ':' + a.id;
                const checked = (esAdmin || actuales.has(key)) ? 'checked' : '';
                const dis = esAdmin ? 'disabled' : '';
                row += `<td style="text-align:center;"><input type="checkbox" class="privChk" value="${key}" ${checked} ${dis}/></td>`;
            });
            return `<tr>${row}</tr>`;
        }).join('');

        abrirModal('modalPrivilegios');
    };

    $('#btnGuardarPriv').on('click', function () {
        if (!privRolId) return;
        const privilegios = $('.privChk:checked').map((_, el) => el.value).get();
        SAG.ajax({
            url: '/mantenimiento/privilegios/save',
            data: { id_rol: privRolId, privilegios },
            success: r => afterSave(r, 'modalPrivilegios')
        });
    });

    // ── INIT ──────────────────────────────────────────
    renderTecnicos();
    renderTemas();
    renderSubtemas();
    renderCultivos();
    renderTiposAt();
    renderUsuarios();
    renderRoles();
});
