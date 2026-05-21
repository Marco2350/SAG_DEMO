<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/capacitaciones.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-graduation-cap" style="color:var(--primario);margin-right:8px;"></i>Capacitaciones
      <small>Registro de capacitaciones técnicas — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <div class="mode-tabs">
      <button class="mode-tab active" onclick="switchTab('nueva')">
        <i class="fas fa-plus-circle" style="font-size:.75rem;margin-right:5px;"></i>Nueva
      </button>
      <button class="mode-tab" onclick="switchTab('participantes')">
        <i class="fas fa-users" style="font-size:.75rem;margin-right:5px;"></i>Participantes
      </button>
      <button class="mode-tab" onclick="switchTab('listado')">
        <i class="fas fa-list" style="font-size:.75rem;margin-right:5px;"></i>Listado
      </button>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val"><?= $resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-graduation-cap" style="color:var(--primario);margin-right:4px;"></i>Total</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;"><?= $resumen['finalizadas'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Finalizadas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat gold">
        <div class="ms-val"><?= $resumen['borrador'] ?></div>
        <div class="ms-lbl"><i class="fas fa-pencil" style="color:#f5a623;margin-right:4px;"></i>En borrador</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat blue">
        <div class="ms-val"><?= $resumen['participantes'] ?></div>
        <div class="ms-lbl"><i class="fas fa-users" style="color:#3b82f6;margin-right:4px;"></i>Participantes</div>
      </div>
    </div>
  </div>

  <!-- TAB NUEVA -->
  <div id="tab-nueva">
    <div class="card-box">
      <div class="card-box-header"><h6><i class="fas fa-plus-circle"></i> Datos de la Capacitación</h6></div>
      <div class="card-box-body">
        <form id="formCapacitacion" novalidate>
          <input type="hidden" id="capId" name="id_capacitacion" value="0"/>

          <div class="form-section-title"><i class="fas fa-location-dot me-1"></i>Lugar</div>
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label-b">Departamento <span class="req">*</span></label>
              <select class="fs" id="cDep" name="id_departamento" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($departamentos as $dep): ?>
                <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Municipio <span class="req">*</span></label>
              <select class="fs" id="cMun" name="id_municipio" required>
                <option value="">— Seleccione departamento —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Aldea</label>
              <input type="text" class="fc" id="cAldea" name="aldea" placeholder="Ej. El Porvenir"/>
            </div>
            <div class="col-md-8">
              <label class="form-label-b">Lugar Específico</label>
              <input type="text" class="fc" id="cLugar" name="lugar_especifico"
                     placeholder="Ej. Finca El Progreso, Casa Comunal..."/>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Fecha <span class="req">*</span></label>
              <input type="date" class="fc" id="cFecha" name="fecha_capacitacion" required/>
            </div>
          </div>

          <div class="form-section-title"><i class="fas fa-book-open me-1"></i>Contenido Técnico</div>
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label-b">Tema <span class="req">*</span></label>
              <select class="fs" id="cTema" name="id_tema" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($temas as $tema): ?>
                <option value="<?= $tema['id_tema'] ?>"><?= htmlspecialchars($tema['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Subtema</label>
              <select class="fs" id="cSubtema" name="id_subtema">
                <option value="">— Seleccione tema primero —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Duración (horas)</label>
              <input type="number" class="fc" id="cDuracion" name="duracion_horas"
                     placeholder="Ej. 2.5" step="0.5" min="0.5" max="24"/>
            </div>
            <div class="col-12">
              <label class="form-label-b">Descripción / Observaciones</label>
              <textarea class="fc" id="cDescripcion" name="descripcion" rows="3"
                        placeholder="Temas abordados, metodología, observaciones..."></textarea>
            </div>
          </div>

          <div class="form-section-title"><i class="fas fa-user-tie me-1"></i>Técnico Responsable</div>
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label-b">Técnico <span class="req">*</span></label>
              <select class="fs" id="cTecnico" name="id_tecnico" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($tecnicos as $tec): ?>
                <option value="<?= $tec['id_tecnico'] ?>"><?= htmlspecialchars($tec['nombre_completo']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div style="padding:14px 18px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #f0f0f0;">
        <button type="button" class="btn-gris" id="btnLimpiarCap"><i class="fas fa-rotate-left"></i> Limpiar</button>
        <button type="button" class="btn-primario" id="btnGuardarCap"><i class="fas fa-floppy-disk"></i> Guardar y Continuar</button>
      </div>
    </div>
  </div>

  <!-- TAB PARTICIPANTES -->
  <div id="tab-participantes" style="display:none;">
    <div id="capActivaBanner" style="display:none;background:linear-gradient(135deg,#e8edf5,#d6dff0);border:1px solid #b0bce0;border-radius:10px;padding:12px 18px;margin-bottom:16px;align-items:center;gap:12px;">
      <i class="fas fa-graduation-cap" style="color:var(--primario);font-size:1.3rem;"></i>
      <div style="flex:1;">
        <div style="font-weight:700;font-size:.88rem;color:#1a2d4e;" id="bannerCapNombre">—</div>
        <div style="font-size:.75rem;color:#4a5c7a;" id="bannerCapInfo">—</div>
      </div>
      <span id="bannerPartCount" class="badge-count">0 participantes</span>
      <button type="button" class="btn-outline btn-sm-icon" id="btnFinalizarCap" title="Finalizar">
        <i class="fas fa-circle-check"></i>
      </button>
    </div>
    <div id="sinCapActiva" style="text-align:center;padding:40px;background:#fff;border-radius:12px;border:1px solid #e5e7eb;">
      <i class="fas fa-arrow-pointer" style="font-size:2rem;color:#b0b0b0;margin-bottom:12px;display:block;"></i>
      <p style="color:#888;font-size:.9rem;">Primero guarde una capacitación o seleccione una del listado.</p>
      <button type="button" class="btn-outline" onclick="switchTab('nueva')" style="margin-top:10px;">
        <i class="fas fa-plus"></i> Crear nueva
      </button>
    </div>
    <div id="panelParticipantes" style="display:none;">
      <div class="row g-3">
        <div class="col-md-5">
          <div class="card-box">
            <div class="card-box-header"><h6><i class="fas fa-user-plus"></i> Agregar Participante</h6></div>
            <div class="card-box-body">
              <form id="formParticipante" novalidate>
                <input type="hidden" id="pCapId" name="id_capacitacion" value="0"/>
                <div class="row g-3">
                  <div class="col-6">
                    <label class="form-label-b">Nombre <span class="req">*</span></label>
                    <input type="text" class="fc" id="pNombre" name="nombre" placeholder="Primer nombre"/>
                  </div>
                  <div class="col-6">
                    <label class="form-label-b">Apellido</label>
                    <input type="text" class="fc" id="pApellido" name="apellido"/>
                  </div>
                  <div class="col-6">
                    <label class="form-label-b">DNI</label>
                    <input type="text" class="fc input-dni" id="pDni" name="dni" maxlength="15" placeholder="0000-0000-00000"/>
                  </div>
                  <div class="col-3">
                    <label class="form-label-b">Edad</label>
                    <input type="number" class="fc" id="pEdad" name="edad" min="1" max="120"/>
                  </div>
                  <div class="col-3">
                    <label class="form-label-b">Sexo</label>
                    <select class="fs" id="pSexo" name="sexo">
                      <option value="">—</option><option value="M">M</option><option value="F">F</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label-b">Organización</label>
                    <select class="fs" id="pOrg" name="id_organizacion">
                      <option value="">— Sin organización —</option>
                      <?php foreach ($organizaciones as $org): ?>
                      <option value="<?= $org['id_organizacion'] ?>"><?= htmlspecialchars($org['nombre']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </form>
            </div>
            <div style="padding:14px 18px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #f0f0f0;">
              <button type="button" class="btn-gris" id="btnLimpiarPart"><i class="fas fa-rotate-left"></i></button>
              <button type="button" class="btn-primario" id="btnAgregarPart"><i class="fas fa-user-plus"></i> Agregar</button>
            </div>
          </div>
        </div>
        <div class="col-md-7">
          <div class="card-box">
            <div class="card-box-header">
              <h6><i class="fas fa-users"></i> Participantes</h6>
              <span id="labelTotalPart" style="font-size:.78rem;color:#888;">0 registrados</span>
            </div>
            <div style="overflow-x:auto;">
              <table class="sag-table" id="tablaParticipantes" style="width:100%;">
                <thead>
                  <tr>
                    <th>#</th><th>Nombre</th><th>DNI</th>
                    <th style="width:45px;">Edad</th><th style="width:45px;">Sexo</th>
                    <th>Organización</th><th style="width:60px;"></th>
                  </tr>
                </thead>
                <tbody id="tbodyParticipantes">
                  <tr id="trSinParticipantes">
                    <td colspan="7" style="text-align:center;color:#aaa;padding:20px;font-size:.82rem;">Sin participantes</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB LISTADO -->
  <div id="tab-listado" style="display:none;">
    <div class="card-box">
      <div class="card-box-header">
        <h6><i class="fas fa-list-ul"></i> Listado de Capacitaciones</h6>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
          <select class="fs" id="filtroDepCap" style="width:165px;padding:6px 10px;">
            <option value="">Todos los deptos.</option>
            <?php foreach ($departamentos as $dep): ?>
            <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <select class="fs" id="filtroTemaCap" style="width:170px;padding:6px 10px;">
            <option value="">Todos los temas</option>
            <?php foreach ($temas as $tema): ?>
            <option value="<?= $tema['id_tema'] ?>"><?= htmlspecialchars($tema['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <select class="fs" id="filtroTecCap" style="width:170px;padding:6px 10px;">
            <option value="">Todos los técnicos</option>
            <?php foreach ($tecnicos as $tec): ?>
            <option value="<?= $tec['id_tecnico'] ?>"><?= htmlspecialchars($tec['nombre_completo']) ?></option>
            <?php endforeach; ?>
          </select>
          <select class="fs" id="filtroEstadoCap" style="width:120px;padding:6px 10px;">
            <option value="">Todos</option>
            <option value="borrador">Borrador</option>
            <option value="finalizado">Finalizado</option>
          </select>
          <button class="btn-outline" id="btnFiltrarCap" style="padding:6px 14px;"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
      </div>
      <div style="padding:16px;overflow-x:auto;">
        <table id="tablaCapacitaciones" class="sag-table" style="width:100%;">
          <thead>
            <tr>
              <th>#</th><th>Fecha</th><th>Tema</th><th>Subtema</th>
              <th>Técnico</th><th>Ubicación</th><th>Lugar</th>
              <th>Partic.</th><th>Horas</th><th>Estado</th><th style="width:100px;">Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /content -->

<!-- Modal Ver Cap -->
<div class="modal fade" id="modalVerCap" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title"><i class="fas fa-graduation-cap me-2"></i>Detalle de Capacitación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalCapBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn-primario btn-editar-desde-modal-cap" data-id=""><i class="fas fa-pen me-1"></i> Editar</button>
        <button type="button" class="btn-outline btn-participantes-desde-modal" data-id=""><i class="fas fa-users me-1"></i> Participantes</button>
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
