<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/fortalecimiento.js?v=' . APP_VERSION . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Breadcrumb -->
  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-chart-line"></i> Acciones de Fortalecimiento
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-chart-line" style="color:var(--primario);margin-right:8px;"></i>Acciones de Fortalecimiento
      <small>Registro y seguimiento — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <div class="mode-tabs">
        <button class="mode-tab active" onclick="switchTab('listado')">
          <i class="fas fa-list" style="font-size:.75rem;margin-right:5px;"></i>Listado
        </button>
        <button class="mode-tab" onclick="switchTab('participantes')">
          <i class="fas fa-users" style="font-size:.75rem;margin-right:5px;"></i>Participantes
        </button>
      </div>
      <button class="btn-primario" id="btnNuevaAccion">
        <i class="fas fa-plus"></i> Nueva Acción
      </button>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val"><?= $resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-list-check" style="color:var(--primario);margin-right:4px;"></i>Total</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#f5a623;">
        <div class="ms-val" style="color:#f5a623;"><?= $resumen['planificado'] ?></div>
        <div class="ms-lbl"><i class="fas fa-clock" style="color:#f5a623;margin-right:4px;"></i>Planificadas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#3b82f6;">
        <div class="ms-val" style="color:#3b82f6;"><?= $resumen['en_ejecucion'] ?></div>
        <div class="ms-lbl"><i class="fas fa-spinner" style="color:#3b82f6;margin-right:4px;"></i>En Ejecución</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;"><?= $resumen['completado'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Completadas</div>
      </div>
    </div>
  </div>

  <!-- TAB LISTADO -->
  <div id="tab-listado">
    <div class="card-box">
      <div class="card-box-header">
        <h6><i class="fas fa-list-ul"></i> Listado de Acciones</h6>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
          <select class="fs" id="filtroTipo" style="width:150px;padding:6px 10px;">
            <option value="">Todos los tipos</option>
            <option value="consultoria">Consultoría</option>
            <option value="taller">Taller</option>
            <option value="reunion">Reunión</option>
            <option value="estudio">Estudio</option>
            <option value="asistencia_tecnica">Asistencia Técnica</option>
            <option value="capacitacion">Capacitación</option>
            <option value="otro">Otro</option>
          </select>
          <select class="fs" id="filtroEstado" style="width:140px;padding:6px 10px;">
            <option value="">Todos los estados</option>
            <option value="planificado">Planificado</option>
            <option value="en_ejecucion">En Ejecución</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
          </select>
          <select class="fs" id="filtroDepto" style="width:160px;padding:6px 10px;">
            <option value="">Todos los deptos.</option>
            <?php foreach ($departamentos as $dep): ?>
            <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn-outline" id="btnFiltrar" style="padding:6px 14px;">
            <i class="fas fa-filter"></i> Filtrar
          </button>
        </div>
      </div>
      <div style="padding:16px;overflow-x:auto;">
        <table id="tablaAcciones" class="sag-table" style="width:100%;">
          <thead>
            <tr>
              <th>#</th>
              <th>Tipo</th>
              <th>Título</th>
              <th>Alcance</th>
              <th>Inicio</th>
              <th>Fin</th>
              <th>Responsable</th>
              <th class="text-center">Part.</th>
              <th class="text-center">Avance</th>
              <th class="text-center">Estado</th>
              <th style="width:80px;"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB PARTICIPANTES -->
  <div id="tab-participantes" style="display:none;">

    <div id="accionActivaBanner" style="display:none;background:var(--tema-light);border:1px solid var(--borde);border-radius:10px;padding:12px 18px;margin-bottom:16px;display:none;align-items:center;gap:12px;">
      <i class="fas fa-chart-line" style="color:var(--primario);font-size:1.3rem;"></i>
      <div style="flex:1;">
        <div style="font-weight:700;font-size:.88rem;color:var(--texto);" id="bannerAccionNombre">—</div>
        <div style="font-size:.75rem;color:var(--texto-sec);" id="bannerAccionInfo">—</div>
      </div>
      <span id="bannerPartCount" class="badge-count">0 participantes</span>
    </div>

    <div id="sinAccionActiva" style="text-align:center;padding:40px;background:#fff;border-radius:12px;border:1px solid #e5e7eb;">
      <i class="fas fa-arrow-pointer" style="font-size:2rem;color:#b0b0b0;margin-bottom:12px;display:block;"></i>
      <p style="color:#888;font-size:.9rem;">Primero registre una acción o seleccione una del listado.</p>
      <button type="button" class="btn-outline" id="btnCrearDesdeParticipantes" style="margin-top:10px;">
        <i class="fas fa-plus"></i> Nueva acción
      </button>
    </div>

    <div id="panelParticipantes" style="display:none;">
      <div class="row g-3">

        <!-- Formulario agregar -->
        <div class="col-md-5">
          <div class="card-box">
            <div class="card-box-header"><h6><i class="fas fa-user-plus"></i> Agregar Participante</h6></div>
            <div class="card-box-body">
              <form id="formParticipante" novalidate>
                <input type="hidden" id="pAccionId" name="id_accion" value="0"/>
                <div class="row g-3">
                  <div class="col-6">
                    <label class="form-label-b">Nombre <span class="req">*</span></label>
                    <input type="text" class="fc" id="pNombre" name="nombre" maxlength="100" placeholder="Primer nombre"/>
                  </div>
                  <div class="col-6">
                    <label class="form-label-b">Apellido</label>
                    <input type="text" class="fc" id="pApellido" name="apellido" maxlength="100"/>
                  </div>
                  <div class="col-8">
                    <label class="form-label-b">Cargo / Puesto</label>
                    <input type="text" class="fc" id="pCargo" name="cargo" maxlength="150" placeholder="Ej. Director, Técnico, Asesor"/>
                  </div>
                  <div class="col-4">
                    <label class="form-label-b">Sexo</label>
                    <select class="fs" id="pSexo" name="sexo">
                      <option value="">—</option>
                      <option value="M">M</option>
                      <option value="F">F</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <label class="form-label-b">Institución</label>
                    <input type="text" class="fc" id="pInstitucion" name="institucion" maxlength="200" placeholder="Ej. SAG, SEPLAN, FAO"/>
                  </div>
                </div>
              </form>
            </div>
            <div style="padding:14px 18px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #f0f0f0;">
              <button type="button" class="btn-gris" id="btnLimpiarPart"><i class="fas fa-rotate-left"></i></button>
              <button type="button" class="btn-primario" id="btnAgregarPart">
                <i class="fas fa-user-plus"></i> Agregar
              </button>
            </div>
          </div>
        </div>

        <!-- Tabla de participantes -->
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
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Institución</th>
                    <th style="width:50px;">Sexo</th>
                    <th style="width:50px;"></th>
                  </tr>
                </thead>
                <tbody id="tbodyParticipantes">
                  <tr id="trSinPart">
                    <td colspan="6" style="text-align:center;color:#aaa;padding:20px;font-size:.82rem;">Sin participantes registrados</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /content -->


