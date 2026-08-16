<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // Cargar datos del artículo
  $id_articulo = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  if ($id_articulo) {
      $conexion = conectar_bd();
      
      // Consulta para obtener datos del artículo con información completa
      $query_articulo = "
          SELECT 
              av.*,
              u.nombre_usuario,
              u.apellido_usuario,
              u.id_usuario
          FROM articulos_venta av
          LEFT JOIN usuarios u ON av.creado_por = u.id_usuario
          WHERE av.id = ?
      ";
      
      $stmt_articulo = mysqli_prepare($conexion, $query_articulo);
      mysqli_stmt_bind_param($stmt_articulo, 'i', $id_articulo);
      mysqli_stmt_execute($stmt_articulo);
      $result_articulo = mysqli_stmt_get_result($stmt_articulo);
      
      if ($result_articulo && mysqli_num_rows($result_articulo) > 0) {
          $articulo = mysqli_fetch_assoc($result_articulo);
          mysqli_stmt_close($stmt_articulo);
          $sucursal_articulo = (int) ($articulo['id_sucursal_destino'] ?? 0);

          $trazabilidad_rows = array();
          $stmt_traz = mysqli_prepare($conexion, "
              SELECT t.*, u.usuario AS nombre_usuario_resuelto
              FROM trazabilidad_articulos_venta t
              LEFT JOIN usuarios u ON t.usuario_accion = u.id_usuario
              WHERE t.id_articulo = ?
              ORDER BY t.fecha_accion DESC, t.id_trazabilidad_articulo DESC
          ");
          if ($stmt_traz) {
              mysqli_stmt_bind_param($stmt_traz, 'i', $id_articulo);
              mysqli_stmt_execute($stmt_traz);
              $result_traz = mysqli_stmt_get_result($stmt_traz);
              while ($row_traz = mysqli_fetch_assoc($result_traz)) {
                  $trazabilidad_rows[] = $row_traz;
              }
              mysqli_stmt_close($stmt_traz);
          }
          
          $estado_articulo_header = strtolower((string) ($articulo['estado'] ?? ''));
  ?>
<?php if (!$puede_acceder_editar): ?>
<style>
  .visor_documento_global .btn, .table .btn {
    display: none !important;
  }
</style>
<?php endif; ?>
  <!-- Header -->
  <div class="row">
    <div class="col-12">
      <div class="card mb-6">
        <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
          <div class="flex-grow-1 mt-4 mt-sm-12">
            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
              <div class="user-profile-info">
                <h4 class="mb-2">Artículo #<?php echo htmlspecialchars($articulo['id']); ?></h4>
                <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                  <li class="list-inline-item">
                    <i class="icon-base ri ri-calendar-line me-2 icon-24px"></i><span class="fw-medium">Fecha: <?php echo !empty($articulo['fecha_alta']) ? date('d/m/Y', strtotime($articulo['fecha_alta'])) : 'N/A'; ?></span>
                  </li>
                </ul>
              </div>
              <div class="d-flex gap-2 align-items-center flex-wrap justify-content-sm-end">
                <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='articulos.php'">
                  <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Artículos
                </button>
                <?php
                $codigo_regimen_badge = isset($articulo['system_codigo_regimen']) && $articulo['system_codigo_regimen'] !== '' && $articulo['system_codigo_regimen'] !== null
                    ? $articulo['system_codigo_regimen']
                    : '—';
                $precio_venta_badge = isset($articulo['precio']) ? number_format((float) $articulo['precio'], 2, ',', '.') . ' €' : '—';

                $venta_id_rel = isset($articulo['last_id_venta']) ? (int) $articulo['last_id_venta'] : 0;
                $venta_num_suc = isset($articulo['id_venta_sucursal']) ? trim((string) $articulo['id_venta_sucursal']) : '';
                $venta_num_display = ($venta_num_suc !== '' && $venta_num_suc !== '0') ? $venta_num_suc : (($venta_id_rel > 0) ? (string) $venta_id_rel : '');
                $mostrar_badge_venta = in_array($estado_articulo_header, ['vendido', 'vendido_web'], true) && $venta_num_display !== '';
                ?>
                <div class="badge bg-label-success rounded-pill lh-xs badget-estados"><?php echo htmlspecialchars($precio_venta_badge); ?></div>
                <div class="badge bg-label-warning rounded-pill lh-xs badget-estados"><?php echo htmlspecialchars($codigo_regimen_badge); ?></div>
                
                <?php if ($mostrar_badge_venta): ?>
                <div class="badge bg-label-secondary rounded-pill lh-xs badget-estados">Venta Nº <?php echo htmlspecialchars($venta_num_display); ?></div>
                <?php endif; ?>
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
            <button type="button" class="nav-link waves-effect waves-light active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-datos-articulo" aria-controls="navs-pills-top-datos-articulo" aria-selected="true">
              <i class="icon-base ri ri-shopping-bag-line icon-sm me-2"></i>Datos Artículo
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-precios" aria-controls="navs-pills-top-precios" aria-selected="false" tabindex="-1">
              <i class="icon-base ri ri-money-euro-circle-line icon-sm me-2"></i>Precios
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-trazabilidad" aria-controls="navs-pills-top-trazabilidad" aria-selected="false" tabindex="-1">
              <i class="icon-base ri ri-route-line icon-sm me-2"></i>Trazabilidad
            </button>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <!--/ Navbar pills -->

  <!-- Tab content -->
  <div class="tab-content">
    <!-- Tab Datos Artículo -->
    <div class="tab-pane fade show active" id="navs-pills-top-datos-articulo" role="tabpanel">
      <div class="row">

      <div class="col-12">
        <!-- Botones de acciones del artículo -->
        <div class="card card-action mb-6">
          <div class="card-header border-bottom align-items-center card-header-forms-buttons-action-lote">
            <div class="d-flex justify-content-end gap-3">
              <?php include 'acciones_articulo_buttons.php'; ?>
            </div>
          </div>
        </div>
      </div>

        <div class="col-xl-5 col-lg-5 col-md-5">
          <!-- Información del Artículo -->
          <div class="card mb-6">
            <div class="card-body">
              <small class="card-text text-uppercase text-body-secondary small">Información del Artículo</small>
              <ul class="list-unstyled my-3 py-1">
              <li class="d-flex align-items-center mb-4"><span class="fw-medium"><?php echo htmlspecialchars($articulo['descripcion']); ?></span>
                </li>
              </ul>
              <small class="card-text text-uppercase text-body-secondary small">Detalles del Artículo</small>
              <ul class="list-unstyled my-3 py-1">
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-store-2-line icon-24px"></i><span class="fw-medium mx-2">Origen:</span>
                  <span class="badge bg-label-<?php echo ($articulo['origen_articulo'] === 'central') ? 'primary' : 'info'; ?> rounded-pill"><?php echo htmlspecialchars($articulo['origen_articulo']); ?></span>
                </li>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-user-line icon-24px"></i><span class="fw-medium mx-2">Creado por:</span>
                  <span><?php echo htmlspecialchars(trim(($articulo['nombre_usuario'] ?? '') . ' ' . ($articulo['apellido_usuario'] ?? ''))); ?></span>
                </li>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-calendar-line icon-24px"></i><span class="fw-medium mx-2">Fecha Alta:</span>
                  <span><?php echo !empty($articulo['fecha_alta']) ? date('d/m/Y H:i', strtotime($articulo['fecha_alta'])) : 'N/A'; ?></span>
                </li>
                <?php if (!empty($articulo['fecha_en_venta']) && $articulo['fecha_en_venta'] != '0000-00-00 00:00:00'): ?>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-calendar-check-line icon-24px"></i><span class="fw-medium mx-2">Fecha en Venta:</span>
                  <span><?php echo date('d/m/Y H:i', strtotime($articulo['fecha_en_venta'])); ?></span>
                </li>
                <?php endif; ?>
                <?php if (!empty($articulo['fecha_vendido']) && $articulo['fecha_vendido'] != '0000-00-00 00:00:00' && $articulo['fecha_vendido'] != '0000-00-00'): ?>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-shopping-cart-line icon-24px"></i><span class="fw-medium mx-2">Fecha Vendido:</span>
                  <span><?php echo date('d/m/Y H:i', strtotime($articulo['fecha_vendido'])); ?></span>
                </li>
                <?php endif; ?>
                <?php if (!empty($venta_num_display) && $venta_id_rel > 0 && in_array(strtolower((string) ($articulo['estado'] ?? '')), ['vendido', 'vendido_web'], true)): ?>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-receipt-line icon-24px"></i><span class="fw-medium mx-2">Venta:</span>
                  <a target="_blank" rel="noopener" href="venta.php?id=<?php echo (int) $venta_id_rel; ?>"><?php echo htmlspecialchars($venta_num_display); ?></a>
                </li>
                <?php endif; ?>
                <?php if (!empty($articulo['fecha_enviado']) && $articulo['fecha_enviado'] != '0000-00-00 00:00:00'): ?>
                <li class="d-flex align-items-center mb-4">
                  <i class="icon-base ri ri-send-plane-line icon-24px"></i><span class="fw-medium mx-2">Fecha Enviado:</span>
                  <span><?php echo date('d/m/Y H:i', strtotime($articulo['fecha_enviado'])); ?></span>
                </li>
                <?php endif; ?>
                <?php if (!empty($articulo['observaciones'])): ?>
                <li class="d-flex align-items-start mb-4">
                  <i class="icon-base ri ri-chat-1-line icon-24px flex-shrink-0"></i>
                  <div class="mx-2">
                    <span class="fw-medium d-block mb-1">Observaciones</span>
                    <span class="text-body-secondary"><?php echo nl2br(htmlspecialchars($articulo['observaciones'])); ?></span>
                  </div>
                </li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
          <!--/ Información del Artículo -->
        </div>

        <div class="col-xl-7 col-lg-7 col-md-7">
          <div id="articulo-imagenes-boot" class="d-none" data-id-articulo="<?php echo (int) $articulo['id']; ?>" aria-hidden="true"></div>
          <div class="card card-action mt-0 mb-6">
            <div class="card-header mb-0 pt-2 pb-1 position-relative">
              <h5 class="text-uppercase text-body-secondary small card-title mb-0 me-auto p-2">Fotos del artículo</h5>
              <div class="position-absolute" style="right: 13px; top: 13px;">
                <?php if ($puede_acceder_editar): ?>
                <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light me-1" onclick="abrirModalFotoMovilArticulo()" title="Foto desde móvil (QR)">
                  <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                </button>
                <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="abrirModalSubirFotoArticulo()" title="Subir documento">
                  <span class="icon-base ri ri-upload-line icon-22px"></span>
                </button>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-body pt-0">
              <div class="row visor_documento_global" id="visor_documentos_articulo">
                <div class="col-12">
                  <div id="contenedor_imagenes_articulo" class="row g-3"></div>
                  <div id="sin_imagenes_articulo" class="text-center py-5" style="display: none;">
                    <i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>
                    <p class="text-body-secondary mb-0">No hay fotos subidas para este artículo</p>
                  </div>
                  <div id="loading_imagenes_articulo" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Cargando...</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal fade" id="modalSubirFotoArticulo" tabindex="-1" aria-labelledby="modalSubirFotoArticuloLabel" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalSubirFotoArticuloLabel">Subir foto</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <form id="formSubirFotoArticulo" enctype="multipart/form-data">
                        <div class="mb-3">
                          <label for="archivo_foto_articulo" class="form-label">Seleccionar foto</label>
                          <input type="file" class="form-control" id="archivo_foto_articulo" name="archivo_foto" accept=".jpg,.jpeg,.gif,.png,.pdf,.PDF,.JPG,.JPEG,.GIF,.PNG" required>
                          <div class="form-text">Formatos permitidos: JPG, JPEG, GIF, PNG, PDF (máximo 5MB). Las imágenes se redimensionan automáticamente a 800px de ancho máximo.</div>
                        </div>
                      </form>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                      <button type="button" class="btn btn-primary" onclick="subirFotoArticulo()">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        Subir
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal fade" id="modalAmpliarImagenArticulo" tabindex="-1" aria-labelledby="modalAmpliarImagenArticuloLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalAmpliarImagenArticuloLabel">Vista ampliada</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                      <img id="imagen_ampliada_articulo" src="" alt="Imagen ampliada" class="img-fluid">
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Modal QR artículo: inyectado por camera/js/camera-doc-panel.js (fetch camera/api/doc_panel.php) -->
            </div>
          </div>
        </div>

      </div>
    </div>
    <!-- /Tab Datos Artículo -->
    
    <!-- Tab Precios -->
    <div class="tab-pane fade" id="navs-pills-top-precios" role="tabpanel">
      <div class="card mt-0">
        <div class="card-body">
          <h5 class="card-title mb-4">Información de Precios</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-4">
                <h6 class="fw-medium mb-2">Precio Venta</h6>
                <p class="text-body-secondary"><?php echo number_format($articulo['precio'], 2, ',', '.'); ?> €</p>
              </div>
              <div class="mb-4">
                <h6 class="fw-medium mb-2">Precio Coste</h6>
                <p class="text-body-secondary"><?php echo number_format($articulo['precio_coste'], 2, ',', '.'); ?> €</p>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-4">
                <h6 class="fw-medium mb-2">Beneficio</h6>
                <p class="text-body-secondary">
                  <?php 
                  $beneficio = $articulo['precio'] - $articulo['precio_coste'];
                  $beneficio_class = ($beneficio > 0) ? 'success' : 'danger';
                  ?>
                  <span class="badge bg-label-<?php echo $beneficio_class; ?> rounded-pill">
                    <?php echo number_format($beneficio, 2, ',', '.'); ?> €
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- /Tab Precios -->

    <!-- Tab Trazabilidad -->
    <div class="tab-pane fade" id="navs-pills-top-trazabilidad" role="tabpanel">
      <div class="card mt-0">
        <div class="card-body">
          <h5 class="card-title mb-4">Trazabilidad del artículo</h5>
          <div class="table-responsive">
            <table class="table m-0" id="tabla_trazabilidad_articulo">
              <thead>
                <tr>
                  <th>Fecha acción</th>
                  <th>Usuario</th>
                  <th>Acción</th>
                  <th>Comentarios</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($trazabilidad_rows as $rstrazabilidadART) {
                    $fecha_accionart = !empty($rstrazabilidadART['fecha_accion'])
                        ? date('d-m-Y H:i:s', strtotime($rstrazabilidadART['fecha_accion']))
                        : '';
                    $comentarios_accion = isset($rstrazabilidadART['comentarios_accion']) ? $rstrazabilidadART['comentarios_accion'] : '';
                    $usuario_accion = isset($rstrazabilidadART['nombre_usuario_resuelto']) && $rstrazabilidadART['nombre_usuario_resuelto'] !== null
                        ? $rstrazabilidadART['nombre_usuario_resuelto']
                        : '';
                    $accion_trazabilidad = isset($rstrazabilidadART['accion_trazabilidad']) ? $rstrazabilidadART['accion_trazabilidad'] : '';
                    $id_articulo_venta = isset($rstrazabilidadART['id_articulo_venta']) ? (int) $rstrazabilidadART['id_articulo_venta'] : 0;
                    ob_start();
                    if ($accion_trazabilidad === 'creado') {
                        echo '<span class="badge bg-label-success" style="padding:5px; min-width:130px;">Creado</span>';
                    } elseif ($accion_trazabilidad === 'enviado_a_central') {
                        echo '<span class="badge bg-label-success" style="padding: 5px; min-width:130px;">Enviado a central</span>';
                    } elseif ($accion_trazabilidad === 'editado') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Editado</span>';
                    } elseif ($accion_trazabilidad === 'cambio_precio') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Cambio de precio</span>';
                    } elseif ($accion_trazabilidad === 'actualiza_web_vendido_tienda') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Actualiza vendido tienda a web</span>';
                    } elseif ($accion_trazabilidad === 'actualiza_web_retirado_tienda') {
                        echo '<span class="badge bg-label-success" style="padding: 5px; min-width:130px;">Actualiza web retirado tienda</span>';
                    } elseif ($accion_trazabilidad === 'etiqueta_impresa_envio') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Etiqueta impresa envío</span>';
                    } elseif ($accion_trazabilidad === 'etiqueta_reimpresa') {
                        echo '<span class="badge bg-label-success" style="padding: 5px; min-width:130px;">Etiqueta reimpresa</span>';
                    } elseif ($accion_trazabilidad === 'retirado') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Retirado</span>';
                    } elseif ($accion_trazabilidad === 'enventa') {
                        echo '<span class="badge bg-label-success" style="padding: 5px; min-width:130px;">Puesto en venta</span>';
                    } elseif ($accion_trazabilidad === 'mermado') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Mermado</span>';
                    } elseif ($accion_trazabilidad === 'publicadoweb') {
                        echo '<span class="badge bg-label-success" style="padding: 5px; min-width:130px;">Publicado en la web</span>';
                    } elseif ($accion_trazabilidad === 'enviado') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Enviado</span>';
                    } elseif ($accion_trazabilidad === 'actualiza_precio_web') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Precio actualizado en la web</span>';
                    } elseif ($accion_trazabilidad === 'vendidoweb') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Vendido en la web</span>';
                    } elseif ($accion_trazabilidad === 'devolucion_cancelada') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Devolución cancelada</span>';
                    } elseif ($accion_trazabilidad === 'devuelto') {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">Devuelto</span>';
                    } elseif ($accion_trazabilidad === 'vendido') {
                        echo '<span class="badge bg-label-success" style="padding: 5px; min-width:130px;">Vendido</span>';
                    } elseif ($accion_trazabilidad === 'traspaso_recibido') {
                        echo '<span class="badge bg-label-success" style="padding: 5px; min-width:130px;">Traspasado</span>';
                    } else {
                        echo '<span class="badge bg-label-warning" style="padding: 5px; min-width:130px;">No auditado</span>';
                    }
                    $badge_accion_html = ob_get_clean();
                ?>
                <tr>
                  <td class="text-center" style="width:150px;"><?php echo htmlspecialchars($fecha_accionart); ?></td>
                  <td class="text-center" style="width:150px;"><?php echo htmlspecialchars($usuario_accion); ?></td>
                  <td class="text-center" style="width:150px;"><?php echo $badge_accion_html; ?></td>
                  <td class="text-center" style="width:320px;">
                    <?php if ($accion_trazabilidad === 'pasado_stock' && $id_articulo_venta > 0) { ?>
                      Artículo generado <a target="_blank" rel="noopener" href="articulo.php?id=<?php echo $id_articulo_venta; ?>"> SKU: <?php echo $id_articulo_venta; ?></a><br>
                    <?php } ?>
                    <?php
                    // Links contextuales en trazabilidad (venta / devolución / traspaso)
                    $oculta_comentarios_accion = false;
                    if ($accion_trazabilidad === 'vendido') {
                        $oculta_comentarios_accion = true;
                        $venta_id_rel_tr = isset($articulo['last_id_venta']) ? (int) $articulo['last_id_venta'] : 0;
                        $venta_num_suc_tr = isset($articulo['id_venta_sucursal']) ? trim((string) $articulo['id_venta_sucursal']) : '';
                        $venta_num_display_tr = ($venta_num_suc_tr !== '' && $venta_num_suc_tr !== '0')
                            ? $venta_num_suc_tr
                            : (($venta_id_rel_tr > 0) ? (string) $venta_id_rel_tr : '');
                        if ($venta_id_rel_tr > 0 && $venta_num_display_tr !== '') {
                            echo 'Vendido en la venta Nº <a target="_blank" rel="noopener" href="venta.php?id=' . $venta_id_rel_tr . '">' . htmlspecialchars($venta_num_display_tr) . '</a><br>';
                        }
                    } elseif ($accion_trazabilidad === 'devuelto') {
                        $oculta_comentarios_accion = true;
                        $id_devolucion_tr = 0;
                        if (isset($rstrazabilidadART['id_devolucion'])) {
                            $id_devolucion_tr = (int) $rstrazabilidadART['id_devolucion'];
                        } elseif (isset($rstrazabilidadART['rel_id_devolucion'])) {
                            $id_devolucion_tr = (int) $rstrazabilidadART['rel_id_devolucion'];
                        } elseif ($comentarios_accion !== '') {
                            if (preg_match('/devoluci[oó]n[^0-9]*([0-9]+)/i', (string) $comentarios_accion, $m)) {
                                $id_devolucion_tr = (int) $m[1];
                            }
                        }
                        if ($id_devolucion_tr > 0) {
                            echo 'En la devolución Nº <a target="_blank" rel="noopener" href="devolucion.php?id=' . $id_devolucion_tr . '">' . (int) $id_devolucion_tr . '</a><br>';
                        }
                    } elseif ($accion_trazabilidad === 'traspaso_recibido') {
                        $oculta_comentarios_accion = true;
                        $id_traspaso_tr = 0;
                        if (isset($rstrazabilidadART['id_traspaso'])) {
                            $id_traspaso_tr = (int) $rstrazabilidadART['id_traspaso'];
                        } elseif (isset($rstrazabilidadART['rel_id_traspaso'])) {
                            $id_traspaso_tr = (int) $rstrazabilidadART['rel_id_traspaso'];
                        } elseif ($comentarios_accion !== '') {
                            if (preg_match('/traspaso[^0-9]*([0-9]+)/i', (string) $comentarios_accion, $m)) {
                                $id_traspaso_tr = (int) $m[1];
                            }
                        }
                        if ($id_traspaso_tr > 0) {
                            echo 'Recibido en el traspaso Nº <a target="_blank" rel="noopener" href="editar_traspaso.php?id=' . $id_traspaso_tr . '">' . (int) $id_traspaso_tr . '</a><br>';
                        }
                    }
                    ?>
                    <?php if (!$oculta_comentarios_accion) { echo $comentarios_accion; } ?>
                  </td>
                </tr>
                <?php } ?>
                <?php if (count($trazabilidad_rows) === 0) { ?>
                <tr>
                  <td colspan="4" class="text-center text-body-secondary">Sin registros de trazabilidad.</td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!-- /Tab Trazabilidad -->
  </div>
  <!-- /Tab content -->

  <?php
  $estado_art_modal = strtolower((string) ($articulo['estado'] ?? ''));
  if (in_array($estado_art_modal, ['vendido', 'vendido_web'], true)) {
      include __DIR__ . '/part_html_auth_devolucion.php';
  }
  ?>

  <?php
      } else {
          echo '<div class="alert alert-danger">Artículo no encontrado</div>';
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de artículo no válido</div>';
  }
  ?>
</div>
<!-- / Content -->
