/**
 * beneficiarios.js — Módulo de Beneficiarios
 * SAG Programas — sag_programas
 */
$(function () {

    let tabla;
    let archivoCSV = null;
    const modalVer = new bootstrap.Modal('#modalVerBene');

    // ── TABS ──────────────────────────────────────────
    window.switchTab = function (tab) {
        ['individual', 'masivo', 'listado'].forEach(t => {
            document.getElementById('tab-' + t).style.display = (t === tab) ? 'block' : 'none';
        });
        document.querySelectorAll('.mode-tab').forEach((btn, i) => {
            btn.classList.toggle('active',
                (tab === 'individual' && i === 0) ||
                (tab === 'masivo'     && i === 1) ||
                (tab === 'listado'    && i === 2)
            );
        });
        if (tab === 'listado' && !tabla) initTabla();
    };

    // ── DATATABLES ────────────────────────────────────
    function initTabla() {
        tabla = $('#tablaBeneficiarios').DataTable({
            processing: true,
            ajax: {
                url:    SAG.BASE_URL + '/beneficiarios/listar',
                type:   'POST',
                data:   function (d) {
                    d.id_organizacion = $('#filtroOrg').val();
                    d.id_departamento = $('#filtroDepBene').val();
                    d.sexo            = $('#filtroSexo').val();
                },
                dataSrc: 'data',
            },
            columns: [
                { data: 'id_beneficiario', width: '40px' },
                { data: 'nombre_completo' },
                { data: 'dni' },
                { data: 'edad',       className: 'text-center' },
                { data: 'sexo',       orderable: false },
                { data: 'organizacion' },
                { data: 'ubicacion' },
                { data: 'telefono' },
                { data: 'acciones',   orderable: false, className: 'text-center' },
            ],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-MX.json' },
            order:      [[1, 'asc']],
            pageLength: 15,
        });
    }

    $('#btnFiltrarBene').on('click', function () {
        if (tabla) tabla.ajax.reload();
        else { initTabla(); switchTab('listado'); }
    });

    // ── GUARDAR BENEFICIARIO ──────────────────────────
    $('#btnGuardarBene').on('click', function () {
        const nombre   = $('#bNombre').val().trim();
        const apellido = $('#bApellido').val().trim();
        const sexo     = $('#bSexo').val();
        const dep      = $('#bDep').val();
        const mun      = $('#bMun').val();

        if (!nombre)   { SAG.toast('El nombre es obligatorio.',   'warning'); return; }
        if (!apellido) { SAG.toast('El apellido es obligatorio.', 'warning'); return; }
        if (!sexo)     { SAG.toast('Seleccione el sexo.',         'warning'); return; }
        if (!dep)      { SAG.toast('Seleccione un departamento.', 'warning'); return; }
        if (!mun)      { SAG.toast('Seleccione un municipio.',    'warning'); return; }

        SAG.btnLoading('#btnGuardarBene', true);
        SAG.ajax({
            url:  '/beneficiarios/save',
            data: $('#formBeneficiario').serialize(),
            success: function (res) {
                SAG.btnLoading('#btnGuardarBene', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message);
                limpiarForm();
            },
            error: function () { SAG.btnLoading('#btnGuardarBene', false); },
        });
    });

    // ── LIMPIAR FORMULARIO ────────────────────────────
    $('#btnLimpiarBene').on('click', limpiarForm);

    function limpiarForm() {
        document.getElementById('formBeneficiario').reset();
        $('#beneId').val(0);
        $('#bMun').html('<option value="">— Seleccione departamento primero —</option>');
    }

    // ── CHANGE DEPTO ──────────────────────────────────
    $('#bDep').on('change', function () {
        SAG.loadMunicipios($(this).val(), '#bMun');
    });

    // ── FORMATEO DNI (0801-AAAA-NNNNN) ───────────────
    $('#bDni').on('input', function () {
        const pos = this.selectionStart;
        const prev = this.value;
        const fmt  = SAG.formatDNI(this.value);
        this.value = fmt;
        // Ajustar cursor si se insertó un guión
        if (fmt.length > prev.length) this.setSelectionRange(pos + 1, pos + 1);
        else this.setSelectionRange(pos, pos);
    });

    // ── FORMATEO TELÉFONO ─────────────────────────────
    $('#bTelefono').on('input', function () {
        const pos = this.selectionStart;
        const prev = this.value;
        const fmt  = SAG.formatTel(this.value);
        this.value = fmt;
        if (fmt.length > prev.length) this.setSelectionRange(pos + 1, pos + 1);
        else this.setSelectionRange(pos, pos);
    });

    // ── VER DETALLE ───────────────────────────────────
    $(document).on('click', '.btn-ver', function () {
        const id = $(this).data('id');
        SAG.ajax({
            url:  '/beneficiarios/get',
            data: { id },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const b = res.data;
                const sexoIcon = b.sexo === 'M'
                    ? '<span style="color:#2563eb;"><i class="fas fa-mars"></i> Masculino</span>'
                    : '<span style="color:#db2777;"><i class="fas fa-venus"></i> Femenino</span>';

                const edad = b.fecha_nacimiento
                    ? calcularEdad(b.fecha_nacimiento) + ' años'
                    : '—';

                $('#detalleBeneBody').html(`
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label-b">Nombre Completo</label>
                        <p class="fw-bold">${escHtml(b.nombre)} ${escHtml(b.apellido || '')}</p>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label-b">DNI</label>
                        <p>${b.dni || '—'}</p>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label-b">Fecha de Nacimiento</label>
                        <p>${b.fecha_nacimiento || '—'}</p>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label-b">Edad</label>
                        <p>${edad}</p>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label-b">Sexo</label>
                        <p>${sexoIcon}</p>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label-b">Teléfono</label>
                        <p>${b.telefono || '—'}</p>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label-b">Organización</label>
                        <p>${escHtml(b.organizacion || '—')}</p>
                      </div>
                      <div class="col-12">
                        <label class="form-label-b">Ubicación</label>
                        <p>${escHtml(b.departamento || '')} / ${escHtml(b.municipio || '')}${b.aldea ? ' / ' + escHtml(b.aldea) : ''}</p>
                      </div>
                    </div>`);

                $('.btn-editar-desde-modal').data('id', id);
                modalVer.show();
            },
        });
    });

    // Editar desde modal de detalle
    $(document).on('click', '.btn-editar-desde-modal', function () {
        modalVer.hide();
        cargarParaEditar($(this).data('id'));
    });

    // ── EDITAR DESDE TABLA ────────────────────────────
    $(document).on('click', '.btn-editar', function () {
        cargarParaEditar($(this).data('id'));
    });

    function cargarParaEditar(id) {
        SAG.ajax({
            url:  '/beneficiarios/get',
            data: { id },
            success: function (res) {
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                const b = res.data;
                limpiarForm();
                switchTab('individual');
                setTimeout(function () {
                    $('#beneId').val(b.id_beneficiario);
                    $('#bNombre').val(b.nombre);
                    $('#bApellido').val(b.apellido);
                    $('#bDni').val(b.dni);
                    $('#bFechaNac').val(b.fecha_nacimiento);
                    $('#bSexo').val(b.sexo);
                    $('#bTelefono').val(b.telefono);
                    $('#bAldea').val(b.aldea);
                    $('#bOrg').val(b.id_organizacion);
                    $('#bDep').val(b.id_departamento);
                    SAG.loadMunicipios(b.id_departamento, '#bMun', b.id_municipio);
                    window.scrollTo(0, 0);
                    SAG.toast('Beneficiario cargado para edición.', 'warning');
                }, 100);
            },
        });
    }

    // ── ELIMINAR ──────────────────────────────────────
    $(document).on('click', '.btn-eliminar', function () {
        const id = $(this).data('id');
        SAG.confirm('¿Desea eliminar este beneficiario del sistema?', function () {
            SAG.ajax({
                url:  '/beneficiarios/delete',
                data: { id },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    tabla.ajax.reload();
                },
            });
        });
    });

    // ── CARGA MASIVA ──────────────────────────────────
    const uploadZone = document.getElementById('uploadZone');

    uploadZone.addEventListener('click', function () {
        document.getElementById('inputCSV').click();
    });
    uploadZone.addEventListener('dragover',  function (e) { e.preventDefault(); uploadZone.classList.add('dragover'); });
    uploadZone.addEventListener('dragleave', function ()  { uploadZone.classList.remove('dragover'); });
    uploadZone.addEventListener('drop', function (e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        if (e.dataTransfer.files[0]) setArchivoCSV(e.dataTransfer.files[0]);
    });
    document.getElementById('inputCSV').addEventListener('change', function () {
        if (this.files[0]) setArchivoCSV(this.files[0]);
    });

    function setArchivoCSV(file) {
        if (!file.name.endsWith('.csv')) {
            SAG.toast('Solo se permiten archivos CSV.', 'warning'); return;
        }
        archivoCSV = file;
        $('#nombreArchivoCSV').text(file.name);
        $('#countFilasCSV').text('~' + Math.round(file.size / 50) + ' registros aprox.');
        $('#previewCSV').show();
        $('#resultadoCarga').hide();
    }

    $('#btnCancelarCSV').on('click', function () {
        archivoCSV = null;
        document.getElementById('inputCSV').value = '';
        $('#previewCSV').hide();
    });

    $('#btnProcesarCSV').on('click', function () {
        if (!archivoCSV) { SAG.toast('Seleccione un archivo CSV.', 'warning'); return; }
        const fd = new FormData();
        fd.append('archivo', archivoCSV);

        SAG.btnLoading('#btnProcesarCSV', true);
        $.ajax({
            url:         SAG.BASE_URL + '/beneficiarios/masivo',
            method:      'POST',
            data:        fd,
            processData: false,
            contentType: false,
            headers:     { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                SAG.btnLoading('#btnProcesarCSV', false);
                let html = res.success
                    ? `<div class="alert-sag" style="background:#dcfce7;border-color:#86efac;color:#166534;">
                           <i class="fas fa-circle-check"></i>
                           <strong>${res.message}</strong>
                       </div>`
                    : `<div class="alert-sag" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;">
                           <i class="fas fa-circle-xmark"></i> ${res.message}
                       </div>`;
                if (res.data && res.data.errores && res.data.errores.length > 0) {
                    html += `<div style="margin-top:10px;font-size:.8rem;">
                        <strong>Advertencias (${res.data.errores.length}):</strong>
                        <ul style="margin-top:6px;">
                            ${res.data.errores.map(e => `<li>${e}</li>`).join('')}
                        </ul></div>`;
                }
                $('#resultadoCarga').html(html).show();
                $('#btnCancelarCSV').click();
            },
            error: function () {
                SAG.btnLoading('#btnProcesarCSV', false);
                SAG.toast('Error al procesar el archivo.', 'error');
            },
        });
    });

    // ── HELPERS ───────────────────────────────────────
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function calcularEdad(fechaNac) {
        const hoy   = new Date();
        const nac   = new Date(fechaNac);
        let edad    = hoy.getFullYear() - nac.getFullYear();
        const m     = hoy.getMonth() - nac.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--;
        return edad;
    }

});