<!-- ═══════════════════════════════════════════════════════════════
     MODAL — Crear / Editar Acción
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAccion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalAccionTitulo">
          <i class="fas fa-plus-circle me-2"></i>Nueva Acción
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formAccion" novalidate>
          <input type="hidden" id="aId" name="id_accion" value="0"/>

          <!-- SECCIÓN 1: Identificación -->
          <div class="form-section-title" style="background:#f5f3ff;padding:8px 12px;border-left:4px solid var(--primario);border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-tag me-1" style="color:var(--primario);"></i>Identificación
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label-b">Tipo de Acción <span class="req">*</span></label>
              <select class="fs" id="aTipo" name="tipo_accion" required>
                <option value="">— Seleccione —</option>
                <option value="consultoria">Consultoría</option>
                <option value="taller">Taller</option>
                <option value="reunion">Reunión</option>
                <option value="estudio">Estudio</option>
                <option value="asistencia_tecnica">Asistencia Técnica</option>
                <option value="capacitacion">Capacitación</option>
                <option value="otro">Otro</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label-b">Título <span class="req">*</span></label>
              <input type="text" class="fc" id="aTitulo" name="titulo" maxlength="300"
                     placeholder="Nombre descriptivo de la acción" required/>
            </div>
            <div class="col-12">
              <label class="form-label-b">Objetivo</label>
              <textarea class="fc" id="aObjetivo" name="objetivo" rows="2"
                        placeholder="¿Qué se espera lograr con esta acción?"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label-b">Descripción / Observaciones</label>
              <textarea class="fc" id="aDescripcion" name="descripcion" rows="2"
                        placeholder="Detalles adicionales, metodología, contexto..."></textarea>
            </div>
          </div>

          <!-- SECCIÓN 2: Alcance y fechas -->
          <div class="form-section-title" style="background:#eff6ff;padding:8px 12px;border-left:4px solid #1e40af;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-location-dot me-1" style="color:#1e40af;"></i>Alcance y Período
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label-b">Alcance <span class="req">*</span></label>
              <select class="fs" id="aAlcance" name="alcance" required>
                <option value="nacional">Nacional</option>
                <option value="departamental">Departamental</option>
              </select>
            </div>
            <div class="col-md-3" id="wrapDepartamento" style="display:none;">
              <label class="form-label-b">Departamento <span class="req">*</span></label>
              <select class="fs" id="aDepartamento" name="id_departamento">
                <option value="">— Seleccione —</option>
                <?php foreach ($departamentos as $dep): ?>
                <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Fecha Inicio <span class="req">*</span></label>
              <input type="date" class="fc" id="aFechaInicio" name="fecha_inicio" required/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Fecha Fin</label>
              <input type="date" class="fc" id="aFechaFin" name="fecha_fin"/>
            </div>
          </div>

          <!-- SECCIÓN 3: Responsabilidad -->
          <div class="form-section-title" style="background:#fef3c7;padding:8px 12px;border-left:4px solid #d97706;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-user-tie me-1" style="color:#d97706;"></i>Responsabilidad
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label-b">Responsable (técnico)</label>
              <select class="fs" id="aResponsable" name="id_responsable">
                <option value="">— Sin asignar —</option>
                <?php foreach ($tecnicos as $tec): ?>
                <option value="<?= $tec['id_tecnico'] ?>"><?= htmlspecialchars($tec['nombre_completo']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label-b">Institución Ejecutora</label>
              <input type="text" class="fc" id="aInstitucion" name="institucion_ejecutora" maxlength="200"
                     placeholder="Ej. SAG, FAO, PNUD, Consultor externo"/>
            </div>
          </div>

          <!-- SECCIÓN 4: Seguimiento -->
          <div class="form-section-title" style="background:#f0fdf4;padding:8px 12px;border-left:4px solid #16a34a;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-chart-line me-1" style="color:#16a34a;"></i>Seguimiento e Indicadores
          </div>
          <div class="row g-3 mb-3">
            <div class="col-12">
              <label class="form-label-b">Indicador de resultado</label>
              <input type="text" class="fc" id="aIndicador" name="indicador" maxlength="300"
                     placeholder="Ej. Número de técnicos capacitados, % de implementación..."/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Meta</label>
              <input type="number" class="fc" id="aMeta" name="meta" min="0" step="0.01"
                     placeholder="Valor meta"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Avance actual</label>
              <input type="number" class="fc" id="aAvance" name="avance" min="0" step="0.01"
                     placeholder="0"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Presupuesto asignado (L)</label>
              <input type="number" class="fc" id="aPresAsig" name="presupuesto_asignado" min="0" step="0.01"
                     placeholder="0.00"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Presupuesto ejecutado (L)</label>
              <input type="number" class="fc" id="aPresEjec" name="presupuesto_ejecutado" min="0" step="0.01"
                     placeholder="0.00"/>
            </div>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-primario" id="btnGuardarAccion">
          <i class="fas fa-floppy-disk me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     MODAL — Ver detalle
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalVerAccion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title"><i class="fas fa-chart-line me-2"></i>Detalle de Acción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalVerBody" style="padding:24px;">
        <!-- Generado por JS -->
      </div>
      <div class="modal-footer" id="modalVerFooter">
        <button type="button" class="btn-primario btn-editar-desde-modal" data-id="">
          <i class="fas fa-pen me-1"></i> Editar
        </button>
        <button type="button" class="btn-outline btn-participantes-desde-modal" data-id="">
          <i class="fas fa-users me-1"></i> Participantes
        </button>
        <!-- Botones de estado (se muestran según estado actual) -->
        <button type="button" class="btn-outline d-none btn-iniciar" data-id=""
                style="color:#3b82f6;border-color:#bfdbfe;">
          <i class="fas fa-play me-1"></i> Iniciar
        </button>
        <button type="button" class="btn-outline d-none btn-completar" data-id=""
                style="color:#16a34a;border-color:#bbf7d0;">
          <i class="fas fa-circle-check me-1"></i> Completar
        </button>
        <button type="button" class="btn-outline d-none btn-cancelar" data-id=""
                style="color:#dc2626;border-color:#fecaca;">
          <i class="fas fa-ban me-1"></i> Cancelar
        </button>
        <button type="button" class="btn-outline d-none btn-reactivar" data-id=""
                style="color:#f5a623;border-color:#fde68a;">
          <i class="fas fa-rotate-left me-1"></i> Reactivar
        </button>
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     MODAL — Confirmar eliminación
════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalConfirmarDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header" style="border-bottom:none;padding-bottom:0;">
        <h6 class="modal-title" style="color:#dc2626;font-weight:700;">
          <i class="fas fa-triangle-exclamation me-1"></i> Eliminar acción
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="font-size:.88rem;color:#555;padding-top:10px;">
        ¿Confirma que desea eliminar esta acción? Esta operación no puede deshacerse.
      </div>
      <div class="modal-footer" style="border-top:none;">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">No, cancelar</button>
        <button type="button" class="btn-danger" id="btnConfirmarDelete"
                style="background:#dc2626;color:#fff;border:none;padding:7px 18px;border-radius:8px;font-weight:600;cursor:pointer;">
          <i class="fas fa-trash-can me-1"></i> Sí, eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
