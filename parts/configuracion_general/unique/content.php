<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">

  <!-- Header -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
        <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
          <div class="flex-grow-1 mt-4 mt-sm-12">
            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
              <div class="user-profile-info">
                <h4 class="mb-2">Configuración general</h4>
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                  <li class="list-inline-item">
                    <i class="icon-base ri ri-settings-3-line me-2 icon-24px"></i><span class="fw-medium">Parámetros globales del sistema</span>
                  </li>
                </ul>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#modalNuevaConfiguracion">
                  <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear nuevo
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Header -->

  <!-- Navbar pills -->
  <div class="row">
    <div class="col-md-12">
      <div class="nav-align-top">
        <ul class="nav nav-pills mb-4" role="tablist">
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-cg-general" aria-controls="navs-pills-cg-general" aria-selected="true">
              <i class="icon-base ri ri-settings-line icon-sm me-2"></i>General
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-cg-textos" aria-controls="navs-pills-cg-textos" aria-selected="false" tabindex="-1">
              <i class="icon-base ri ri-file-text-line icon-sm me-2"></i>Textos
            </button>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <!--/ Navbar pills -->

  <!-- Tab Content -->
  <div class="tab-content" id="navs-pills-cg-content">
    <div class="tab-pane fade show active" id="navs-pills-cg-general" role="tabpanel">
      <?php
      $conexion_cfg_list = conectar_bd();
      $configs_list = array();
      $q_cfg = "SELECT id_config, typ_config, texto_value, boleano_value, options_value, integro_value, decimal_value, varchar_value, titulo_config
                FROM configuracion_general WHERE estado_condifg = 'true' ORDER BY titulo_config ASC";
      $res_cfg = mysqli_query($conexion_cfg_list, $q_cfg);
      if ($res_cfg) {
          while ($rw = mysqli_fetch_assoc($res_cfg)) {
              $configs_list[] = $rw;
          }
          mysqli_free_result($res_cfg);
      }
      mysqli_close($conexion_cfg_list);
      ?>
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body" id="cfg-lista-general">
              <?php if (empty($configs_list)) : ?>
                <p class="text-body-secondary mb-0">No hay parámetros de configuración. Use <strong>Crear nuevo</strong> para añadir el primero.</p>
              <?php else : ?>
                <?php foreach ($configs_list as $cfg) :
                    $cid = (int) $cfg['id_config'];
                    $ctyp = $cfg['typ_config'];
                    $ctit = $cfg['titulo_config'];
                    ?>
                <div class="cfg-item border rounded p-4 mb-4" data-cfg-id="<?php echo $cid; ?>" data-cfg-typ="<?php echo htmlspecialchars($ctyp, ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                    <div class="flex-grow-1 min-w-0">
                      <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                        <h6 class="mb-0"><?php echo htmlspecialchars($ctit, ENT_QUOTES, 'UTF-8'); ?></h6>
                        <span class="badge bg-label-primary"><?php echo htmlspecialchars($ctyp, ENT_QUOTES, 'UTF-8'); ?></span>
                      </div>
                      <?php if ($ctyp === 'text') : ?>
                        <label class="form-label small text-body-secondary">Texto</label>
                        <textarea class="form-control cfg-campo-actualizable" rows="4"><?php echo htmlspecialchars($cfg['texto_value'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                      <?php elseif ($ctyp === 'boleano') :
                          $on = ($cfg['boleano_value'] === 'true');
                          ?>
                        <label class="switch switch-lg">
                          <input type="checkbox" class="switch-input" <?php echo $on ? 'checked' : ''; ?> disabled />
                          <span class="switch-toggle-slider">
                            <span class="switch-on">
                              <i class="icon-base ri ri-check-line"></i>
                            </span>
                            <span class="switch-off">
                              <i class="icon-base ri ri-close-line"></i>
                            </span>
                          </span>
                          <span class="switch-label"><?php echo htmlspecialchars($ctit, ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                      <?php elseif ($ctyp === 'options') :
                          $opts_raw = array_map('trim', explode(',', $cfg['options_value']));
                          $opts = array_values(array_filter($opts_raw, function ($o) { return $o !== ''; }));
                          $sel_opt = trim($cfg['varchar_value']);
                          $sel_ok = $sel_opt !== '' && in_array($sel_opt, $opts, true);
                          ?>
                        <label class="form-label small text-body-secondary">Opciones</label>
                        <select class="form-select cfg-select2-options" id="cfg-opt-<?php echo $cid; ?>" disabled>
                          <?php if (empty($opts)) : ?>
                            <option value="">Sin opciones definidas</option>
                          <?php else : ?>
                            <?php if (!$sel_ok) : ?>
                              <option value="">Seleccione…</option>
                            <?php endif; ?>
                            <?php foreach ($opts as $op) : ?>
                              <option value="<?php echo htmlspecialchars($op, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($sel_ok && $sel_opt === $op) ? 'selected' : ''; ?>><?php echo htmlspecialchars($op, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </select>
                        <?php if ($sel_opt !== '' && !$sel_ok && !empty($opts)) : ?>
                          <p class="small text-warning mb-0 mt-2">varchar_value no coincide con ninguna opción: <?php echo htmlspecialchars($sel_opt, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                      <?php elseif ($ctyp === 'integro') : ?>
                        <label class="form-label small text-body-secondary">Entero</label>
                        <input type="number" step="1" class="form-control cfg-campo-actualizable" value="<?php echo htmlspecialchars((string) (int) $cfg['integro_value'], ENT_QUOTES, 'UTF-8'); ?>" />
                      <?php elseif ($ctyp === 'decimal') : ?>
                        <label class="form-label small text-body-secondary">Decimal</label>
                        <input type="number" step="0.01" class="form-control cfg-campo-actualizable" value="<?php echo htmlspecialchars(number_format((float) $cfg['decimal_value'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>" />
                      <?php elseif ($ctyp === 'varchar') : ?>
                        <label class="form-label small text-body-secondary">Varchar</label>
                        <input type="text" class="form-control cfg-campo-actualizable" maxlength="168" value="<?php echo htmlspecialchars($cfg['varchar_value'], ENT_QUOTES, 'UTF-8'); ?>" />
                      <?php endif; ?>
                    </div>
                    <?php if (in_array($ctyp, array('text', 'varchar', 'integro', 'decimal'), true)) : ?>
                      <div class="flex-shrink-0">
                        <button type="button" class="btn btn-primary cfg-btn-actualizar waves-effect waves-light">
                          <i class="icon-base ri ri-save-3-line icon-16px me-1"></i>Actualizar
                        </button>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="navs-pills-cg-textos" role="tabpanel">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/ Tab Content -->

  <!-- Modal nueva configuración -->
  <div class="modal fade" id="modalNuevaConfiguracion" tabindex="-1" aria-labelledby="modalNuevaConfiguracionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNuevaConfiguracionLabel">Nueva configuración</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <form id="formNuevaConfiguracion">
            <div class="mb-3">
              <label for="cfg_typ_config" class="form-label">Tipo</label>
              <select name="typ_config" id="cfg_typ_config" class="form-select select2-cfg-tipo" required>
                <option value="text">text</option>
                <option value="boleano">boleano</option>
                <option value="options">options</option>
                <option value="integro">integro</option>
                <option value="decimal">decimal</option>
                <option value="varchar">varchar</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="cfg_titulo_config" class="form-label">Nombre</label>
              <input type="text" class="form-control" name="titulo_config" id="cfg_titulo_config" maxlength="64" required autocomplete="off" placeholder="Identificador o título">
            </div>
            <div class="mb-0">
              <label for="cfg_valor" class="form-label">Valor</label>
              <textarea class="form-control" name="valor" id="cfg_valor" rows="3" placeholder="Contenido según el tipo elegido"></textarea>
              <div class="form-text">Para <strong>boleano</strong> use true/false, 1/0 o sí/no.</div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="btnGuardarConfiguracion">Guardar</button>
        </div>
      </div>
    </div>
  </div>

</div>
<!-- / Content -->
