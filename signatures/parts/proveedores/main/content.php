<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // Cargar datos del proveedor
  $id_proveedor = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  // Variable global para el ID del proveedor
  if ($id_proveedor) {
      echo "<script>window.idProveedor = {$id_proveedor};</script>";
  }
  
  if ($id_proveedor) {
      $conexion = conectar_bd();
      
      // Consulta para obtener datos del proveedor
      $query_proveedor = "
          SELECT 
              id_proveedor,
              nombre_proveedor,
              fecha_creacion_proveedor,
              cif_proveedor,
              direccion_proveedor,
              poblacion_proveedor,
              provincia_proveedor,
              pais_proveedor,
              telefono_proveedor,
              moneda_proveedor,
              codigo_postal_proveedor,
              email_proveedor,
              creado_por,
              forma_pago_proveedor,
              fundicion,
              fundicion_multi_kilates
          FROM proveedores
          WHERE id_proveedor = ?
      ";
      
      $stmt_proveedor = mysqli_prepare($conexion, $query_proveedor);
      mysqli_stmt_bind_param($stmt_proveedor, 'i', $id_proveedor);
      mysqli_stmt_execute($stmt_proveedor);
      $result_proveedor = mysqli_stmt_get_result($stmt_proveedor);
      
      if ($result_proveedor && mysqli_num_rows($result_proveedor) > 0) {
          $proveedor = mysqli_fetch_assoc($result_proveedor);
          mysqli_stmt_close($stmt_proveedor);
      } else {
          echo '<div class="alert alert-danger">Proveedor no encontrado</div>';
          $proveedor = null;
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de proveedor no válido</div>';
      $proveedor = null;
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
                          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='proveedores.php'">
                            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Proveedores
                          </button>
                            <h4 class="mb-2">Proveedor <?php echo isset($proveedor['nombre_proveedor']) ? htmlspecialchars($proveedor['nombre_proveedor']) : 'Proveedor no encontrado'; ?></h4>
                            <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                              <li class="list-inline-item">
                                <i class="icon-base ri ri-building-line me-2 icon-24px"></i><span class="fw-medium">CIF: <?php echo isset($proveedor['cif_proveedor']) ? htmlspecialchars($proveedor['cif_proveedor']) : 'N/A'; ?></span>
                              </li>
                              <li class="list-inline-item">
                                <i class="icon-base ri ri-map-pin-line me-2 icon-24px"></i><span class="fw-medium">Ubicación: <?php echo isset($proveedor['poblacion_proveedor']) ? htmlspecialchars($proveedor['poblacion_proveedor']) : 'N/A'; ?>, <?php echo isset($proveedor['provincia_proveedor']) ? htmlspecialchars($proveedor['provincia_proveedor']) : 'N/A'; ?></span>
                              </li>
                            </ul>
                          </div>
                          <div class="d-flex gap-2">
                            <?php if ($puede_acceder_editar): ?>
                            <a href="editar_proveedor.php?id=<?php echo $id_proveedor; ?>" class="btn btn-primary waves-effect waves-light">
                              <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar Proveedor
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
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-pago" aria-controls="navs-pills-top-pago" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-bank-card-line icon-sm me-2"></i>Forma de Pago
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-fundicion" aria-controls="navs-pills-top-fundicion" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-tools-line icon-sm me-2"></i>Fundición
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
                  <!-- Proveedor Profile Content -->
              <div class="row">
                <div class="col-xl-4 col-lg-5 col-md-5">
                  <!-- About Proveedor -->
                  <div class="card mb-6">
                    <div class="card-body">
                      <small class="card-text text-uppercase text-body-secondary small">Información General</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-building-line icon-24px"></i><span class="fw-medium mx-2">Nombre:</span> <span><?php echo isset($proveedor['nombre_proveedor']) ? htmlspecialchars($proveedor['nombre_proveedor']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-id-card-line icon-24px"></i><span class="fw-medium mx-2">CIF:</span> <span><?php echo isset($proveedor['cif_proveedor']) ? htmlspecialchars($proveedor['cif_proveedor']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-calendar-line icon-24px"></i><span class="fw-medium mx-2">Fecha Creación:</span> <span><?php echo isset($proveedor['fecha_creacion_proveedor']) ? htmlspecialchars($proveedor['fecha_creacion_proveedor']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-currency-line icon-24px"></i><span class="fw-medium mx-2">Moneda:</span> <span><?php echo isset($proveedor['moneda_proveedor']) ? htmlspecialchars($proveedor['moneda_proveedor']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                          <i class="icon-base ri ri-bank-card-line icon-24px"></i><span class="fw-medium mx-2">Forma de Pago:</span> <span><?php echo isset($proveedor['forma_pago_proveedor']) ? htmlspecialchars($proveedor['forma_pago_proveedor']) : 'N/A'; ?></span>
                        </li>
                      </ul>
                    </div>
                  </div>
                  <!--/ About Proveedor -->

                  <!-- Contact Info -->
                  <div class="card mb-6">
                    <div class="card-body">
                      <small class="card-text text-uppercase text-body-secondary small">Información de Contacto</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-mail-line icon-24px"></i><span class="fw-medium mx-2">Email:</span> <span><?php echo isset($proveedor['email_proveedor']) ? htmlspecialchars($proveedor['email_proveedor']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                          <i class="icon-base ri ri-phone-line icon-24px"></i><span class="fw-medium mx-2">Teléfono:</span> <span><?php echo isset($proveedor['telefono_proveedor']) ? htmlspecialchars($proveedor['telefono_proveedor']) : 'N/A'; ?></span>
                        </li>
                      </ul>
                    </div>
                  </div>
                  <!--/ Contact Info -->
                </div>
                <!--/ About Proveedor -->

                <div class="col-xl-8 col-lg-7 col-md-7">
                                     <!-- Proveedor Activity -->
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
                             <p class="text-body-secondary"><?php echo isset($proveedor['direccion_proveedor']) ? htmlspecialchars($proveedor['direccion_proveedor']) : 'N/A'; ?></p>
                           </div>
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Población</h6>
                             <p class="text-body-secondary"><?php echo isset($proveedor['poblacion_proveedor']) ? htmlspecialchars($proveedor['poblacion_proveedor']) : 'N/A'; ?></p>
                           </div>
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Provincia</h6>
                             <p class="text-body-secondary"><?php echo isset($proveedor['provincia_proveedor']) ? htmlspecialchars($proveedor['provincia_proveedor']) : 'N/A'; ?></p>
                           </div>
                         </div>
                         <div class="col-md-6">
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">País</h6>
                             <p class="text-body-secondary"><?php echo isset($proveedor['pais_proveedor']) ? htmlspecialchars($proveedor['pais_proveedor']) : 'N/A'; ?></p>
                           </div>
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Código Postal</h6>
                             <p class="text-body-secondary"><?php echo isset($proveedor['codigo_postal_proveedor']) ? htmlspecialchars($proveedor['codigo_postal_proveedor']) : 'N/A'; ?></p>
                           </div>
                         </div>
                       </div>
                     </div>
                  </div>
                  <!--/ Proveedor Activity -->
                </div>
              </div>
              <!--/ Proveedor Profile Content -->
                </div>
                <!--/ Tab Perfil -->

                <!-- Tab Forma de Pago -->
                <div class="tab-pane fade" id="navs-pills-top-pago" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <!-- Información de Pago -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Información de Pago del Proveedor</h5>
                        </div>
                        <div class="card-body">
                          <div class="row">
                            <div class="col-md-6">
                              <div class="mb-4">
                                <h6 class="fw-medium mb-2">Forma de Pago</h6>
                                <p class="text-body-secondary"><?php echo isset($proveedor['forma_pago_proveedor']) ? ucfirst(htmlspecialchars($proveedor['forma_pago_proveedor'])) : 'N/A'; ?></p>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="mb-4">
                                <h6 class="fw-medium mb-2">Moneda</h6>
                                <p class="text-body-secondary"><?php echo isset($proveedor['moneda_proveedor']) ? htmlspecialchars($proveedor['moneda_proveedor']) : 'N/A'; ?></p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Forma de Pago -->

                <!-- Tab Fundición -->
                <div class="tab-pane fade" id="navs-pills-top-fundicion" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <!-- Información de Fundición -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Servicios de Fundición</h5>
                        </div>
                        <div class="card-body">
                          <div class="row">
                            <div class="col-md-6">
                              <div class="mb-4">
                                <h6 class="fw-medium mb-2">Servicio de Fundición</h6>
                                <p class="text-body-secondary">
                                  <?php 
                                  if (isset($proveedor['fundicion'])) {
                                      echo $proveedor['fundicion'] === 'true' ? 
                                          '<span class="badge bg-success">Sí</span>' : 
                                          '<span class="badge bg-secondary">No</span>';
                                  } else {
                                      echo 'N/A';
                                  }
                                  ?>
                                </p>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="mb-4">
                                <h6 class="fw-medium mb-2">Fundición Multi-Kilates</h6>
                                <p class="text-body-secondary">
                                  <?php 
                                  if (isset($proveedor['fundicion_multi_kilates'])) {
                                      echo $proveedor['fundicion_multi_kilates'] === 'true' ? 
                                          '<span class="badge bg-success">Sí</span>' : 
                                          '<span class="badge bg-secondary">No</span>';
                                  } else {
                                      echo 'N/A';
                                  }
                                  ?>
                                </p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Fundición -->
              </div>
              <!--/ Tab Content -->
</div>
<!-- / Content -->