<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  $id_banco = isset($_GET['id']) ? (int) $_GET['id'] : 0;
  $banco = null;
  $api_banco = null;
  $nombre_pais = '';
  $nombre_provincia = '';
  $nombre_poblacion = '';

  if ($id_banco > 0) {
      echo '<script>window.idBancoConfig = ' . $id_banco . ';</script>';
      $conexion = conectar_bd();
      $stmt = mysqli_prepare(
          $conexion,
          'SELECT
              b.id_banco,
              b.nombre_banco,
              b.direccion_banco,
              b.provincia_banco,
              b.poblacion_banco,
              b.pais_banco,
              b.estado_banco,
              b.telefono_banco,
              b.email_banco,
              b.contacto_banco,
              b.fecha_creacion,
              c.name_spanish AS nombre_pais,
              prov.nombreProvince AS nombre_provincia,
              pob.poblacion AS nombre_poblacion
           FROM bancos_config b
           LEFT JOIN countrys c ON c.id_country = b.pais_banco
           LEFT JOIN provincias prov ON prov.id_province = b.provincia_banco
           LEFT JOIN poblacion pob ON pob.idpoblacion = b.poblacion_banco
           WHERE b.id_banco = ?
           LIMIT 1'
      );
      if ($stmt) {
          mysqli_stmt_bind_param($stmt, 'i', $id_banco);
          mysqli_stmt_execute($stmt);
          $res = mysqli_stmt_get_result($stmt);
          $banco = $res ? mysqli_fetch_assoc($res) : null;
          mysqli_stmt_close($stmt);
      }

      if ($banco) {
          $nombre_pais = (string) ($banco['nombre_pais'] ?? '');
          $nombre_provincia = (string) ($banco['nombre_provincia'] ?? '');
          $nombre_poblacion = (string) ($banco['nombre_poblacion'] ?? '');

          $stmtApi = mysqli_prepare(
              $conexion,
              'SELECT id_api, api_key, token_value, secret_api_key, url_api, rel_id_banco,
                      estado_api, id_comercio_api, fecha_creacion
               FROM apis_bancos
               WHERE rel_id_banco = ?
               ORDER BY id_api DESC
               LIMIT 1'
          );
          if ($stmtApi) {
              mysqli_stmt_bind_param($stmtApi, 'i', $id_banco);
              mysqli_stmt_execute($stmtApi);
              $resApi = mysqli_stmt_get_result($stmtApi);
              $api_banco = $resApi ? mysqli_fetch_assoc($resApi) : null;
              mysqli_stmt_close($stmtApi);
          }
      }
      mysqli_close($conexion);
  }

  $api_estado_activo = $api_banco && (($api_banco['estado_api'] ?? '') === 'true');
  ?>

  <?php if (!$banco) : ?>
    <div class="alert alert-danger">Banco no encontrado</div>
  <?php else : ?>
    <div class="row">
      <div class="col-12">
        <div class="card mb-6">
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
            <div class="flex-grow-1 mt-4 mt-sm-12">
              <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                <div class="user-profile-info">
                  <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='bancos_config.php'">
                    <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Bancos
                  </button>
                  <h4 class="mb-2"><?php echo htmlspecialchars($banco['nombre_banco'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-user-line me-2 icon-24px"></i>
                      <span class="fw-medium">Contacto: <?php echo htmlspecialchars($banco['contacto_banco'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-map-pin-line me-2 icon-24px"></i>
                      <span class="fw-medium">
                        <?php echo htmlspecialchars($nombre_poblacion !== '' ? $nombre_poblacion : 'N/A', ENT_QUOTES, 'UTF-8'); ?>,
                        <?php echo htmlspecialchars($nombre_provincia !== '' ? $nombre_provincia : 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </li>
                    <li class="list-inline-item">
                      <?php if (($banco['estado_banco'] ?? '') === 'true') : ?>
                        <span class="badge bg-label-success">Activo</span>
                      <?php else : ?>
                        <span class="badge bg-label-secondary">Inactivo</span>
                      <?php endif; ?>
                    </li>
                  </ul>
                </div>
                <div class="d-flex gap-2">
                  <?php if (!empty($puede_acceder_editar)) : ?>
                    <a href="editar_banco_config.php?id=<?php echo (int) $id_banco; ?>" class="btn btn-primary waves-effect waves-light">
                      <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar banco
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($puede_acceder_borrar)) : ?>
                    <button type="button" class="btn btn-outline-danger waves-effect" onclick="eliminarBancoConfig(<?php echo (int) $id_banco; ?>)">
                      <i class="icon-base ri ri-delete-bin-line icon-16px me-2"></i>Eliminar
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="nav-align-top">
          <ul class="nav nav-pills mb-4" role="tablist">
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link waves-effect waves-light active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-banco-info" aria-controls="navs-pills-banco-info" aria-selected="true">
                <i class="icon-base ri ri-bank-line icon-sm me-2"></i>Información
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-banco-api" aria-controls="navs-pills-banco-api" aria-selected="false" tabindex="-1">
                <i class="icon-base ri ri-key-2-line icon-sm me-2"></i>API Config
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="navs-pills-banco-info" role="tabpanel">
        <div class="row">
          <div class="col-xl-6 col-lg-6 col-md-6">
            <div class="card mb-6">
              <div class="card-body">
                <small class="card-text text-uppercase text-body-secondary small">Información general</small>
                <ul class="list-unstyled my-3 py-1">
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-bank-line icon-24px"></i>
                    <span class="fw-medium mx-2">Nombre:</span>
                    <span><?php echo htmlspecialchars($banco['nombre_banco'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-user-3-line icon-24px"></i>
                    <span class="fw-medium mx-2">Contacto:</span>
                    <span><?php echo htmlspecialchars($banco['contacto_banco'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-phone-line icon-24px"></i>
                    <span class="fw-medium mx-2">Teléfono:</span>
                    <span><?php echo htmlspecialchars($banco['telefono_banco'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-mail-line icon-24px"></i>
                    <span class="fw-medium mx-2">Email:</span>
                    <span><?php echo htmlspecialchars($banco['email_banco'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-2">
                    <i class="icon-base ri ri-calendar-line icon-24px"></i>
                    <span class="fw-medium mx-2">Fecha creación:</span>
                    <span><?php echo htmlspecialchars($banco['fecha_creacion'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="col-xl-6 col-lg-6 col-md-6">
            <div class="card mb-6">
              <div class="card-body">
                <small class="card-text text-uppercase text-body-secondary small">Dirección</small>
                <ul class="list-unstyled my-3 py-1">
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-road-map-line icon-24px"></i>
                    <span class="fw-medium mx-2">Dirección:</span>
                    <span><?php echo htmlspecialchars($banco['direccion_banco'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-building-line icon-24px"></i>
                    <span class="fw-medium mx-2">Población:</span>
                    <span><?php echo htmlspecialchars($nombre_poblacion !== '' ? $nombre_poblacion : 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-map-2-line icon-24px"></i>
                    <span class="fw-medium mx-2">Provincia:</span>
                    <span><?php echo htmlspecialchars($nombre_provincia !== '' ? $nombre_provincia : 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-2">
                    <i class="icon-base ri ri-earth-line icon-24px"></i>
                    <span class="fw-medium mx-2">País:</span>
                    <span><?php echo htmlspecialchars($nombre_pais !== '' ? $nombre_pais : 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="navs-pills-banco-api" role="tabpanel">
        <div class="row">
          <div class="col-xl-8 col-lg-10 col-md-12">
            <div class="card mb-6">
              <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="card-title mb-0">
                    <i class="icon-base ri ri-key-2-line icon-24px text-body me-2"></i>Configuración API
                  </h5>
                  <small class="text-muted">Credenciales de integración del banco</small>
                </div>
                <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#modalEditarApiBanco">
                  <i class="icon-base ri ri-edit-line icon-16px me-2"></i><?php echo $api_banco ? 'Editar API' : 'Configurar API'; ?>
                </button>
              </div>
              <div class="card-body">
                <?php if (!$api_banco) : ?>
                  <div class="alert alert-secondary mb-0">
                    No hay configuración API para este banco. Pulsa <strong>Configurar API</strong> para crearla.
                  </div>
                <?php else : ?>
                  <div class="d-flex align-items-center mb-4">
                    <span class="fw-medium me-2">Estado de la API:</span>
                    <?php if ($api_estado_activo) : ?>
                      <span class="badge bg-label-success">Activada</span>
                    <?php else : ?>
                      <span class="badge bg-label-secondary">Desactivada</span>
                    <?php endif; ?>
                  </div>
                  <ul class="list-unstyled mb-0">
                    <li class="d-flex align-items-start mb-4">
                      <i class="icon-base ri ri-store-2-line icon-24px mt-1"></i>
                      <span class="fw-medium mx-2">ID cliente:</span>
                      <span class="text-break"><?php echo htmlspecialchars((string) $api_banco['id_comercio_api'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="d-flex align-items-start mb-4">
                      <i class="icon-base ri ri-key-line icon-24px mt-1"></i>
                      <span class="fw-medium mx-2">API Key:</span>
                      <span class="text-break font-monospace"><?php echo htmlspecialchars((string) $api_banco['api_key'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="d-flex align-items-start mb-4">
                      <i class="icon-base ri ri-shield-keyhole-line icon-24px mt-1"></i>
                      <span class="fw-medium mx-2">Token:</span>
                      <span class="text-break font-monospace"><?php echo htmlspecialchars((string) $api_banco['token_value'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="d-flex align-items-start mb-4">
                      <i class="icon-base ri ri-lock-password-line icon-24px mt-1"></i>
                      <span class="fw-medium mx-2">Secret API:</span>
                      <span class="text-break font-monospace"><?php echo htmlspecialchars((string) $api_banco['secret_api_key'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="d-flex align-items-start mb-2">
                      <i class="icon-base ri ri-link icon-24px mt-1"></i>
                      <span class="fw-medium mx-2">URL API:</span>
                      <span class="text-break"><?php echo htmlspecialchars((string) $api_banco['url_api'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                  </ul>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="modalEditarApiBanco" tabindex="-1" aria-labelledby="modalEditarApiBancoTitulo" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditarApiBancoTitulo"><?php echo $api_banco ? 'Editar API Config' : 'Configurar API'; ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form id="formApiBanco">
              <input type="hidden" name="id_api" id="api_id_api" value="<?php echo $api_banco ? (int) $api_banco['id_api'] : 0; ?>" />
              <input type="hidden" name="rel_id_banco" id="api_rel_id_banco" value="<?php echo (int) $id_banco; ?>" />

              <div class="form-floating form-floating-outline mb-4">
                <input type="number" class="form-control" id="api_id_comercio" name="id_comercio_api" min="1" required
                       value="<?php echo $api_banco ? (int) $api_banco['id_comercio_api'] : ''; ?>"
                       placeholder="ID cliente" />
                <label for="api_id_comercio">ID cliente *</label>
              </div>

              <div class="form-floating form-floating-outline mb-4">
                <textarea class="form-control" id="api_api_key" name="api_key" style="min-height: 90px" required
                          placeholder="API Key"><?php echo $api_banco ? htmlspecialchars((string) $api_banco['api_key'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                <label for="api_api_key">API Key *</label>
              </div>

              <div class="form-floating form-floating-outline mb-4">
                <input type="text" class="form-control" id="api_token_value" name="token_value" maxlength="168" required
                       value="<?php echo $api_banco ? htmlspecialchars((string) $api_banco['token_value'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                       placeholder="Token" />
                <label for="api_token_value">Token *</label>
              </div>

              <div class="form-floating form-floating-outline mb-4">
                <textarea class="form-control" id="api_secret_api_key" name="secret_api_key" style="min-height: 90px" required
                          placeholder="Secret API"><?php echo $api_banco ? htmlspecialchars((string) $api_banco['secret_api_key'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                <label for="api_secret_api_key">Secret API *</label>
              </div>

              <div class="form-floating form-floating-outline mb-4">
                <input type="url" class="form-control" id="api_url_api" name="url_api" required
                       value="<?php echo $api_banco ? htmlspecialchars((string) $api_banco['url_api'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                       placeholder="URL API" />
                <label for="api_url_api">URL API *</label>
              </div>

              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="api_estado_api" name="estado_api" value="true"
                  <?php echo $api_estado_activo ? 'checked' : ''; ?> />
                <label class="form-check-label" for="api_estado_api">API activada</label>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnGuardarApiBanco" onclick="guardarApiBanco()">
              <i class="icon-base ri ri-save-3-line me-2"></i>Guardar
            </button>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<!-- / Content -->
