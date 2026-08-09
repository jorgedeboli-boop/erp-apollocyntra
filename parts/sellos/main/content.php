<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  $id_sello = isset($_GET['id']) ? (int) $_GET['id'] : (isset($id) ? (int) $id : 0);
  $sello = null;

  if ($id_sello > 0) {
      echo '<script>window.idSello = ' . $id_sello . ';</script>';
      $conexion = conectar_bd();
      $stmt = mysqli_prepare(
          $conexion,
          'SELECT
              s.id_sello,
              s.nombre_sello,
              s.sello_logotipo,
              s.imagen_logotipo,
              s.fecha_creacion,
              s.creado_por,
              IFNULL(u.usuario, \'\') AS creado_por_nombre
           FROM sellos s
           LEFT JOIN usuarios u ON s.creado_por = u.id_usuario
           WHERE s.id_sello = ?
           LIMIT 1'
      );
      if ($stmt) {
          mysqli_stmt_bind_param($stmt, 'i', $id_sello);
          mysqli_stmt_execute($stmt);
          $res = mysqli_stmt_get_result($stmt);
          $sello = $res ? mysqli_fetch_assoc($res) : null;
          mysqli_stmt_close($stmt);
      }
      mysqli_close($conexion);
  }

  $sello_logotipo = ($sello && ($sello['sello_logotipo'] ?? '') === 'true') ? 'true' : 'false';
  $imagen_logotipo = $sello['imagen_logotipo'] ?? '';
  $nombre_sello = $sello['nombre_sello'] ?? '';
  ?>

  <?php if (!$sello) : ?>
    <div class="alert alert-danger">Sello no encontrado</div>
  <?php else : ?>
    <div class="row">
      <div class="col-12">
        <div class="card mb-6">
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
            <div class="flex-grow-1 mt-4 mt-sm-12">
              <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                <div class="user-profile-info">
                  <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='sellos.php'">
                    <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Sellos
                  </button>
                  <h4 class="mb-2"><?php echo htmlspecialchars($nombre_sello, ENT_QUOTES, 'UTF-8'); ?></h4>
                  <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-hashtag me-2 icon-24px"></i>
                      <span class="fw-medium">ID: <?php echo (int) $id_sello; ?></span>
                    </li>
                    <li class="list-inline-item">
                      <?php if ($sello_logotipo === 'true') : ?>
                        <span class="badge bg-label-success">Con logotipo</span>
                      <?php else : ?>
                        <span class="badge bg-label-secondary">Sin logotipo</span>
                      <?php endif; ?>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="card mb-6">
          <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Datos del sello</h5>
          </div>
          <div class="card-body mt-4">
            <form id="formActualizarSello" method="POST" action="parts/sellos/main/actualizar_sello.php">
              <input type="hidden" name="id_sello" id="id_sello" value="<?php echo (int) $id_sello; ?>" />

              <div class="form-floating form-floating-outline mb-8">
                <input
                  type="text"
                  class="form-control"
                  id="nombre_sello"
                  name="nombre_sello"
                  placeholder="Nombre del sello"
                  maxlength="164"
                  value="<?php echo htmlspecialchars($nombre_sello, ENT_QUOTES, 'UTF-8'); ?>"
                  required
                />
                <label for="nombre_sello">Nombre del sello *</label>
              </div>

              <div class="mb-4">
                <label class="form-label d-block mb-3">¿Posee logotipo? *</label>
                <div class="d-flex gap-3">
                  <div class="form-check custom-option custom-option-basic">
                    <label class="form-check-label custom-option-content" for="sello_logotipo_si">
                      <input class="form-check-input" type="radio" name="sello_logotipo" value="true" id="sello_logotipo_si" <?php echo $sello_logotipo === 'true' ? 'checked' : ''; ?> required>
                      <span class="custom-option-header">
                        <span class="badge bg-label-success">Sí</span>
                      </span>
                    </label>
                  </div>
                  <div class="form-check custom-option custom-option-basic">
                    <label class="form-check-label custom-option-content" for="sello_logotipo_no">
                      <input class="form-check-input" type="radio" name="sello_logotipo" value="false" id="sello_logotipo_no" <?php echo $sello_logotipo === 'false' ? 'checked' : ''; ?> required>
                      <span class="custom-option-header">
                        <span class="badge bg-label-secondary">No</span>
                      </span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="sellos.php" class="btn btn-text-primary">
                  <i class="icon-base ri ri-arrow-left-line me-2"></i>Volver
                </a>
                <div>
                  <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                    <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                    Aguarde...
                  </button>
                  <button type="submit" class="btn btn-primary" id="btnActualizarSello">
                    <i class="icon-base ri ri-check-line me-2"></i>Actualizar sello
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card mb-6">
          <div class="card-header border-bottom d-flex align-items-center justify-content-between">
            <div>
              <h5 class="card-title mb-0">Vista previa del sello</h5>
              <small class="text-muted">Dimensiones recomendadas: 260×150 px · PNG transparente o JPG</small>
            </div>
            <button
              type="button"
              id="btnSubirLogotipoSello"
              class="btn btn-primary btn-sm waves-effect waves-light"
              onclick="abrirModalSubirLogotipoSello()"
              style="<?php echo $sello_logotipo === 'true' ? '' : 'display:none;'; ?>"
            >
              <i class="icon-base ri ri-upload-line icon-16px me-1"></i>Cambiar logotipo
            </button>
          </div>
          <div class="card-body">
            <div class="border rounded p-4 text-muted contenedor_sello_logotipo" id="contenedor_preview_sello">
              <div id="sello_con_logo" style="<?php echo $sello_logotipo === 'true' ? '' : 'display:none;'; ?>">
                <div id="sello">
                  <span class="spans_sellos" id="ordago">
                    <?php if (!empty($imagen_logotipo)) : ?>
                      <img src="photos/<?php echo htmlspecialchars($imagen_logotipo, ENT_QUOTES, 'UTF-8'); ?>" alt="Logotipo del sello" class="img-fluid">
                    <?php else : ?>
                      <span class="text-muted small">Sin imagen</span>
                    <?php endif; ?>
                  </span>
                  <span class="spans_sellos" id="nombre_empresa">Nombre Empresa</span>
                  <span class="spans_sellos" id="cif_empresa">CIF: B999999999</span>
                  <span class="spans_sellos" id="direccion_tienda">Dirección tienda</span>
                  <span class="spans_sellos" id="datos_varios">
                    <span id="codigo_postal_tienda">22222 </span>
                    <span id="poblacion_tienda"> Población </span>
                    <span id="provincia_tienda"> (Provincia)</span>
                  </span>
                </div>
              </div>

              <div id="sello_sin_logo" style="<?php echo $sello_logotipo === 'false' ? '' : 'display:none;'; ?>">
                <div id="sello">
                  <span class="spans_sellos_sinlogo" id="ordago_sinlogo"></span>
                  <span class="spans_sellos_sinlogo" id="nombre_empresa_sinlogo">Nombre Empresa</span>
                  <span class="spans_sellos_sinlogo" id="cif_empresa_sinlogo">CIF: B999999999</span>
                  <span class="spans_sellos_sinlogo" id="direccion_tienda_sinlogo">Dirección tienda</span>
                  <span class="spans_sellos_sinlogo" id="datos_varios_sinlogo">
                    <span>22222 </span>
                    <span> Población </span>
                    <span> (Provincia)</span>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<!-- / Content -->
