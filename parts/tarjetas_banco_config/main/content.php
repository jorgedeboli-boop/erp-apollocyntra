<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  $id_tarjeta = isset($_GET['id']) ? (int) $_GET['id'] : 0;
  $tarjeta = null;

  if ($id_tarjeta > 0) {
      echo '<script>window.idTarjetaBancoConfig = ' . $id_tarjeta . ';</script>';
      $conexion = conectar_bd();
      $stmt = mysqli_prepare(
          $conexion,
          'SELECT
              t.id_tarjeta_banco,
              t.numerotarjeta,
              t.banco_tarjeta,
              t.empresa_tarjeta_id,
              t.por_defecto,
              t.fecha_creacion,
              t.creado_por,
              b.nombre_banco,
              e.nombre_empresa
           FROM tarjetas_banco_empresas t
           LEFT JOIN bancos_config b ON b.id_banco = CAST(t.banco_tarjeta AS UNSIGNED)
           LEFT JOIN empresas e ON e.id_empresa = t.empresa_tarjeta_id
           WHERE t.id_tarjeta_banco = ?
           LIMIT 1'
      );
      mysqli_stmt_bind_param($stmt, 'i', $id_tarjeta);
      mysqli_stmt_execute($stmt);
      $tarjeta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
      mysqli_stmt_close($stmt);
      mysqli_close($conexion);
  }
  ?>

  <?php if (!$tarjeta) : ?>
    <div class="alert alert-danger">Tarjeta no encontrada</div>
  <?php else : ?>
    <div class="row">
      <div class="col-12">
        <div class="card mb-6">
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
            <div class="flex-grow-1 mt-4 mt-sm-12">
              <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                <div class="user-profile-info">
                  <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='tarjetas_banco_config.php'">
                    <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Tarjetas
                  </button>
                  <h4 class="mb-2"><?php echo htmlspecialchars($tarjeta['numerotarjeta'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <ul class="list-inline mb-0 d-flex align-items-center flex-wrap gap-4">
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-bank-line me-2 icon-24px"></i>
                      <span class="fw-medium"><?php echo htmlspecialchars($tarjeta['nombre_banco'] ?: ('ID ' . $tarjeta['banco_tarjeta']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-building-line me-2 icon-24px"></i>
                      <span class="fw-medium"><?php echo htmlspecialchars($tarjeta['nombre_empresa'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="list-inline-item">
                      <?php if ($tarjeta['por_defecto'] === 'true') : ?>
                        <span class="badge bg-label-success">Por defecto</span>
                      <?php else : ?>
                        <span class="badge bg-label-secondary">No por defecto</span>
                      <?php endif; ?>
                    </li>
                  </ul>
                </div>
                <div class="d-flex gap-2">
                  <?php if (!empty($puede_acceder_editar)) : ?>
                    <a href="editar_tarjeta_banco_config.php?id=<?php echo (int) $id_tarjeta; ?>" class="btn btn-primary">
                      <i class="icon-base ri ri-edit-line me-2"></i>Editar
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($puede_acceder_borrar)) : ?>
                    <button type="button" class="btn btn-outline-danger" onclick="eliminarTarjetaBancoConfig(<?php echo (int) $id_tarjeta; ?>)">
                      <i class="icon-base ri ri-delete-bin-line me-2"></i>Eliminar
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
      <div class="col-md-6">
        <div class="card mb-6">
          <div class="card-body">
            <small class="text-uppercase text-body-secondary">Detalle</small>
            <ul class="list-unstyled my-3">
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-hashtag icon-24px"></i>
                <span class="fw-medium mx-2">ID:</span>
                <span><?php echo (int) $tarjeta['id_tarjeta_banco']; ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-bank-card-2-line icon-24px"></i>
                <span class="fw-medium mx-2">Nº tarjeta:</span>
                <span><?php echo htmlspecialchars($tarjeta['numerotarjeta'], ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-bank-line icon-24px"></i>
                <span class="fw-medium mx-2">Banco ID:</span>
                <span>
                  <a href="banco_config.php?id=<?php echo (int) $tarjeta['banco_tarjeta']; ?>">
                    <?php echo (int) $tarjeta['banco_tarjeta']; ?>
                    — <?php echo htmlspecialchars($tarjeta['nombre_banco'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-building-line icon-24px"></i>
                <span class="fw-medium mx-2">Empresa:</span>
                <span><?php echo htmlspecialchars($tarjeta['nombre_empresa'] ?: '—', ENT_QUOTES, 'UTF-8'); ?> (ID <?php echo (int) $tarjeta['empresa_tarjeta_id']; ?>)</span>
              </li>
              <li class="d-flex align-items-center mb-2">
                <i class="icon-base ri ri-calendar-line icon-24px"></i>
                <span class="fw-medium mx-2">Fecha:</span>
                <span><?php echo htmlspecialchars($tarjeta['fecha_creacion'], ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
