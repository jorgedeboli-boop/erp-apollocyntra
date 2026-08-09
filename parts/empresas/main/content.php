<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // Cargar datos de la empresa
  $id_empresa = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  // Variable global para el ID de la empresa
  if ($id_empresa) {
      echo "<script>window.idEmpresa = {$id_empresa};</script>";
  }
  
  if ($id_empresa) {
      $conexion = conectar_bd();
      $texto_region_itp = 'N/A';

      $empresa = function_exists('cargar_empresa_por_id')
          ? cargar_empresa_por_id($conexion, $id_empresa)
          : null;
      if (!$empresa) {
          $result_fallback = mysqli_query($conexion, 'SELECT * FROM empresas WHERE id_empresa = ' . (int) $id_empresa . ' LIMIT 1');
          if ($result_fallback && mysqli_num_rows($result_fallback) > 0) {
              $empresa = mysqli_fetch_assoc($result_fallback);
          }
      }
      if ($empresa) {
          if (function_exists('cargar_texto_region_itp_empresa')) {
              $region_itp_texto = cargar_texto_region_itp_empresa(
                  $conexion,
                  $id_empresa,
                  isset($empresa['rel_id_provincia']) ? (int) $empresa['rel_id_provincia'] : 0
              );
              if ($region_itp_texto !== '') {
                  $texto_region_itp = $region_itp_texto;
              }
          }
      } else {
          echo '<div class="alert alert-danger">Empresa no encontrada</div>';
          $empresa = null;
      }

      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de empresa no válido</div>';
      $empresa = null;
  }

  $empresa_doc = (isset($empresa) && is_array($empresa)) ? $empresa : array();
  $factura_digital_val = isset($empresa_doc['factura_digital']) ? $empresa_doc['factura_digital'] : 'false';
  if ($factura_digital_val === true || $factura_digital_val === 1 || $factura_digital_val === '1') {
      $factura_digital_val = 'true';
  } elseif ($factura_digital_val !== 'true') {
      $factura_digital_val = 'false';
  }
  $fecha_inicio_fd = '';
  if (!empty($empresa_doc['fecha_inicio_factura_digital'])) {
      $raw_f = (string) $empresa_doc['fecha_inicio_factura_digital'];
      if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $raw_f, $m_f) && $m_f[1] !== '0000-00-00') {
          $fecha_inicio_fd = $m_f[1];
      }
  }
  $region_regimen_val = isset($empresa_doc['region_regimen']) ? $empresa_doc['region_regimen'] : 'false';
  if (!isset($texto_region_itp)) {
      $texto_region_itp = 'N/A';
  }
  ?>

              <!-- Header -->
              <div class="row">
                <div class="col-12">
                  <div class="card mb-6">
                    
                    <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
                      
                      <div class="flex-grow-1 mt-4 mt-sm-12">
                        <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                          <div class="user-profile-info">
                          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='empresas.php'">
                            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Empresas
                          </button>
                            <h4 class="mb-2">Empresa <?php echo isset($empresa['nombre_empresa']) ? htmlspecialchars($empresa['nombre_empresa']) : 'Empresa no encontrada'; ?></h4>
                            <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                              <li class="list-inline-item">
                                <i class="icon-base ri ri-building-line me-2 icon-24px"></i><span class="fw-medium">CIF: <?php echo isset($empresa['cif_empresa']) ? htmlspecialchars($empresa['cif_empresa']) : 'N/A'; ?></span>
                              </li>
                              <li class="list-inline-item">
                                <i class="icon-base ri ri-map-pin-line me-2 icon-24px"></i><span class="fw-medium">Ubicación: <?php echo isset($empresa['poblacion_empresa']) ? htmlspecialchars($empresa['poblacion_empresa']) : 'N/A'; ?>, <?php echo isset($empresa['provincia_empresa']) ? htmlspecialchars($empresa['provincia_empresa']) : 'N/A'; ?></span>
                              </li>
                            </ul>
                          </div>
                          <div class="d-flex gap-2">
                            <?php if ($puede_acceder_editar): ?>
                            <a href="editar_empresa.php?id=<?php echo $id_empresa; ?>" class="btn btn-primary waves-effect waves-light">
                              <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar Empresa
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
                    <ul class="nav nav-pills mb-4" role="tablist">
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-perfil" aria-controls="navs-pills-top-perfil" aria-selected="true">
                          <i class="icon-base ri ri-user-3-line icon-sm me-2"></i>Perfil
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-textos" aria-controls="navs-pills-top-textos" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-file-text-line icon-sm me-2"></i>Textos para documentos
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-facturacion" aria-controls="navs-pills-top-facturacion" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-bill-line icon-sm me-2"></i>Sistema de facturación
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-cuentas-bancarias" aria-controls="navs-pills-top-cuentas-bancarias" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-bank-fill icon-sm me-2"></i>Cuentas bancarias
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-tarjetas-banco" aria-controls="navs-pills-top-tarjetas-banco" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-bank-card-line icon-sm me-2"></i>Tarjetas banco
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-configuracion" aria-controls="navs-pills-top-configuracion" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-image-edit-fill icon-sm me-2"></i>Logotipo
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-sucursales" aria-controls="navs-pills-top-sucursales" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-building-fill icon-sm me-2"></i>Sucursales
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <!--/ Navbar pills -->

              <!-- Tab Content -->
              <div class="tab-content">
                <!-- Tab Perfil -->
                <div class="tab-pane fade show active" id="navs-pills-top-perfil" role="tabpanel">
                  <!-- Empresa Profile Content -->
              <div class="row">
                <div class="col-xl-4 col-lg-5 col-md-5">
                  <!-- About Empresa -->
                  <div class="card mb-6">
                    <div class="card-body">
                      <small class="card-text text-uppercase text-body-secondary small">Información General</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-building-line icon-24px"></i><span class="fw-medium mx-2">Nombre:</span> <span><?php echo isset($empresa['nombre_empresa']) ? htmlspecialchars($empresa['nombre_empresa']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-id-card-line icon-24px"></i><span class="fw-medium mx-2">CIF:</span> <span><?php echo isset($empresa['cif_empresa']) ? htmlspecialchars($empresa['cif_empresa']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-calendar-line icon-24px"></i><span class="fw-medium mx-2">Fecha Creación:</span> <span><?php echo isset($empresa['fecha_creacion_empresa']) ? htmlspecialchars($empresa['fecha_creacion_empresa']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-currency-line icon-24px"></i><span class="fw-medium mx-2">Moneda:</span> <span><?php echo isset($empresa['moneda_empresa']) ? htmlspecialchars($empresa['moneda_empresa']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-global-line icon-24px"></i><span class="fw-medium mx-2">Web:</span> <span><?php echo isset($empresa['webempresa']) ? htmlspecialchars($empresa['webempresa']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-map-2-line icon-24px"></i><span class="fw-medium mx-2">Región ITP:</span> <span><?php echo htmlspecialchars($texto_region_itp); ?></span>
                        </li>
                      </ul>
                    </div>
                  </div>
                  <!--/ About Empresa -->

                  <!-- Contact Info -->
                  <div class="card mb-6">
                    <div class="card-body">
                      <small class="card-text text-uppercase text-body-secondary small">Información de Contacto</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-mail-line icon-24px"></i><span class="fw-medium mx-2">Email:</span> <span><?php echo isset($empresa['email_empresa']) ? htmlspecialchars($empresa['email_empresa']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-phone-line icon-24px"></i><span class="fw-medium mx-2">Teléfono:</span> <span><?php echo isset($empresa['telefono_empresa']) ? htmlspecialchars($empresa['telefono_empresa']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                          <i class="icon-base ri ri-bank-card-line icon-24px"></i><span class="fw-medium mx-2">Cuenta Corriente:</span> <span><?php echo isset($empresa['cuenta_corriente_empresa']) ? htmlspecialchars($empresa['cuenta_corriente_empresa']) : 'N/A'; ?></span>
                        </li>
                      </ul>
                    </div>
                  </div>
                  <!--/ Contact Info -->
                </div>
                <!--/ About Empresa -->

                <div class="col-xl-8 col-lg-7 col-md-7">
                                     <!-- Empresa Activity -->
                   <div class="card card-action mb-6">
                     <div class="card-header align-items-center">
                       <h5 class="card-action-title mb-0">
                         <i class="icon-base ri ri-map-pin-line icon-24px text-body me-4"></i>Dirección
                       </h5>
                     </div>
                                         <div class="card-body pt-5">
                       <div class="row">
                         <div class="col-md-6">
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Dirección</h6>
                             <p class="text-body-secondary"><?php echo isset($empresa['direccion_empresa']) ? htmlspecialchars($empresa['direccion_empresa']) : 'N/A'; ?></p>
                           </div>
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Población</h6>
                             <p class="text-body-secondary"><?php echo isset($empresa['poblacion_empresa']) ? htmlspecialchars($empresa['poblacion_empresa']) : 'N/A'; ?></p>
                           </div>
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Provincia</h6>
                             <p class="text-body-secondary"><?php echo isset($empresa['provincia_empresa']) ? htmlspecialchars($empresa['provincia_empresa']) : 'N/A'; ?></p>
                           </div>
                         </div>
                         <div class="col-md-6">
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">País</h6>
                             <p class="text-body-secondary"><?php echo isset($empresa['pais_empresa']) ? htmlspecialchars($empresa['pais_empresa']) : 'N/A'; ?></p>
                           </div>
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Código Postal</h6>
                             <p class="text-body-secondary"><?php echo isset($empresa['codigo_postal_empresa']) ? htmlspecialchars($empresa['codigo_postal_empresa']) : 'N/A'; ?></p>
                           </div>
                         </div>
                       </div>
                     </div>
                  </div>
                  <!--/ Empresa Activity -->
                </div>
              </div>
              <!--/ Empresa Profile Content -->
                </div>
                <!--/ Tab Perfil -->

                <!-- Tab Textos -->
                <div class="tab-pane fade" id="navs-pills-top-textos" role="tabpanel">
                  <div class="row g-4">
                    <?php
                    $tarjetas_textos = array(
                        array('campo' => 'texto_contrato_compra', 'titulo' => 'Contratos compra', 'icon' => 'ri-shopping-cart-line'),
                        array('campo' => 'texto_contrato_empeno', 'titulo' => 'Texto contrato empeño', 'icon' => 'ri-file-paper-line'),
                        array('campo' => 'texto_facturas_oro_inversion', 'titulo' => 'Texto facturas ORO INVERSIÓN', 'icon' => 'ri-coins-line'),
                        array('campo' => 'texto_facturas', 'titulo' => 'Texto facturas REBU', 'icon' => 'ri-file-list-3-line'),
                        array('campo' => 'texto_facturas_regular', 'titulo' => 'Texto facturas régimen general', 'icon' => 'ri-article-line'),
                    );
                    foreach ($tarjetas_textos as $tt) {
                        $campo_t = $tt['campo'];
                        $raw_t = isset($empresa_doc[$campo_t]) ? (string) $empresa_doc[$campo_t] : '';
                        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                            $prev_t = $raw_t === '' ? '' : (mb_strlen($raw_t) > 200 ? mb_substr($raw_t, 0, 200) . '…' : $raw_t);
                        } else {
                            $prev_t = $raw_t === '' ? '' : (strlen($raw_t) > 200 ? substr($raw_t, 0, 200) . '…' : $raw_t);
                        }
                        $placeholder_prev = $prev_t === '' ? 'Sin texto configurado. Pulse para editar.' : $prev_t;
                        ?>
                    <div class="col-12 col-md-6 col-xl-4">
                      <div class="card card-action h-100 shadow-none border cursor-pointer textos-doc-card" role="button" tabindex="0"
                           data-texto-campo="<?php echo htmlspecialchars($campo_t); ?>"
                           data-texto-titulo="<?php echo htmlspecialchars($tt['titulo']); ?>"
                           onclick="abrirModalEditarTextoDocumento(this)"
                           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();abrirModalEditarTextoDocumento(this);}">
                        <div class="card-header border-bottom py-3 d-flex align-items-center gap-2">
                          <i class="icon-base ri <?php echo htmlspecialchars($tt['icon']); ?> icon-24px text-body me-1"></i>
                          <h5 class="card-title mb-0"><?php echo htmlspecialchars($tt['titulo']); ?></h5>
                        </div>
                        <div class="card-body">
                          <p class="mb-0 text-body-secondary small textos-doc-preview"><?php echo nl2br(htmlspecialchars($placeholder_prev)); ?></p>
                        </div>
                        <div class="card-footer text-body-secondary small border-top-0 pt-0">
                          <i class="icon-base ri ri-edit-box-line icon-16px me-1"></i>Editar en ventana emergente
                        </div>
                      </div>
                    </div>
                        <?php
                    }
                    ?>
                  </div>
                </div>
                <!--/ Tab Textos -->

                <!-- Tab Sistema de facturación -->
                <div class="tab-pane fade" id="navs-pills-top-facturacion" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-action mb-6">
                        <div class="card-header border-bottom align-items-center">
                          <h5 class="card-action-title mb-1">
                            <i class="icon-base ri ri-bill-line icon-24px text-body me-2"></i>
                            Factura digital (Verifactu / TicketBai / Régimen general)
                          </h5>
                          <small class="text-muted d-block">Régimen guardado en base de datos: <span class="fw-medium text-heading"><?php echo htmlspecialchars((string) $region_regimen_val); ?></span></small>
                        </div>
                        <div class="card-body pt-5">
                          <form id="formFacturacionEmpresa" onsubmit="return false;">
                            <input type="hidden" name="id_empresa" value="<?php echo (int) $id_empresa; ?>" />
                            <div class="row">
                              <div class="col-12 col-lg-6 mb-4">
                                <label class="form-label">Activar factura digital</label>
                                <div class="d-flex flex-wrap gap-3">
                                  <div class="form-check custom-option custom-option-basic">
                                    <label class="form-check-label custom-option-content" for="factura_digital_no_main">
                                      <input class="form-check-input" type="radio" name="factura_digital" id="factura_digital_no_main" value="false" <?php echo $factura_digital_val === 'false' ? 'checked' : ''; ?> />
                                      <span class="custom-option-header">
                                        <span class="h6 mb-0">No</span>
                                      </span>
                                    </label>
                                  </div>
                                  <div class="form-check custom-option custom-option-basic">
                                    <label class="form-check-label custom-option-content" for="factura_digital_si_main">
                                      <input class="form-check-input" type="radio" name="factura_digital" id="factura_digital_si_main" value="true" <?php echo $factura_digital_val === 'true' ? 'checked' : ''; ?> />
                                      <span class="custom-option-header">
                                        <span class="h6 mb-0">Sí</span>
                                      </span>
                                    </label>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <div class="row g-4 mb-2">
                              <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                  <input type="date" class="form-control" name="fecha_inicio_factura_digital" id="fecha_inicio_factura_digital_main" value="<?php echo htmlspecialchars($fecha_inicio_fd); ?>" placeholder=" " />
                                  <label for="fecha_inicio_factura_digital_main">Fecha inicio factura digital</label>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                  <select class="form-select" name="region_regimen" id="region_regimen_main" aria-label="Región factura digital">
                                    <option value="false" <?php echo $region_regimen_val === 'false' ? 'selected' : ''; ?>>Ninguna</option>
                                    <option value="General" <?php echo $region_regimen_val === 'General' ? 'selected' : ''; ?>>General</option>
                                    <option value="Verifactu" <?php echo $region_regimen_val === 'Verifactu' ? 'selected' : ''; ?>>Verifactu</option>
                                    <option value="TicketBAIBizkaia" <?php echo $region_regimen_val === 'TicketBAIBizkaia' ? 'selected' : ''; ?>>TicketBAIBizkaia</option>
                                    <option value="TicketBAIAlava" <?php echo $region_regimen_val === 'TicketBAIAlava' ? 'selected' : ''; ?>>TicketBAIAlava</option>
                                    <option value="TicketBAIGipuzkua" <?php echo $region_regimen_val === 'TicketBAIGipuzkua' ? 'selected' : ''; ?>>TicketBAIGipuzkua</option>
                                  </select>
                                  <label for="region_regimen_main">Región factura digital</label>
                                </div>
                              </div>
                            </div>
                            <div class="mt-4 pt-2">
                              <button type="button" class="btn btn-primary waves-effect waves-light" onclick="guardarFacturacionEmpresa()">
                                <i class="icon-base ri ri-save-3-line icon-16px me-2"></i>Guardar cambios
                              </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Sistema de facturación -->

                <!-- Tab Cuentas Bancarias -->
                <div class="tab-pane fade" id="navs-pills-top-cuentas-bancarias" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-action mb-6">
                        <div class="card-header border-bottom align-items-center">
                          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 w-100">
                            <h5 class="card-action-title mb-0">
                              <i class="icon-base ri ri-bank-fill icon-24px text-body me-2"></i>
                              Cuentas bancarias de la empresa
                            </h5>
                            <button type="button" onclick="abrirModalCrearCuenta()" class="btn btn-primary waves-effect waves-light">
                              <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear nueva cuenta
                            </button>
                          </div>
                        </div>
                        <div class="card-body pt-5">
                          <div class="table-responsive text-nowrap">
                            <table class="table datatables-cuentas-bancarias align-middle">
                              <thead>
                                <tr>
                                  <th>NÚMERO DE CUENTA</th>
                                  <th>BANCO</th>
                                  <th>POR DEFECTO</th>
                                  <th>FECHA CREACIÓN</th>
                                  <th>ACCIONES</th>
                                </tr>
                              </thead>
                              <tbody class="table-border-bottom-0">
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Cuentas Bancarias -->

                <!-- Tab Tarjetas Banco -->
                <div class="tab-pane fade" id="navs-pills-top-tarjetas-banco" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-action mb-6">
                        <div class="card-header border-bottom align-items-center">
                          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 w-100">
                            <h5 class="card-action-title mb-0">
                              <i class="icon-base ri ri-bank-card-line icon-24px text-body me-2"></i>
                              Tarjetas banco de la empresa
                            </h5>
                            <button type="button" onclick="abrirModalCrearTarjeta()" class="btn btn-primary waves-effect waves-light">
                              <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear nueva tarjeta
                            </button>
                          </div>
                        </div>
                        <div class="card-body pt-5">
                          <div class="table-responsive text-nowrap">
                            <table class="table datatables-tarjetas-banco align-middle">
                              <thead>
                                <tr>
                                  <th>NÚMERO DE TARJETA</th>
                                  <th>BANCO</th>
                                  <th>POR DEFECTO</th>
                                  <th>FECHA CREACIÓN</th>
                                  <th>ACCIONES</th>
                                </tr>
                              </thead>
                              <tbody class="table-border-bottom-0">
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Tarjetas Banco -->

                <!-- Tab Sucursales -->
                <div class="tab-pane fade" id="navs-pills-top-sucursales" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <div class="card card-action mb-6">
                        <div class="card-header border-bottom align-items-center">
                          <h5 class="card-action-title mb-0">
                            <i class="icon-base ri ri-building-fill icon-24px text-body me-2"></i>
                            Sucursales de la empresa
                          </h5>
                        </div>
                        <div class="card-body pt-5">
                          <div class="table-responsive text-nowrap">
                            <table class="table datatables-sucursales-empresa">
                              <thead>
                                <tr>
                                  <th>ID</th>
                                  <th>NOMBRE SUCURSAL</th>
                                  <th>NOMBRE CORTO</th>
                                  <th>POBLACIÓN</th>
                                  <th>PROVINCIA</th>
                                  <th>TELÉFONO</th>
                                  <th>ESTADO</th>
                                  <th>ACCIONES</th>
                                </tr>
                              </thead>
                              <tbody class="table-border-bottom-0">
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Sucursales -->

                <!-- Tab Logotipo -->
                <div class="tab-pane fade" id="navs-pills-top-configuracion" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <!-- Logotipo de la Empresa -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">
                            <i class="icon-base ri ri-image-line icon-24px text-body me-2"></i>Logotipo de la Empresa
                          </h5>
                        </div>
                        <div class="card-body pt-5">
                          <div class="row">
                            <div class="col-12">
                              <h6 class="fw-medium mb-2">Logotipo (JPG 600x120px)
                                <button type="button" onclick="abrirModalSubirLogotipo()" style="float: right;" class="btn btn-primary btn-xs waves-effect waves-light">
                                  <i class="icon-base ri ri-upload-line icon-16px me-2"></i>Subir Logotipo
                                </button>
                              </h6>
                              <div class="border rounded p-4 text-muted contenedor_sello_logotipo" style="position: relative;" id="contenedor_logotipo">
                                <!-- El logotipo se cargará aquí dinámicamente con JavaScript -->
                                <div id="contenedor_logotipo_actual">
                                  <p class="mb-0">Cargando logotipo...</p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Configuración -->
              </div>
              <!--/ Tab Content -->

              <!-- Modal: editar texto de documento -->
              <div class="modal fade" id="modalEditarTextoDocumento" tabindex="-1" aria-labelledby="modalEditarTextoDocumentoTitulo" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title" id="modalEditarTextoDocumentoTitulo">Editar texto</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" id="modal_texto_documento_campo" value="" />
                      <div class="form-floating form-floating-outline">
                        <textarea class="form-control" id="modal_texto_documento_contenido" placeholder=" " style="min-height: 280px"></textarea>
                        <label for="modal_texto_documento_contenido">Contenido</label>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                      <button type="button" class="btn btn-primary" onclick="guardarTextoDocumentoEmpresa()">
                        <i class="icon-base ri ri-save-3-line me-2"></i>Guardar
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <script>
              window.textosEmpresaDoc = <?php
              $payload_td = array(
                  'texto_contrato_compra' => isset($empresa_doc['texto_contrato_compra']) ? (string) $empresa_doc['texto_contrato_compra'] : '',
                  'texto_contrato_empeno' => isset($empresa_doc['texto_contrato_empeno']) ? (string) $empresa_doc['texto_contrato_empeno'] : '',
                  'texto_facturas_oro_inversion' => isset($empresa_doc['texto_facturas_oro_inversion']) ? (string) $empresa_doc['texto_facturas_oro_inversion'] : '',
                  'texto_facturas' => isset($empresa_doc['texto_facturas']) ? (string) $empresa_doc['texto_facturas'] : '',
                  'texto_facturas_regular' => isset($empresa_doc['texto_facturas_regular']) ? (string) $empresa_doc['texto_facturas_regular'] : '',
              );
              echo json_encode($payload_td, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
              ?>;
              </script>
</div>
<!-- / Content -->