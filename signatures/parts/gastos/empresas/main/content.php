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
      
      // Consulta para obtener datos de la empresa
      $query_empresa = "
          SELECT 
              id_empresa,
              nombre_empresa,
              fecha_creacion_empresa,
              cif_empresa,
              direccion_empresa,
              poblacion_empresa,
              provincia_empresa,
              pais_empresa,
              telefono_empresa,
              moneda_empresa,
              logotipo_empresa,
              codigo_postal_empresa,
              email_empresa,
              creada_por,
              cuenta_corriente_empresa,
              texto_facturas,
              texto_contrato_empeno,
              texto_contrato_compra,
              webempresa,
              sello_empresa
          FROM empresas
          WHERE id_empresa = ?
      ";
      
      $stmt_empresa = mysqli_prepare($conexion, $query_empresa);
      mysqli_stmt_bind_param($stmt_empresa, 'i', $id_empresa);
      mysqli_stmt_execute($stmt_empresa);
      $result_empresa = mysqli_stmt_get_result($stmt_empresa);
      
      if ($result_empresa && mysqli_num_rows($result_empresa) > 0) {
          $empresa = mysqli_fetch_assoc($result_empresa);
          mysqli_stmt_close($stmt_empresa);
      } else {
          echo '<div class="alert alert-danger">Empresa no encontrada</div>';
          $empresa = null;
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de empresa no válido</div>';
      $empresa = null;
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
                            <a href="editar_empresa.php?id=<?php echo $id_empresa; ?>" class="btn btn-primary waves-effect waves-light">
                              <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar Empresa
                            </a>
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
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-sucursales" aria-controls="navs-pills-top-configuracion" aria-selected="false" tabindex="-1">
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
                        <li class="d-flex align-items-center mb-2">
                          <i class="icon-base ri ri-global-line icon-24px"></i><span class="fw-medium mx-2">Web:</span> <span><?php echo isset($empresa['webempresa']) ? htmlspecialchars($empresa['webempresa']) : 'N/A'; ?></span>
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
                  <div class="row">
                    <div class="col-12">
                      <!-- Texto Facturas -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Texto para Facturas</h5>
                        </div>
                        <div class="card-body">
                          <span class="fw-medium text-heading"><?php echo isset($empresa['texto_facturas']) ? htmlspecialchars($empresa['texto_facturas']) : 'No hay texto configurado para facturas'; ?></span>
                        </div>
                      </div>

                      <!-- Texto Contrato Empeño -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Texto para Contratos de Empeño</h5>
                        </div>
                        <div class="card-body">
                          <span class="fw-medium text-heading"><?php echo isset($empresa['texto_contrato_empeno']) ? htmlspecialchars($empresa['texto_contrato_empeno']) : 'No hay texto configurado para contratos de empeño'; ?></span>
                        </div>
                      </div>

                      <!-- Texto Contrato Compra -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Texto para Contratos de Compra</h5>
                        </div>
                        <div class="card-body">
                          <span class="fw-medium text-heading"><?php echo isset($empresa['texto_contrato_compra']) ? htmlspecialchars($empresa['texto_contrato_compra']) : 'No hay texto configurado para contratos de compra'; ?></span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Textos -->

                <!-- Tab Cuentas Bancarias -->
                <div class="tab-pane fade" id="navs-pills-top-cuentas-bancarias" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <!-- Cuentas Bancarias -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Cuentas Bancarias de la Empresa</h5>
                          <button type="button" onclick="abrirModalCrearCuenta()" class="btn btn-primary waves-effect waves-light">
                            <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear Nueva Cuenta
                          </button>
                        </div>
                        <div class="card-body pt-5">
                          <div class="card-datatable table-responsive">
                            <table class="datatables-cuentas-bancarias table border-top">
                              <thead>
                                <tr>
                                  <th>NÚMERO DE CUENTA</th>
                                  <th>BANCO</th>
                                  <th>POR DEFECTO</th>
                                  <th>FECHA CREACIÓN</th>
                                  <th>ACCIONES</th>
                                </tr>
                              </thead>
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
                      <!-- Tarjetas Banco -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Tarjetas Banco de la Empresa</h5>
                          <button type="button" onclick="abrirModalCrearTarjeta()" class="btn btn-primary waves-effect waves-light">
                            <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear Nueva Tarjeta
                          </button>
                        </div>
                        <div class="card-body pt-5">
                          <div class="card-datatable table-responsive">
                            <table class="datatables-tarjetas-banco table border-top">
                              <thead>
                                <tr>
                                  <th>NÚMERO DE TARJETA</th>
                                  <th>BANCO</th>
                                  <th>POR DEFECTO</th>
                                  <th>FECHA CREACIÓN</th>
                                  <th>ACCIONES</th>
                                </tr>
                              </thead>
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
                      <!-- Lista de Sucursales -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Sucursales de la Empresa</h5>
                        </div>
                        <div class="card-body pt-5">
                          <div class="card-datatable table-responsive">
                            <table class="datatables-sucursales-empresa table border-top">
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
</div>
<!-- / Content -->