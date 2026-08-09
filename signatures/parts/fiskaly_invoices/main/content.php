<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // Cargar datos de la factura
  $id_factura = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  $factura = null;
  
  if ($id_factura) {
      // Conectar a la base de datos de Fiskaly producción (solo ENVIRONMENT === 'production')
      $conexion = get_mysqli_fiskalyapp_production();
      
      if ($conexion) {
          
          // Consulta para obtener todos los datos de la factura
          $query_factura = "
              SELECT 
                  id_factura,
                  id_sucursal,
                  numero_factura,
                  cliente_factura,
                  facturado_por,
                  estado_factura,
                  tipo_pago_factura,
                  total_factura,
                  fecha_factura,
                  hora_factura,
                  formato_factura,
                  rel_id_lote,
                  rel_id_renovacion,
                  rel_id_venta,
                  fecha_anulacion,
                  prefijo_factura,
                  rel_cliente_id,
                  nombre_cliente,
                  identificacion_fiscal_cliente,
                  direccion_cliente,
                  codigo_postal_cliente,
                  texto_facturas,
                  estado_cache,
                  invoice_id_fiskaly,
                  client_id_fiskaly,
                  tbai,
                  url_validacion,
                  imagen_codigo_qr,
                  tipo_factura,
                  registration_csv,
                  InvoiceState,
                  SignedInvoiceRegistrationState,
                  SignedInvoiceCancellationState,
                  ValidationErrors,
                  `ValidationErrors description`
              FROM facturas_fiskaly_cache
              WHERE id_factura = ?
          ";
          
          $stmt_factura = mysqli_prepare($conexion, $query_factura);
          if ($stmt_factura) {
              mysqli_stmt_bind_param($stmt_factura, 'i', $id_factura);
              mysqli_stmt_execute($stmt_factura);
              $result_factura = mysqli_stmt_get_result($stmt_factura);
              
              if ($result_factura && mysqli_num_rows($result_factura) > 0) {
                  $factura = mysqli_fetch_assoc($result_factura);
              }
              mysqli_stmt_close($stmt_factura);
          }
      }
  }
  
  if (!$factura) {
      echo '<div class="alert alert-danger">Factura no encontrada</div>';
  }
  ?>

  <?php if ($factura): ?>
    <!-- Header -->
    <div class="row">
      <div class="col-12">
        <div class="card mb-6">
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
            <div class="flex-grow-1 mt-4 mt-sm-12">
              <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                <div class="user-profile-info">
                  <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='fiskaly_invoices.php?id=<?php echo $factura['id_sucursal']; ?>'">
                    <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Facturas
                  </button>
                  <h4 class="mb-2">Factura <?php echo htmlspecialchars($factura['prefijo_factura'] . '-' . $factura['numero_factura']); ?></h4>
                  <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-calendar-line me-2 icon-24px"></i><span class="fw-medium">Fecha: <?php echo isset($factura['fecha_factura']) ? date('d/m/Y', strtotime($factura['fecha_factura'])) : 'N/A'; ?></span>
                    </li>
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-time-line me-2 icon-24px"></i><span class="fw-medium">Hora: <?php echo isset($factura['hora_factura']) ? htmlspecialchars($factura['hora_factura']) : 'N/A'; ?></span>
                    </li>
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-money-euro-circle-line me-2 icon-24px"></i><span class="fw-medium">Total: <?php echo isset($factura['total_factura']) ? number_format($factura['total_factura'], 2, ',', '.') . ' €' : 'N/A'; ?></span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!--/ Header -->

    <!-- Factura Content -->
    <div class="row">
      <div class="col-xl-4 col-lg-5 col-md-5">
        <!-- Información General -->
        <div class="card mb-6">
          <div class="card-body">
            <small class="card-text text-uppercase text-body-secondary small">Información General</small>
            <ul class="list-unstyled my-3 py-1">
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-file-list-line icon-24px"></i><span class="fw-medium mx-2">ID Factura:</span> <span><?php echo htmlspecialchars($factura['id_factura']); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-file-list-3-line icon-24px"></i><span class="fw-medium mx-2">Número Factura:</span> <span><?php echo htmlspecialchars($factura['prefijo_factura'] . '-' . $factura['numero_factura']); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-building-line icon-24px"></i><span class="fw-medium mx-2">ID Sucursal:</span> <span><?php echo htmlspecialchars($factura['id_sucursal']); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-file-text-line icon-24px"></i><span class="fw-medium mx-2">Formato:</span> <span class="badge bg-label-info rounded-pill"><?php echo htmlspecialchars($factura['formato_factura']); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-file-edit-line icon-24px"></i><span class="fw-medium mx-2">Tipo Factura:</span> <span class="badge bg-label-primary rounded-pill"><?php echo htmlspecialchars($factura['tipo_factura']); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-checkbox-circle-line icon-24px"></i><span class="fw-medium mx-2">Estado Cache:</span> 
                <span>
                  <?php 
                  $estado_cache = $factura['estado_cache'];
                  $badge_class = 'bg-label-secondary';
                  if ($estado_cache === 'aceptada') $badge_class = 'bg-label-success';
                  elseif ($estado_cache === 'rechazada') $badge_class = 'bg-label-danger';
                  elseif ($estado_cache === 'enviada') $badge_class = 'bg-label-warning';
                  ?>
                  <span class="badge <?php echo $badge_class; ?> rounded-pill"><?php echo htmlspecialchars($estado_cache); ?></span>
                </span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-file-list-2-line icon-24px"></i><span class="fw-medium mx-2">Estado Factura:</span> 
                <span>
                  <?php 
                  $estado_factura = $factura['estado_factura'];
                  $badge_class_factura = 'bg-label-secondary';
                  if ($estado_factura === 'pagada') $badge_class_factura = 'bg-label-success';
                  elseif ($estado_factura === 'anulada') $badge_class_factura = 'bg-label-danger';
                  ?>
                  <span class="badge <?php echo $badge_class_factura; ?> rounded-pill"><?php echo htmlspecialchars($estado_factura); ?></span>
                </span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-bank-card-line icon-24px"></i><span class="fw-medium mx-2">Tipo Pago:</span> <span><?php echo htmlspecialchars($factura['tipo_pago_factura']); ?></span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-money-euro-circle-line icon-24px"></i><span class="fw-medium mx-2">Total:</span> <span><?php echo number_format($factura['total_factura'], 2, ',', '.') . ' €'; ?></span>
              </li>
            </ul>
          </div>
        </div>
        <!--/ Información General -->

        <!-- Estados Fiskaly -->
        <div class="card mb-6">
          <div class="card-body">
            <small class="card-text text-uppercase text-body-secondary small">Estados Fiskaly</small>
            <ul class="list-unstyled my-3 py-1">
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-file-check-line icon-24px"></i><span class="fw-medium mx-2">Invoice State:</span> 
                <span>
                  <?php 
                  $invoice_state = $factura['InvoiceState'] !== 'false' ? $factura['InvoiceState'] : '-';
                  $badge_class_state = 'bg-label-secondary';
                  if ($invoice_state === 'ISSUED') $badge_class_state = 'bg-label-success';
                  elseif ($invoice_state === 'CANCELLED') $badge_class_state = 'bg-label-danger';
                  elseif ($invoice_state !== '-') $badge_class_state = 'bg-label-warning';
                  ?>
                  <span class="badge <?php echo $badge_class_state; ?> rounded-pill"><?php echo htmlspecialchars($invoice_state); ?></span>
                </span>
              </li>
              <li class="d-flex align-items-center mb-4">
                <i class="icon-base ri ri-file-check-line icon-24px"></i><span class="fw-medium mx-2">Registration State:</span> 
                <span>
                  <?php 
                  $reg_state = $factura['SignedInvoiceRegistrationState'] !== 'false' ? $factura['SignedInvoiceRegistrationState'] : '-';
                  $badge_class_reg = 'bg-label-secondary';
                  if ($reg_state === 'REGISTERED' || $reg_state === 'STORED') $badge_class_reg = 'bg-label-success';
                  elseif ($reg_state === 'INVALID' || $reg_state === 'REQUIRES_CORRECTION') $badge_class_reg = 'bg-label-danger';
                  elseif ($reg_state !== '-') $badge_class_reg = 'bg-label-warning';
                  ?>
                  <span class="badge <?php echo $badge_class_reg; ?> rounded-pill"><?php echo htmlspecialchars($reg_state); ?></span>
                </span>
              </li>
              <li class="d-flex align-items-center mb-2">
                <i class="icon-base ri ri-file-check-line icon-24px"></i><span class="fw-medium mx-2">Cancellation State:</span> 
                <span>
                  <?php 
                  $cancel_state = $factura['SignedInvoiceCancellationState'] !== 'false' ? $factura['SignedInvoiceCancellationState'] : '-';
                  $badge_class_cancel = 'bg-label-secondary';
                  if ($cancel_state === 'NOT_CANCELLED' || $cancel_state === 'STORED') $badge_class_cancel = 'bg-label-success';
                  elseif ($cancel_state === 'CANCELLED') $badge_class_cancel = 'bg-label-info';
                  elseif ($cancel_state === 'INVALID') $badge_class_cancel = 'bg-label-danger';
                  elseif ($cancel_state !== '-') $badge_class_cancel = 'bg-label-warning';
                  ?>
                  <span class="badge <?php echo $badge_class_cancel; ?> rounded-pill"><?php echo htmlspecialchars($cancel_state); ?></span>
                </span>
              </li>
            </ul>
          </div>
        </div>
        <!--/ Estados Fiskaly -->
      </div>

      <div class="col-xl-8 col-lg-7 col-md-7">
        <!-- Cliente -->
        <div class="card card-action mb-6">
          <div class="card-header align-items-center">
            <h5 class="card-action-title mb-0">
              <i class="icon-base ri ri-user-line icon-24px text-body me-4"></i>Cliente
            </h5>
          </div>
          <div class="card-body pt-5">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Nombre Cliente</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['nombre_cliente']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">ID Cliente</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['rel_cliente_id']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Cliente Factura</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['cliente_factura']); ?></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Identificación Fiscal</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['identificacion_fiscal_cliente']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Dirección</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['direccion_cliente']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Código Postal</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['codigo_postal_cliente']); ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!--/ Cliente -->

        <!-- Información Fiskaly -->
        <div class="card card-action mb-6">
          <div class="card-header align-items-center">
            <h5 class="card-action-title mb-0">
              <i class="icon-base ri ri-server-line icon-24px text-body me-4"></i>Información Fiskaly
            </h5>
          </div>
          <div class="card-body pt-5">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Invoice ID Fiskaly</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['invoice_id_fiskaly']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Client ID Fiskaly</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['client_id_fiskaly']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">TBAI</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['tbai']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Registration CSV</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['registration_csv']); ?></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">URL Validación</h6>
                  <?php if (!empty($factura['url_validacion'])): ?>
                    <p class="text-body-secondary"><a href="<?php echo htmlspecialchars($factura['url_validacion']); ?>" target="_blank" class="text-primary"><i class="icon-base ri ri-external-link-line me-1"></i>Ver URL</a></p>
                  <?php else: ?>
                    <p class="text-body-secondary">N/A</p>
                  <?php endif; ?>
                </div>
                <?php if (!empty($factura['imagen_codigo_qr'])): ?>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Código QR</h6>
                  <p class="text-body-secondary">
                    <img src="data:image/png;base64,<?php echo htmlspecialchars($factura['imagen_codigo_qr']); ?>" alt="Código QR" style="max-width: 200px;">
                  </p>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <!--/ Información Fiskaly -->

        <!-- Información Adicional -->
        <div class="card card-action mb-6">
          <div class="card-header align-items-center">
            <h5 class="card-action-title mb-0">
              <i class="icon-base ri ri-information-line icon-24px text-body me-4"></i>Información Adicional
            </h5>
          </div>
          <div class="card-body pt-5">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Facturado Por</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['facturado_por']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">ID Lote</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['rel_id_lote']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">ID Renovación</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['rel_id_renovacion']); ?></p>
                </div>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">ID Venta</h6>
                  <p class="text-body-secondary"><?php echo htmlspecialchars($factura['rel_id_venta']); ?></p>
                </div>
              </div>
              <div class="col-md-6">
                <?php if (!empty($factura['fecha_anulacion'])): ?>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Fecha Anulación</h6>
                  <p class="text-body-secondary"><?php echo date('d/m/Y H:i', strtotime($factura['fecha_anulacion'])); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($factura['ValidationErrors'])): ?>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Validation Errors</h6>
                  <p class="text-body-secondary text-danger"><?php echo htmlspecialchars($factura['ValidationErrors']); ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($factura['ValidationErrors description'])): ?>
                <div class="mb-4">
                  <h6 class="fw-medium mb-2">Validation Errors Description</h6>
                  <p class="text-body-secondary text-danger"><?php echo htmlspecialchars($factura['ValidationErrors description']); ?></p>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php if (!empty($factura['texto_facturas'])): ?>
            <div class="row mt-4">
              <div class="col-12">
                <h6 class="fw-medium mb-2">Texto Facturas</h6>
                <p class="text-body-secondary"><?php echo nl2br(htmlspecialchars($factura['texto_facturas'])); ?></p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <!--/ Información Adicional -->
      </div>
    </div>
    <!--/ Factura Content -->
  <?php endif; ?>
</div>
<!-- / Content -->