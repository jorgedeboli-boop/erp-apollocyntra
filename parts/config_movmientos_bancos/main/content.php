<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  $tiposConfigLabels = [
      'transferencia_saliente' => 'Transferencia saliente',
      'transferencia_entrante' => 'Transferencia entrante',
      'cobro_tarjeta' => 'Cobro tarjeta',
      'pago_tarjeta' => 'Pago tarjeta',
      'retiro_tarjeta' => 'Retiro tarjeta',
      'retiro_cuenta' => 'Retiro cuenta',
      'ingreso_cuenta' => 'Ingreso cuenta',
  ];

  $id_config = isset($_GET['id']) ? (int) $_GET['id'] : 0;
  $config = null;

  if ($id_config > 0) {
      echo '<script>window.idConfigMovmientoBanco = ' . $id_config . ';</script>';
      $conexion = conectar_bd();
      $stmt = mysqli_prepare(
          $conexion,
          'SELECT
              c.id_config,
              c.nombre_config,
              c.rel_id_tipo_movimiento,
              c.tipo_config,
              c.estado_config,
              c.fecha_creacion,
              g.nombre_grupo,
              g.tipo_grupo
           FROM config_movimientos_bancos c
           LEFT JOIN grupos_movimientos g ON g.id_grupo = c.rel_id_tipo_movimiento
           WHERE c.id_config = ?
           LIMIT 1'
      );
      mysqli_stmt_bind_param($stmt, 'i', $id_config);
      mysqli_stmt_execute($stmt);
      $config = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
      mysqli_stmt_close($stmt);
      mysqli_close($conexion);
  }
  ?>

  <?php if (!$config) : ?>
    <div class="alert alert-danger">Configuración no encontrada</div>
  <?php else :
      $tipoLabel = $tiposConfigLabels[$config['tipo_config']] ?? $config['tipo_config'];
      ?>
    <div class="row">
      <div class="col-12">
        <div class="card mb-6">
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
            <div class="flex-grow-1 mt-4 mt-sm-12">
              <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                <div class="user-profile-info">
                  <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='config_movmientos_bancos.php'">
                    <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Configs
                  </button>
                  <h4 class="mb-2"><?php echo htmlspecialchars($config['nombre_config'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <ul class="list-inline mb-0 d-flex align-items-center flex-wrap gap-4">
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-folder-line me-2 icon-24px"></i>
                      <span class="fw-medium"><?php echo htmlspecialchars($config['nombre_grupo'] ?: ('ID ' . $config['rel_id_tipo_movimiento']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-exchange-line me-2 icon-24px"></i>
                      <span class="fw-medium"><?php echo htmlspecialchars($tipoLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="list-inline-item">
                      <?php if ($config['estado_config'] === 'true') : ?>
                        <span class="badge bg-label-success">Activo</span>
                      <?php else : ?>
                        <span class="badge bg-label-secondary">Inactivo</span>
                      <?php endif; ?>
                    </li>
                  </ul>
                </div>
                <div class="d-flex gap-2">
                  <?php if (!empty($puede_acceder_editar)) : ?>
                    <a href="editar_config_movmiento_banco.php?id=<?php echo (int) $id_config; ?>" class="btn btn-primary">
                      <i class="icon-base ri ri-edit-line me-2"></i>Editar
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($puede_acceder_borrar)) : ?>
                    <button type="button" class="btn btn-outline-danger" onclick="eliminarConfigMovmientoBanco(<?php echo (int) $id_config; ?>)">
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
                <span><?php echo (int) $config['id_config']; ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-text icon-24px"></i>
                <span class="fw-medium mx-2">Nombre:</span>
                <span><?php echo htmlspecialchars($config['nombre_config'], ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-folder-line icon-24px"></i>
                <span class="fw-medium mx-2">Grupo (id_grupo):</span>
                <span>
                  <?php echo (int) $config['rel_id_tipo_movimiento']; ?>
                  — <?php echo htmlspecialchars($config['nombre_grupo'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8'); ?>
                  <?php if (!empty($config['tipo_grupo'])) : ?>
                    <span class="text-muted">(<?php echo htmlspecialchars($config['tipo_grupo'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                  <?php endif; ?>
                </span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-exchange-line icon-24px"></i>
                <span class="fw-medium mx-2">Tipo config:</span>
                <span><?php echo htmlspecialchars($tipoLabel, ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
              <li class="d-flex align-items-center mb-2">
                <i class="icon-base ri ri-calendar-line icon-24px"></i>
                <span class="fw-medium mx-2">Fecha:</span>
                <span><?php echo htmlspecialchars($config['fecha_creacion'], ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
