/**
 * beneficiarios.js — Módulo de Beneficiarios (Productores)
 * SAG Programas — tabla principal + modales crear/editar y carga masiva
 */
$(function () {

    let tabla;
    let archivoCSV   = null;
    const modalBene  = new bootstrap.Modal('#modalBeneficiario');
    const modalCarga = new bootstrap.Modal('#modalCargaMasiva');
    const modalVer   = new bootstrap.Modal('#modalVerBene');

    const HOY = new Date().toISOString().split('T')[0];
    $('#bFechaNac').attr('max', HOY); // la fecha de nacimiento no puede ser futura

    // ── DATATABLE (vista principal) ───────────────────
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

    initTabla();

    // ── FILTRAR (botón + recarga automática al cambiar) ──
    $('#btnFiltrarBene').on('click', function () {
        tabla.ajax.reload();
    });
    $('#filtroOrg, #filtroDepBene, #filtroSexo').on('change', function () {
        tabla.ajax.reload();
    });

    // ── ABRIR MODAL NUEVO ─────────────────────────────
    $('#btnNuevoBene').on('click', function () {
        limpiarForm();
        $('#modalBeneTitulo').html('<i class="fas fa-user-plus me-2"></i>Nuevo Productor');
        modalBene.show();
    });

    // ── GUARDAR (crear / editar) ──────────────────────
    $('#btnGuardarBene').on('click', function () {
        const nombre   = $('#bNombre').val().trim();
        const apellido = $('#bApellido').val().trim();
        const sexo     = $('#bSexo').val();
        const dep      = $('#bDep').val();
        const mun      = $('#bMun').val();
        const dni      = ($('#bDni').val() || '').replace(/\D/g, '');
        const tel      = ($('#bTelefono').val() || '').replace(/\D/g, '');
        const fechaNac = $('#bFechaNac').val();

        if (!nombre)   { SAG.toast('El nombre es obligatorio.',   'warning'); return; }
        if (!apellido) { SAG.toast('El apellido es obligatorio.', 'warning'); return; }
        if (!sexo)     { SAG.toast('Seleccione el sexo.',         'warning'); return; }
        if (!dep)      { SAG.toast('Seleccione un departamento.', 'warning'); return; }
        if (!mun)      { SAG.toast('Seleccione un municipio.',    'warning'); return; }
        if (dni && dni.length !== 13) {
            SAG.toast('El DNI debe tener 13 dígitos.', 'warning'); return;
        }
        if (tel && tel.length !== 8) {
            SAG.toast('El teléfono debe tener 8 dígitos (formato Honduras).', 'warning'); return;
        }
        if (fechaNac && fechaNac > HOY) {
            SAG.toast('La fecha de nacimiento no puede ser futura.', 'warning'); return;
        }

        SAG.btnLoading('#btnGuardarBene', true);
        SAG.ajax({
            url:  '/beneficiarios/save',
            data: $('#formBeneficiario').serialize(),
            success: function (res) {
                SAG.btnLoading('#btnGuardarBene', false);
                if (!res.success) { SAG.toast(res.message, 'error'); return; }
                SAG.toast(res.message);
                modalBene.hide();
                tabla.ajax.reload(null, false); // mantener la página actual
            },
            error: function () { SAG.btnLoading('#btnGuardarBene', false); },
        });
    });

    function limpiarForm() {
        document.getElementById('formBeneficiario').reset();
        $('#beneId').val(0);
        $('#bMun').html('<option value="">— Seleccione departamento primero —</option>');
    }

    // ── CHANGE DEPTO (modal) ──────────────────────────
    $('#bDep').on('change', function () {
        SAG.loadMunicipios($(this).val(), '#bMun');
    });

    // ── MÁSCARAS DE ENTRADA ───────────────────────────
    $('#bDni').on('input', function () {
        const pos  = this.selectionStart;
        const prev = this.value;
        const fmt  = SAG.formatDNI(this.value);
        this.value = fmt;
        if (fmt.length > prev.length) this.setSelectionRange(pos + 1, pos + 1);
        else this.setSelectionRange(pos, pos);
    });
    $('#bTelefono').on('input', function () {
        const pos  = this.selectionStart;
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
                        <p>${escHtml(SAG.formatDNI(b.dni || '')) || '—'}</p>
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
                        <p>${escHtml(b.telefono || '—')}</p>
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
                $('#modalBeneTitulo').html('<i class="fas fa-pen me-2"></i>Editar Productor');
                $('#beneId').val(b.id_beneficiario);
                $('#bNombre').val(b.nombre);
                $('#bApellido').val(b.apellido);
                $('#bDni').val(SAG.formatDNI(b.dni || ''));
                $('#bFechaNac').val(b.fecha_nacimiento);
                $('#bSexo').val(b.sexo);
                $('#bTelefono').val(SAG.formatTel(b.telefono || ''));
                $('#bAldea').val(b.aldea);
                $('#bOrg').val(b.id_organizacion);
                $('#bDep').val(b.id_departamento);
                SAG.loadMunicipios(b.id_departamento, '#bMun', b.id_municipio);
                modalBene.show();
            },
        });
    }

    // ── ELIMINAR ──────────────────────────────────────
    $(document).on('click', '.btn-eliminar', function () {
        const id     = $(this).data('id');
        const nombre = $(this).data('nombre') || 'este beneficiario';
        SAG.confirm('¿Desea eliminar a "' + nombre + '" del sistema?', function () {
            SAG.ajax({
                url:  '/beneficiarios/delete',
                data: { id },
                success: function (res) {
                    if (!res.success) { SAG.toast(res.message, 'error'); return; }
                    SAG.toast(res.message);
                    tabla.ajax.reload(null, false);
                },
            });
        });
    });

    // ── CARGA MASIVA (modal) ──────────────────────────
    $('#btnCargaMasiva').on('click', function () {
        resetCargaMasiva();
        modalCarga.show();
    });

    function resetCargaMasiva() {
        archivoCSV = null;
        document.getElementById('inputCSV').value = '';
        $('#previewCSV').hide();
        $('#resultadoCarga').hide().empty();
    }

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
        if (window.SAG && SAG.CSRF) fd.append('_csrf', SAG.CSRF);

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
                            ${res.data.errores.map(e => `<li>${escHtml(e)}</li>`).join('')}
                        </ul></div>`;
                }
                $('#resultadoCarga').html(html).show();
                $('#btnCancelarCSV').click();
                if (res.success) tabla.ajax.reload(null, false);
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
