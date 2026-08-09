<?php
function servicios_ui_unidad($v)
{
    $m = ['hora' => 'Hora', 'media_hora' => 'Media hora', 'dia' => 'Día', 'sesion' => 'Sesión'];
    return $m[$v] ?? $v;
}
function servicios_ui_tipo_fact($v)
{
    $m = ['por_hora' => 'Por hora', 'precio_fijo' => 'Precio fijo', 'por_sesion' => 'Por sesión'];
    return $m[$v] ?? $v;
}

$id_servicio = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$servicio = null;

if ($id_servicio > 0) {
    $conexion = conectar_bd();
    $stmt = mysqli_prepare($conexion, '
        SELECT s.*, e.nombre_empresa,
               c.nombre_categoria,
               uc.nombre_usuario AS nu_crea, uc.apellido_usuario AS au_crea,
               um.nombre_usuario AS nu_mod, um.apellido_usuario AS au_mod
        FROM servicios s
        LEFT JOIN empresas e ON s.rel_id_empresa = e.id_empresa
        LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
        LEFT JOIN usuarios uc ON s.id_usuario_creador = uc.id_usuario
        LEFT JOIN usuarios um ON s.id_usuario_modificador = um.id_usuario
        WHERE s.id = ?
        LIMIT 1
    ');
    mysqli_stmt_bind_param($stmt, 'i', $id_servicio);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $servicio = mysqli_fetch_assoc($res);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}
?>

<div class="container-fluid flex-grow-1 container-p-y">
<?php if ($servicio) { ?>

  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
        <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
          <div class="flex-grow-1 mt-4 mt-sm-12">
            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
              <div class="user-profile-info">
                <h4 class="mb-2"><?php echo htmlspecialchars($servicio['nombre']); ?></h4>
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                  <li class="list-inline-item">
                    <i class="icon-base ri ri-hashtag me-2 icon-24px"></i><span class="fw-medium">ID <?php echo (int)$servicio['id']; ?></span>
                  </li>
                  <?php if (!empty($servicio['codigo'])) { ?>
                  <li class="list-inline-item">
                    <i class="icon-base ri ri-barcode-line me-2 icon-24px"></i><span class="fw-medium"><?php echo htmlspecialchars($servicio['codigo']); ?></span>
                  </li>
                  <?php } ?>
                  <li class="list-inline-item">
                    <?php if ((int)$servicio['activo'] === 1) { ?>
                    <span class="badge bg-label-success rounded-pill">Activo</span>
                    <?php } else { ?>
                    <span class="badge bg-label-secondary rounded-pill">Inactivo</span>
                    <?php } ?>
                  </li>
                </ul>
              </div>
              <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='servicios.php'">
                  <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Servicios
                </button>
                <?php if ($puede_acceder_editar): ?>
                <a href="editar_servicio.php?id=<?php echo (int)$servicio['id']; ?>" class="btn btn-primary waves-effect waves-light">
                  <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar
                </a>
                <?php if ((int)$servicio['activo'] === 1) { ?>
                <button type="button" class="btn btn-outline-danger waves-effect" onclick="desactivarServicio(<?php echo (int)$servicio['id']; ?>)">
                  <i class="icon-base ri ri-close-circle-line icon-16px me-2"></i>Desactivar
                </button>
                <?php } ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-xl-5 col-lg-6">
      <div class="card mb-6">
        <div class="card-body">
          <small class="card-text text-uppercase text-body-secondary small">Empresa y clasificación</small>
          <ul class="list-unstyled my-3 py-1">
            <li class="d-flex align-items-center mb-3">
              <span class="fw-medium me-2">Empresa:</span>
              <span><?php echo htmlspecialchars($servicio['nombre_empresa'] ?? '—'); ?></span>
            </li>
            <li class="d-flex align-items-center mb-3">
              <span class="fw-medium me-2">Categoría:</span>
              <span><?php echo htmlspecialchars($servicio['nombre_categoria'] ?? '—'); ?></span>
            </li>
            <li class="d-flex align-items-start mb-2">
              <span class="fw-medium me-2">Descripción:</span>
              <span class="text-body-secondary"><?php echo nl2br(htmlspecialchars($servicio['descripcion'] ?? '')); ?></span>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <div class="col-xl-7 col-lg-6">
      <div class="card mb-6">
        <div class="card-body">
          <small class="card-text text-uppercase text-body-secondary small">Tiempo y facturación</small>
          <ul class="list-unstyled my-3 py-1">
            <li class="mb-2"><span class="fw-medium">Tipo facturación:</span> <?php echo htmlspecialchars(servicios_ui_tipo_fact($servicio['tipo_facturacion'] ?? '')); ?></li>
            <li class="mb-2"><span class="fw-medium">Unidad tiempo:</span> <?php echo htmlspecialchars(servicios_ui_unidad($servicio['unidad_tiempo'] ?? '')); ?></li>
            <li class="mb-2"><span class="fw-medium">Duración:</span> <?php echo htmlspecialchars((string)$servicio['duracion_horas']); ?> h · <?php echo (int)$servicio['duracion_minutos']; ?> min</li>
            <li class="mb-2"><span class="fw-medium">Mín. horas / incremento:</span> <?php echo htmlspecialchars((string)$servicio['minimo_horas']); ?> / <?php echo htmlspecialchars((string)$servicio['incremento_horas']); ?></li>
          </ul>
          <small class="card-text text-uppercase text-body-secondary small">Precios</small>
          <ul class="list-unstyled my-3 py-1">
            <li class="mb-2"><span class="fw-medium">Precio hora:</span> <?php echo number_format((float)$servicio['precio_hora'], 2, ',', '.'); ?> €</li>
            <li class="mb-2"><span class="fw-medium">Coste hora:</span> <?php echo number_format((float)$servicio['precio_coste_hora'], 2, ',', '.'); ?> €</li>
            <li class="mb-2"><span class="fw-medium">Precio fijo:</span> <?php echo number_format((float)$servicio['precio_fijo'], 2, ',', '.'); ?> €</li>
            <li class="mb-2"><span class="fw-medium">IVA:</span> <?php echo number_format((float)$servicio['porcentaje_iva'], 2, ',', '.'); ?> %</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
        <div class="card-body">
          <small class="card-text text-uppercase text-body-secondary small">Notas y auditoría</small>
          <p class="mt-3 mb-4 text-body-secondary"><?php echo $servicio['notas'] !== '' ? nl2br(htmlspecialchars($servicio['notas'])) : '<em class="text-muted">Sin notas</em>'; ?></p>
          <div class="row">
            <div class="col-md-6">
              <p class="mb-1 small text-muted">Creado</p>
              <p class="mb-0"><?php echo !empty($servicio['fecha_creacion']) ? date('d/m/Y H:i', strtotime($servicio['fecha_creacion'])) : '—'; ?>
                <?php if (!empty($servicio['nu_crea'])) { ?>
                · <?php echo htmlspecialchars(trim(($servicio['nu_crea'] ?? '') . ' ' . ($servicio['au_crea'] ?? ''))); ?>
                <?php } ?>
              </p>
            </div>
            <div class="col-md-6">
              <p class="mb-1 small text-muted">Última modificación</p>
              <p class="mb-0"><?php echo !empty($servicio['fecha_modificacion']) ? date('d/m/Y H:i', strtotime($servicio['fecha_modificacion'])) : '—'; ?>
                <?php if (!empty($servicio['nu_mod'])) { ?>
                · <?php echo htmlspecialchars(trim(($servicio['nu_mod'] ?? '') . ' ' . ($servicio['au_mod'] ?? ''))); ?>
                <?php } ?>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php } else { ?>
  <div class="alert alert-danger">Servicio no encontrado o ID no válido.</div>
  <a href="servicios.php" class="btn btn-primary">Volver al listado</a>
<?php } ?>
</div>
