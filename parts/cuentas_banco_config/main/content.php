<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  $id_cuenta = isset($_GET['id']) ? (int) $_GET['id'] : 0;
  $cuenta = null;

  if ($id_cuenta > 0) {
      echo '<script>window.idCuentaBancoConfig = ' . $id_cuenta . ';</script>';
      $conexion = conectar_bd();
      $stmt = mysqli_prepare(
          $conexion,
          'SELECT
              c.id_cuenta_banco,
              c.numerocuenta,
              c.banco_cuenta,
              c.empresa_cuenta_id,
              c.por_defecto,
              c.fecha_creacion,
              c.creado_por,
              b.nombre_banco,
              e.nombre_empresa
           FROM cuentas_banco_empresas c
           LEFT JOIN bancos_config b ON b.id_banco = CAST(c.banco_cuenta AS UNSIGNED)
           LEFT JOIN empresas e ON e.id_empresa = c.empresa_cuenta_id
           WHERE c.id_cuenta_banco = ?
           LIMIT 1'
      );
      mysqli_stmt_bind_param($stmt, 'i', $id_cuenta);
      mysqli_stmt_execute($stmt);
      $cuenta = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
      mysqli_stmt_close($stmt);
      mysqli_close($conexion);
  }
  ?>

  <?php if (!$cuenta) : ?>
    <div class="alert alert-danger">Cuenta no encontrada</div>
  <?php else : ?>
    <div class="row">
      <div class="col-12">
        <div class="card mb-6">
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
            <div class="flex-grow-1 mt-4 mt-sm-12">
              <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                <div class="user-profile-info">
                  <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='cuentas_banco_config.php'">
                    <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Cuentas
                  </button>
                  <h4 class="mb-2"><?php echo htmlspecialchars($cuenta['numerocuenta'], ENT_QUOTES, 'UTF-8'); ?></h4>
                  <ul class="list-inline mb-0 d-flex align-items-center flex-wrap gap-4">
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-bank-line me-2 icon-24px"></i>
                      <span class="fw-medium"><?php echo htmlspecialchars($cuenta['nombre_banco'] ?: ('ID ' . $cuenta['banco_cuenta']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-building-line me-2 icon-24px"></i>
                      <span class="fw-medium"><?php echo htmlspecialchars($cuenta['nombre_empresa'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="list-inline-item">
                      <?php if ($cuenta['por_defecto'] === 'true') : ?>
                        <span class="badge bg-label-success">Por defecto</span>
                      <?php else : ?>
                        <span class="badge bg-label-secondary">No por defecto</span>
                      <?php endif; ?>
                    </li>
                  </ul>
                </div>
                <div class="d-flex gap-2">
                  <?php if (!empty($puede_acceder_editar)) : ?>
                    <a href="editar_cuenta_banco_config.php?id=<?php echo (int) $id_cuenta; ?>" class="btn btn-primary">
                      <i class="icon-base ri ri-edit-line me-2"></i>Editar
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($puede_acceder_borrar)) : ?>
                    <button type="button" class="btn btn-outline-danger" onclick="eliminarCuentaBancoConfig(<?php echo (int) $id_cuenta; ?>)">
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
                <span><?php echo (int) $cuenta['id_cuenta_banco']; ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-bank-card-line icon-24px"></i>
                <span class="fw-medium mx-2">Nº cuenta:</span>
                <span><?php echo htmlspecialchars($cuenta['numerocuenta'], ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-bank-line icon-24px"></i>
                <span class="fw-medium mx-2">Banco ID:</span>
                <span>
                  <a href="banco_config.php?id=<?php echo (int) $cuenta['banco_cuenta']; ?>">
                    <?php echo (int) $cuenta['banco_cuenta']; ?>
                    — <?php echo htmlspecialchars($cuenta['nombre_banco'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8'); ?>
                  </a>
                </span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-building-line icon-24px"></i>
                <span class="fw-medium mx-2">Empresa:</span>
                <span><?php echo htmlspecialchars($cuenta['nombre_empresa'] ?: '—', ENT_QUOTES, 'UTF-8'); ?> (ID <?php echo (int) $cuenta['empresa_cuenta_id']; ?>)</span>
              </li>
              <li class="d-flex align-items-center mb-2">
                <i class="icon-base ri ri-calendar-line icon-24px"></i>
                <span class="fw-medium mx-2">Fecha:</span>
                <span><?php echo htmlspecialchars($cuenta['fecha_creacion'], ENT_QUOTES, 'UTF-8'); ?></span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
