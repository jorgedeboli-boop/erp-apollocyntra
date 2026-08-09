<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // Cargar datos del gasto
  $id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

  if ($id_gasto) {
      $conexion = conectar_bd();
      
      // Consulta para obtener datos del gasto con información relacionada
      $query_gasto = "
          SELECT 
              g.id_gasto,
              g.sucursal_gasto,
              g.proveedor_gasto,
              g.fecha_gasto,
              g.fecha_pago_gasto,
              g.fecha_factura_gasto,
              g.usuario_creacion_gasto,
              g.base_impobile,
              g.iva_total,
              g.total_gasto,
              g.forma_pago_gasto,
              g.estado_gasto,
              g.tipo_de_gasto,
              g.usuario_pago_gasto,
              g.empresa_gasto,
              g.descripcion_gasto,
              g.numero_factura_proveedor,
              g.irpf,
              g.creado_desde,
              g.tipo_iva,
              g.origen_gasto_variable,
              g.rel_id_gasto_fijo,
              g.gasto_tipo,
              e.nombre_empresa,
              s.nombre_sucursal,
              p.nombre_proveedor,
              tg.nombre_tipo_gasto,
              fp.nombre_forma_de_pago
          FROM gastos g
          LEFT JOIN empresas e ON g.empresa_gasto = e.id_empresa
          LEFT JOIN sucursal s ON g.sucursal_gasto = s.id_sucursal
          LEFT JOIN proveedores p ON g.proveedor_gasto = p.id_proveedor
          LEFT JOIN tipo_de_gasto tg ON g.tipo_de_gasto = tg.id_tipo_gasto
          LEFT JOIN formas_de_pago fp ON g.forma_pago_gasto = fp.id_forma_de_pago
          WHERE g.id_gasto = ?
      ";
      
      $stmt_gasto = mysqli_prepare($conexion, $query_gasto);
      mysqli_stmt_bind_param($stmt_gasto, 'i', $id_gasto);
      mysqli_stmt_execute($stmt_gasto);
      $result_gasto = mysqli_stmt_get_result($stmt_gasto);
      
      if ($result_gasto && mysqli_num_rows($result_gasto) > 0) {
          $gasto = mysqli_fetch_assoc($result_gasto);
          mysqli_stmt_close($stmt_gasto);
      } else {
          echo '<div class="alert alert-danger">Gasto no encontrado</div>';
          $gasto = null;
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de gasto no válido</div>';
      $gasto = null;
  }
  ?>

  <?php if ($gasto): ?>
    <script>
      window.idGasto = <?php echo (int) $gasto['id_gasto']; ?>;
      window.idEmpresaGasto = <?php echo (int) ($gasto['empresa_gasto'] ?? 0); ?>;
      window.idSucursalGasto = <?php echo (int) ($gasto['sucursal_gasto'] ?? 0); ?>;
    </script>
    <!-- Header -->
    <div class="row">
      <div class="col-12">
        <div class="card mb-6">
          <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
            <div class="flex-grow-1 mt-4 mt-sm-12">
              <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                <div class="user-profile-info">
                  <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='gastos.php'">
                    <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Gastos
                  </button>
                  <h4 class="mb-2">Gasto #<?php echo htmlspecialchars($gasto['id_gasto']); ?></h4>
                  <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-calendar-line me-2 icon-24px"></i><span class="fw-medium">Fecha: <?php echo htmlspecialchars($gasto['fecha_gasto']); ?></span>
                    </li>
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-money-euro-circle-fill me-2 icon-24px"></i><span class="fw-medium">Total: <?php echo number_format($gasto['total_gasto'], 2, ',', '.') . '€'; ?></span>
                    </li>
                    <li class="list-inline-item">
                      <i class="icon-base ri ri-building-line me-2 icon-24px"></i><span class="fw-medium">Empresa: <?php echo htmlspecialchars($gasto['nombre_empresa'] ?? 'N/A'); ?></span>
                    </li>
                  </ul>
                </div>
                <div class="d-flex gap-2">
                  <?php if ($puede_acceder_editar): ?>
                  <a href="editar_gasto.php?id=<?php echo $id_gasto; ?>" class="btn btn-primary waves-effect waves-light">
                    <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar Gasto
                  </a>
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
          <ul class="nav nav-pills flex-column flex-sm-row mb-6 row-gap-2" role="tablist">
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link waves-effect waves-light active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-informacion" aria-controls="navs-pills-top-informacion" aria-selected="true">
                <i class="icon-base ri ri-information-line icon-sm me-2"></i>Información
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-financiero" aria-controls="navs-pills-top-financiero" aria-selected="false" tabindex="-1">
                <i class="icon-base ri ri-money-euro-circle-fill icon-sm me-2"></i>Financiero
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-facturacion" aria-controls="navs-pills-top-facturacion" aria-selected="false" tabindex="-1">
                <i class="icon-base ri ri-file-text-line icon-sm me-2"></i>Facturación
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!--/ Navbar pills -->

    <!-- Tab Content -->
    <div class="tab-content">
      <!-- Tab Información -->
      <div class="tab-pane fade show active" id="navs-pills-top-informacion" role="tabpanel">
        <div class="row">
          <div class="col-xl-4 col-lg-5 col-md-5">
            <!-- Información Básica -->
            <div class="card mb-6">
              <div class="card-body">
                <small class="card-text text-uppercase text-body-secondary small">Información Básica</small>
                <ul class="list-unstyled my-3 py-1">
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-hashtag icon-24px"></i><span class="fw-medium mx-2">ID:</span> <span><?php echo htmlspecialchars($gasto['id_gasto']); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-calendar-line icon-24px"></i><span class="fw-medium mx-2">Fecha Gasto:</span> <span><?php echo htmlspecialchars($gasto['fecha_gasto']); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-time-line icon-24px"></i><span class="fw-medium mx-2">Estado:</span>
                    <span class="badge <?php
                      echo $gasto['estado_gasto'] == 'pagado' ? 'bg-label-success' :
                           ($gasto['estado_gasto'] == 'pendiente' ? 'bg-label-warning' : 'bg-label-danger');
                    ?>">
                      <?php echo ucfirst($gasto['estado_gasto']); ?>
                    </span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-building-line icon-24px"></i><span class="fw-medium mx-2">Empresa:</span> <span><?php echo htmlspecialchars($gasto['nombre_empresa'] ?? 'N/A'); ?></span>
                  </li>
                  <li class="d-flex align-items-start mb-4">
                    <i class="icon-base ri ri-file-text-line icon-24px"></i>
                    <span class="fw-medium mx-2">Descripción:</span>
                    <span class="text-body-secondary"><?php echo htmlspecialchars($gasto['descripcion_gasto'] ?? 'No hay descripción disponible'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-file-list-line icon-24px"></i><span class="fw-medium mx-2">Nº Factura:</span> <span><?php echo htmlspecialchars($gasto['numero_factura_proveedor'] ?? 'N/A'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-calendar-event-line icon-24px"></i><span class="fw-medium mx-2">Fecha Factura:</span> <span><?php echo htmlspecialchars($gasto['fecha_factura_gasto'] ?? 'N/A'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-login-circle-line icon-24px"></i><span class="fw-medium mx-2">Creado Desde:</span> <span><?php echo htmlspecialchars($gasto['creado_desde'] ?? 'N/A'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-2">
                    <i class="icon-base ri ri-price-tag-3-line icon-24px"></i><span class="fw-medium mx-2">Tipo de Gasto:</span> <span><?php echo htmlspecialchars($gasto['gasto_tipo'] ?? 'N/A'); ?></span>
                  </li>
                </ul>
              </div>
            </div>
            <!--/ Información Básica -->

            <!-- Información Relacionada -->
            <div class="card mb-6">
              <div class="card-body">
                <small class="card-text text-uppercase text-body-secondary small">Información Relacionada</small>
                <ul class="list-unstyled my-3 py-1">
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-building-2-line icon-24px"></i><span class="fw-medium mx-2">Sucursal:</span> <span><?php echo htmlspecialchars($gasto['nombre_sucursal'] ?? 'N/A'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-4">
                    <i class="icon-base ri ri-truck-line icon-24px"></i><span class="fw-medium mx-2">Proveedor:</span> <span><?php echo htmlspecialchars($gasto['nombre_proveedor'] ?? 'N/A'); ?></span>
                  </li>
                  <li class="d-flex align-items-center mb-2">
                    <i class="icon-base ri ri-price-tag-3-line icon-24px"></i><span class="fw-medium mx-2">Tipo:</span> <span><?php echo htmlspecialchars($gasto['nombre_tipo_gasto'] ?? 'N/A'); ?></span>
                  </li>
                </ul>
              </div>
            </div>
            <!--/ Información Relacionada -->
          </div>

          <div class="col-xl-8 col-lg-7 col-md-7">
            <!-- Documentación del gasto -->
            <div class="card card-action mb-6">
              <div class="card-header mb-0 pt-2 pb-1">
                <h5 class="text-uppercase text-body-secondary small card-title mb-0 me-auto p-2">Documentación del gasto</h5>
                <div class="position-absolute" style="right: 13px; top: 13px;">
                  <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="abrirModalFotoMovilGasto()">
                    <span class="icon-base ri ri-camera-ai-fill icon-22px"></span>
                  </button>
                  <button type="button" class="p-2 btn rounded-pill btn-icon btn-primary waves-effect waves-light" onclick="abrirModalSubirFotoGasto()">
                    <span class="icon-base ri ri-upload-line icon-22px"></span>
                  </button>
                </div>
              </div>
              <div class="card-body pt-0">
                <div class="row visor_documento_global" id="visor_documentos_gasto">
                  <div class="col-12">
                    <div id="contenedor_imagenes_gasto" class="row g-3"></div>
                    <div id="sin_imagenes_gasto" class="text-center py-5" style="display: none;">
                      <i class="icon-base ri ri-image-line icon-48px text-body-secondary mb-3"></i>
                      <p class="text-body-secondary mb-0">No hay documentos cargados para este gasto</p>
                    </div>
                    <div id="loading_imagenes_gasto" class="text-center py-4">
                      <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" id="modalSubirFotoGasto" tabindex="-1" aria-labelledby="modalSubirFotoGastoLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalSubirFotoGastoLabel">Subir Documento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <form id="formSubirFotoGasto" enctype="multipart/form-data">
                          <div class="mb-3">
                            <label for="archivo_foto_gasto" class="form-label">Seleccionar archivo</label>
                            <input type="file" class="form-control" id="archivo_foto_gasto" name="archivo_foto" accept=".jpg,.jpeg,.gif,.png,.pdf,.PDF,.JPG,.JPEG,.GIF,.PNG" required>
                            <div class="form-text">Formatos permitidos: JPG, JPEG, GIF, PNG, PDF (máximo 5MB). Las imágenes se redimensionan automáticamente a 800px de ancho máximo.</div>
                          </div>
                        </form>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="subirFotoGasto()">
                          <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                          Subir
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="modal fade" id="modalAmpliarImagenGasto" tabindex="-1" aria-labelledby="modalAmpliarImagenGastoLabel" aria-hidden="true">
                  <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalAmpliarImagenGastoLabel">Vista Ampliada</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <img id="imagen_ampliada_gasto" src="" alt="Imagen ampliada" class="img-fluid">
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--/ Documentación del gasto -->
          </div>
        </div>
      </div>
      <!--/ Tab Información -->

      <!-- Tab Financiero -->
      <div class="tab-pane fade" id="navs-pills-top-financiero" role="tabpanel">
        <div class="row">
          <div class="col-12">
            <!-- Información Financiera -->
            <div class="card mb-6">
              <div class="card-header">
                <h5 class="card-title">Información Financiera</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-4">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Base Imponible</h6>
                      <p class="text-body-secondary fs-5 fw-bold"><?php echo number_format($gasto['base_impobile'], 2, ',', '.') . '€'; ?></p>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">IVA Total</h6>
                      <p class="text-body-secondary fs-5 fw-bold"><?php echo number_format($gasto['iva_total'], 2, ',', '.') . '€'; ?></p>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Total Gasto</h6>
                      <p class="text-success fs-4 fw-bold"><?php echo number_format($gasto['total_gasto'], 2, ',', '.') . '€'; ?></p>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">IRPF</h6>
                      <p class="text-body-secondary"><?php echo number_format($gasto['irpf'], 2, ',', '.') . '€'; ?></p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Forma de Pago</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['nombre_forma_de_pago'] ?? 'N/A'); ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--/ Información Financiera -->

            <!-- Fechas de Pago -->
            <div class="card mb-6">
              <div class="card-header">
                <h5 class="card-title">Fechas de Pago</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Fecha de Pago</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['fecha_pago_gasto'] ?? 'N/A'); ?></p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Usuario de Pago</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['usuario_pago_gasto'] ?? 'N/A'); ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--/ Fechas de Pago -->
          </div>
        </div>
      </div>
      <!--/ Tab Financiero -->

      <!-- Tab Facturación -->
      <div class="tab-pane fade" id="navs-pills-top-facturacion" role="tabpanel">
        <div class="row">
          <div class="col-12">
            <!-- Detalles de Facturación -->
            <div class="card mb-6">
              <div class="card-header">
                <h5 class="card-title">Detalles de Facturación</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Número de Factura del Proveedor</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['numero_factura_proveedor'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Fecha de Factura</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['fecha_factura_gasto'] ?? 'N/A'); ?></p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Tipo de IVA</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['tipo_iva'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Origen del Gasto Variable</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['origen_gasto_variable'] ?? 'N/A'); ?></p>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">ID Gasto Fijo Relacionado</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['rel_id_gasto_fijo'] ?? 'N/A'); ?></p>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-4">
                      <h6 class="fw-medium mb-2">Usuario de Creación</h6>
                      <p class="text-body-secondary"><?php echo htmlspecialchars($gasto['usuario_creacion_gasto'] ?? 'N/A'); ?></p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--/ Detalles de Facturación -->
          </div>
        </div>
      </div>
      <!--/ Tab Facturación -->
    </div>
    <!--/ Tab Content -->

  <?php else: ?>
    <div class="alert alert-warning">
      <h4 class="alert-heading">Gasto no encontrado</h4>
      <p>El gasto que buscas no existe o no tienes permisos para verlo.</p>
      <hr>
      <a href="gastos.php" class="btn btn-primary">Volver a Gastos</a>
    </div>
  <?php endif; ?>
</div>