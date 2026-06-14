<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/asistencia_tecnica.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Breadcrumb del módulo activo -->
  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-person-chalkboard"></i> Asistencia Técnica
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-person-chalkboard" style="color:var(--primario);margin-right:8px;"></i>Asistencia Técnica
      <small>Registro de visitas técnicas a productores — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-primario" id="btnNuevaAT">
      <i class="fas fa-plus"></i> Nueva Visita
    </button>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val"><?= $resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-handshake" style="color:var(--primario);margin-right:4px;"></i>Total visitas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;"><?= $resumen['finalizadas'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Finalizadas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat blue">
        <div class="ms-val"><?= $resumen['hombres'] ?></div>
        <div class="ms-lbl"><i class="fas fa-user" style="color:#3b82f6;margin-right:4px;"></i>Productores hombres</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#db2777;">
        <div class="ms-val"><?= $resumen['mujeres'] ?></div>
        <div class="ms-lbl"><i class="fas fa-user" style="color:#db2777;margin-right:4px;"></i>Productoras mujeres</div>
      </div>
    </div>
  </div>

  <!-- Tabla principal -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Visitas AT</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroDepAT" style="width:155px;padding:6px 10px;">
          <option value="">Todos los deptos.</option>
          <?php foreach ($departamentos as $dep): ?>
          <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroTipoAT" style="width:165px;padding:6px 10px;">
          <option value="">Todos los tipos</option>
          <?php foreach ($tiposAT as $t): ?>
          <option value="<?= $t['id_tipo_at'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroTemaAT" style="width:165px;padding:6px 10px;">
          <option value="">Todos los temas</option>
          <?php foreach ($temas as $tema): ?>
          <option value="<?= $tema['id_tema'] ?>"><?= htmlspecialchars($tema['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroTecAT" style="width:165px;padding:6px 10px;">
          <option value="">Todos los técnicos</option>
          <?php foreach ($tecnicos as $tec): ?>
          <option value="<?= $tec['id_tecnico'] ?>"><?= htmlspecialchars($tec['nombre_completo']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroEstadoAT" style="width:115px;padding:6px 10px;">
          <option value="">Todos</option>
          <option value="borrador">Borrador</option>
          <option value="finalizado">Finalizado</option>
        </select>
        <button class="btn-outline" id="btnFiltrarAT" style="padding:6px 14px;"><i class="fas fa-filter"></i> Filtrar</button>
      </div>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaAT" class="sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th><th>Fecha</th><th>Tipo</th><th>Productor</th>
            <th>Tema</th><th>Cultivo</th><th>Técnico</th><th>Ubicación</th>
            <th>Próx. Visita</th><th>Estado</th><th style="width:100px;">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<!-- MODAL Crear / Editar Visita AT -->
<div class="modal fade" id="modalAT" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalATTitulo">
          <i class="fas fa-file-medical me-2"></i>Nueva Visita Técnica
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formAT" data-sag-autosave="form-at" novalidate>
          <input type="hidden" id="atId" name="id_at" value="0"/>

          <div class="form-section-title" style="background:#f0fdf4;padding:8px 12px;border-left:4px solid #16a34a;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-tag me-1" style="color:#16a34a;"></i>Tipo y Fecha
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-5">
              <label class="form-label-b">Tipo de Asistencia <span class="req">*</span></label>
              <select class="fs" id="atTipo" name="id_tipo_at" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($tiposAT as $t):
                    // Detectar modalidad grupal por nombre (sin necesidad de modificar BD)
                    $nombreTipo = (string)$t['nombre'];
                    $patronesGrupal = ['grupal', 'taller', 'capacitaci', 'demostraci', 'día de campo', 'dia de campo', 'parcela', 'escuela de campo', 'gira', 'reunión', 'reunion'];
                    $esGrupal = false;
                    $nombreLower = mb_strtolower($nombreTipo, 'UTF-8');
                    foreach ($patronesGrupal as $p) {
                        if (mb_strpos($nombreLower, $p) !== false) { $esGrupal = true; break; }
                    }
                ?>
                <option value="<?= $t['id_tipo_at'] ?>" data-grupal="<?= $esGrupal ? '1' : '0' ?>">
                  <?= htmlspecialchars($nombreTipo) ?><?= $esGrupal ? ' (Grupal)' : '' ?>
                </option>
                <?php endforeach; ?>
              </select>
              <small style="color:#6b7280;font-size:.7rem;">Si elige un tipo grupal, los campos de productor individual se ocultan y se muestra el bloque de grupo.</small>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Fecha de Visita <span class="req">*</span></label>
              <input type="date" class="fc" id="atFecha" name="fecha_visita" required/>
            </div>
            <!-- Hora y Duración removidos (no aplica a este contexto) -->
          </div>

          <div class="form-section-title" style="background:#eff6ff;padding:8px 12px;border-left:4px solid #1e40af;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-location-dot me-1" style="color:#1e40af;"></i>Lugar
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label-b">Departamento <span class="req">*</span></label>
              <select class="fs" id="atDep" name="id_departamento" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($departamentos as $dep): ?>
                <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Municipio <span class="req">*</span></label>
              <select class="fs" id="atMun" name="id_municipio" required>
                <option value="">— Seleccione departamento —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Aldea</label>
              <select class="fs sag-search" id="atAldeaSelect" style="margin-bottom:4px;display:none;">
                <option value="">— Seleccione municipio primero —</option>
              </select>
              <input type="text" class="fc" id="atAldea" name="aldea" placeholder="Ej. El Porvenir" maxlength="200"/>
              <small style="color:#6b7280;font-size:.7rem;">Elija del catálogo o escriba directamente.</small>
            </div>
          </div>

          <!-- ══ MODO INDIVIDUAL (default) ══ -->
          <div id="atBloqueIndividual">
            <div class="form-section-title" style="background:#fef3c7;padding:8px 12px;border-left:4px solid #d97706;border-radius:4px;margin-bottom:14px;">
              <i class="fas fa-user me-1" style="color:#d97706;"></i>Productor Visitado (Atención Individual)
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label-b">Nombre <span class="req">*</span></label>
                <input type="text" class="fc" id="atPNombre" name="productor_nombre" placeholder="Primer nombre" maxlength="200"/>
              </div>
              <div class="col-md-3">
                <label class="form-label-b">Apellido</label>
                <input type="text" class="fc" id="atPApellido" name="productor_apellido" maxlength="200"/>
              </div>
              <div class="col-md-3">
                <label class="form-label-b">DNI</label>
                <input type="text" class="fc input-dni" id="atPDni" name="productor_dni" maxlength="15" placeholder="0000-0000-00000" inputmode="numeric"/>
              </div>
              <div class="col-md-1">
                <label class="form-label-b">Edad</label>
                <input type="number" class="fc" id="atPEdad" name="productor_edad" min="1" max="120"/>
              </div>
              <div class="col-md-2">
                <label class="form-label-b">Sexo</label>
                <select class="fs" id="atPSexo" name="productor_sexo">
                  <option value="">— —</option>
                  <option value="M">Masculino</option>
                  <option value="F">Femenino</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label-b">Teléfono</label>
                <input type="text" class="fc input-tel" id="atPTel" name="productor_telefono" placeholder="9999-9999" maxlength="9" inputmode="numeric"/>
              </div>
              <div class="col-md-4">
                <label class="form-label-b">Organización</label>
                <select class="fs" id="atOrg" name="id_organizacion">
                  <option value="">— Sin organización —</option>
                  <?php foreach ($organizaciones as $org): ?>
                  <option value="<?= $org['id_organizacion'] ?>"><?= htmlspecialchars($org['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label-b">Área Productiva (mz)</label>
                <input type="number" class="fc" id="atArea" name="area_productiva" step="0.1" min="0"/>
              </div>
            </div>
          </div>

          <!-- ══ MODO GRUPAL (se muestra cuando el tipo lo amerite) ══ -->
          <div id="atBloqueGrupal" style="display:none;">
            <div class="form-section-title" style="background:#dbeafe;padding:8px 12px;border-left:4px solid #2563eb;border-radius:4px;margin-bottom:14px;">
              <i class="fas fa-users me-1" style="color:#2563eb;"></i>Grupo Asistido (Atención Grupal)
            </div>
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label-b">Total asistentes <span class="req">*</span></label>
                <input type="number" class="fc" id="atGrTotal" name="grupo_total" min="1" max="500" placeholder="Ej. 25"/>
              </div>
              <div class="col-md-3">
                <label class="form-label-b">Hombres</label>
                <input type="number" class="fc" id="atGrHombres" name="grupo_hombres" min="0" max="500" placeholder="0"/>
              </div>
              <div class="col-md-3">
                <label class="form-label-b">Mujeres</label>
                <input type="number" class="fc" id="atGrMujeres" name="grupo_mujeres" min="0" max="500" placeholder="0"/>
              </div>
              <div class="col-md-3">
                <label class="form-label-b">Organización (opcional)</label>
                <select class="fs" id="atGrOrg" name="id_organizacion_grupal">
                  <option value="">— Sin organización —</option>
                  <?php foreach ($organizaciones as $org): ?>
                  <option value="<?= $org['id_organizacion'] ?>"><?= htmlspecialchars($org['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label-b">Nombres de participantes / lista (opcional)</label>
                <textarea class="fc" id="atGrLista" name="grupo_lista" rows="3" placeholder="Uno por línea o separados por coma"></textarea>
                <small style="color:#6b7280;font-size:.7rem;">También puede adjuntar la lista de asistencia firmada en la sección "Ficha Técnica" más abajo.</small>
              </div>
            </div>
          </div>

          <div class="form-section-title" style="background:#f0fdf4;padding:8px 12px;border-left:4px solid #16a34a;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-seedling me-1" style="color:#16a34a;"></i>Contenido Técnico
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label-b">Tema <span class="req">*</span></label>
              <select class="fs" id="atTema" name="id_tema" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($temas as $tema): ?>
                <option value="<?= $tema['id_tema'] ?>"><?= htmlspecialchars($tema['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Subtema</label>
              <select class="fs" id="atSubtema" name="id_subtema">
                <option value="">— Seleccione tema primero —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Cultivo / Rubro</label>
              <select class="fs" id="atCultivo" name="id_cultivo">
                <option value="">— Sin especificar —</option>
                <?php
                $tipoActual = '';
                foreach ($cultivos as $cult):
                    if ($cult['tipo'] !== $tipoActual):
                        if ($tipoActual) echo '</optgroup>';
                        $tipoActual = $cult['tipo'];
                        $etiq = $tipoActual === 'cultivo' ? 'Cultivos' : ($tipoActual === 'ganaderia' ? 'Ganadería' : 'Otros');
                        echo '<optgroup label="' . htmlspecialchars($etiq) . '">';
                    endif;
                ?>
                <option value="<?= $cult['id_cultivo'] ?>"><?= htmlspecialchars($cult['nombre']) ?></option>
                <?php endforeach; if ($tipoActual) echo '</optgroup>'; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label-b">Descripción de la visita</label>
              <textarea class="fc" id="atDescripcion" name="descripcion" rows="3"
                        placeholder="Actividades realizadas, metodología..."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label-b">
                <i class="fas fa-list-check me-1" style="color:var(--primario);"></i>
                Resultados / Logros obtenidos
                <span style="font-size:.73rem;color:#aaa;font-weight:normal;"> (uno por línea)</span>
              </label>
              <textarea class="fc" id="atResultados" name="resultados" rows="3"
                        placeholder="Ej.&#10;Se aplicó fertilizante en 2 mz&#10;El productor recibió semilla certificada"></textarea>
            </div>
          </div>

          <div class="form-section-title" style="background:#f5f3ff;padding:8px 12px;border-left:4px solid #7c3aed;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-user-tie me-1" style="color:#7c3aed;"></i>Técnico y Seguimiento
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label-b">Técnico responsable <span class="req">*</span></label>
              <select class="fs" id="atTecnico" name="id_tecnico" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($tecnicos as $tec): ?>
                <option value="<?= $tec['id_tecnico'] ?>"><?= htmlspecialchars($tec['nombre_completo']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Próxima visita</label>
              <input type="date" class="fc" id="atProxVisita" name="prox_visita"/>
            </div>
            <div class="col-md-5">
              <label class="form-label-b">Observaciones adicionales</label>
              <textarea class="fc" id="atObservaciones" name="observaciones" rows="1"
                        placeholder="Notas internas..."></textarea>
            </div>
          </div>
        </form>

        <!-- ══ FICHA TÉCNICA (antes "Evidencia / Listado de Atención") ══ -->
        <div class="card-box mt-3" id="bloqueEvidenciaAT" style="border:1.5px dashed #cbd5e1;background:#fafafa;">
          <div class="card-box-header" style="background:transparent;border-bottom:1px solid #e5e7eb;">
            <h6><i class="fas fa-paperclip"></i> Ficha Técnica
              <span id="evATEstadoBadge" style="margin-left:8px;font-size:.7rem;padding:3px 10px;border-radius:12px;font-weight:700;background:#f1f5f9;color:#6b7280;">PENDIENTE</span>
            </h6>
            <small style="color:#888;font-size:.74rem;">PDF, Excel o imagen — máx 10 MB</small>
          </div>
          <div style="padding:14px 18px;">
            <!-- Sin archivo: formulario de carga -->
            <div id="evATSinArchivo">
              <form id="formEvidenciaAT" enctype="multipart/form-data">
                <input type="hidden" id="evATIdAt" name="id_at" value="0"/>
                <div class="row g-3 align-items-end">
                  <div class="col-md-7">
                    <label class="form-label-b">Archivo de Ficha Técnica</label>
                    <input type="file" class="fc" id="evATArchivo" name="archivo"
                           accept=".pdf,.xls,.xlsx,.jpg,.jpeg,.png" required/>
                    <small id="evATAviso" style="color:#6b7280;font-size:.72rem;">PDF, Excel o imagen (máx 10 MB).</small>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label-b">Observaciones</label>
                    <input type="text" class="fc" id="evATObs" name="observaciones" placeholder="Ej. Hoja firmada por productores"/>
                  </div>
                </div>
                <div style="margin-top:12px;">
                  <button type="button" class="btn-primario" id="btnSubirEvAT">
                    <i class="fas fa-cloud-arrow-up"></i> Subir Ficha Técnica
                  </button>
                </div>
              </form>
            </div>

            <!-- Con archivo -->
            <div id="evATConArchivo" style="display:none;">
              <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#fff;border-radius:8px;border:1.5px solid #e5e7eb;">
                <div style="font-size:2rem;color:#0d9488;" id="evATIcono"><i class="fas fa-file"></i></div>
                <div style="flex:1;min-width:0;">
                  <div style="font-weight:700;font-size:.88rem;" id="evATNombre">archivo</div>
                  <div style="font-size:.74rem;color:#666;" id="evATMeta">—</div>
                  <div id="evATObsBox" style="font-size:.76rem;color:#555;margin-top:4px;display:none;"></div>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                  <a href="#" id="evATVerLink" target="_blank" class="btn-outline btn-sm-icon" title="Ver / Descargar">
                    <i class="fas fa-eye"></i>
                  </a>
                  <button type="button" class="btn-outline btn-sm-icon" id="btnReemplazarEvAT" title="Reemplazar archivo">
                    <i class="fas fa-arrows-rotate"></i>
                  </button>
                </div>
              </div>
              <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="btn-primario btn-sm" id="btnValidarEvAT" style="background:#16a34a;border-color:#16a34a;">
                  <i class="fas fa-circle-check"></i> Validar
                </button>
                <button type="button" class="btn-outline btn-sm" id="btnRechazarEvAT" style="color:#dc2626;border-color:#fecaca;">
                  <i class="fas fa-circle-xmark"></i> Rechazar
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-primario" id="btnGuardarAT">
          <i class="fas fa-floppy-disk me-1"></i> Guardar Visita
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ver AT -->
<div class="modal fade" id="modalVerAT" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title"><i class="fas fa-person-chalkboard me-2"></i>Detalle de Asistencia Técnica</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalATBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn-primario btn-editar-desde-modal-at" data-id=""><i class="fas fa-pen me-1"></i> Editar</button>
        <button type="button" class="btn-outline btn-finalizar-desde-modal" data-id=""><i class="fas fa-circle-check me-1"></i> Finalizar</button>
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
