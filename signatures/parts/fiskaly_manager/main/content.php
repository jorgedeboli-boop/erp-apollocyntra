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
              sello_empresa,
              tipo_api
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
      
      // Consultar datos de Fiskaly desde la base de datos externa

      $mysqli_fiskalyapp = obtenerConexionFiskalyPorEmpresa($id_empresa);

      $datos_fiskaly = null;
      if (isset($mysqli_fiskalyapp)) {
          $query_fiskaly = "
              SELECT 
                  clave_api,
                  secret_clave_api,
                  id_organization_fisklaly
              FROM datos_fiskaly_empresas
              WHERE rel_empresa = ?
          ";
          
          $stmt_fiskaly = mysqli_prepare($mysqli_fiskalyapp, $query_fiskaly);
          if ($stmt_fiskaly) {
              mysqli_stmt_bind_param($stmt_fiskaly, 'i', $id_empresa);
              mysqli_stmt_execute($stmt_fiskaly);
              $result_fiskaly = mysqli_stmt_get_result($stmt_fiskaly);
              
              if ($result_fiskaly && mysqli_num_rows($result_fiskaly) > 0) {
                  $datos_fiskaly = mysqli_fetch_assoc($result_fiskaly);
              }
              mysqli_stmt_close($stmt_fiskaly);
          }
      }

      $url_api_fiskaly = obtenerUrlApiFiskalyPorEmpresa($id_empresa);
      if ($url_api_fiskaly) {
        echo "<script>window.urlApiFiskaly = '{$url_api_fiskaly}';</script>";
      } else {
        echo '<div class="alert alert-danger">URL de la API de Fiskaly no encontrada</div>';
        $url_api_fiskaly = null;
      }

      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de empresa no válido</div>';
      $empresa = null;
      $datos_fiskaly = null;
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
                            <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='fiskaly_manager.php'">
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
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-sucursales" aria-controls="navs-pills-top-configuracion" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-building-fill icon-sm me-2"></i>Sucursales
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-datos-fiskaly" aria-controls="navs-pills-top-datos-fiskaly" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-server-line icon-sm me-2"></i>Datos Fiskaly
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-invoices-fiskaly" aria-controls="navs-pills-top-invoices-fiskaly" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-file-list-line icon-sm me-2"></i>Invoices Fiskaly
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

                    <!-- Empresa direccion -->
                    <div class="col-xl-8 col-lg-7 col-md-7">
                    
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
    

                    </div>
                    <!--/ Empresa direccion -->

                  </div>
                  <!--/ Empresa Profile Content -->
                </div>
                <!--/ Tab Perfil -->

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

                <!-- Tab Datos Fiskaly -->
                <div class="tab-pane fade" id="navs-pills-top-datos-fiskaly" role="tabpanel">

                  <div class="row">

                    <div class="col-12">

                      <!-- Datos Fiskaly -->
                      <div class="card mb-6">

                        <div class="card-header d-flex justify-content-between align-items-center">
                          <h5 class="card-title mb-0">
                            <i class="icon-base ri ri-server-line icon-24px text-body me-2"></i>Datos Fiskaly
                          </h5>
                          <div class="d-flex align-items-center gap-2">
                            <div class="badge bg-label-primary rounded-pill lh-xs badget-estados" id="tipo_api_fiskaly"><?php echo isset($empresa['tipo_api']) && !empty($empresa['tipo_api']) ? htmlspecialchars($empresa['tipo_api']) : 'N/A'; ?></div>
                            <?php 
                            $tipo_api_actual = isset($empresa['tipo_api']) ? $empresa['tipo_api'] : 'test';
                            $nuevo_tipo = ($tipo_api_actual === 'test') ? 'produccion' : 'test';
                            $texto_boton = ($tipo_api_actual === 'test') ? 'Pasar a producción' : 'Pasar a test';
                            ?>
                            <button type="button" class="btn btn-sm btn-label-secondary" onclick="cambiarTipoApi(<?php echo $id_empresa; ?>, '<?php echo $nuevo_tipo; ?>')">
                              <?php echo htmlspecialchars($texto_boton); ?>
                            </button>
                          </div>
                        </div>

                        <div class="card-body pt-5">

                          <?php if ($datos_fiskaly): ?>
                            <div class="row">

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Clave API</h6>
                                  <p class="text-body-secondary" id="fiskaly_clave_api" data-clave-api="<?php echo isset($datos_fiskaly['clave_api']) && !empty($datos_fiskaly['clave_api']) ? htmlspecialchars($datos_fiskaly['clave_api']) : ''; ?>"><?php echo isset($datos_fiskaly['clave_api']) && !empty($datos_fiskaly['clave_api']) ? htmlspecialchars($datos_fiskaly['clave_api']) : 'N/A'; ?></p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Secret Clave API</h6>
                                  <p class="text-body-secondary" id="fiskaly_secret_clave_api" data-secret-clave-api="<?php echo isset($datos_fiskaly['secret_clave_api']) && !empty($datos_fiskaly['secret_clave_api']) ? htmlspecialchars($datos_fiskaly['secret_clave_api']) : ''; ?>"><?php echo isset($datos_fiskaly['secret_clave_api']) && !empty($datos_fiskaly['secret_clave_api']) ? htmlspecialchars($datos_fiskaly['secret_clave_api']) : 'N/A'; ?></p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">ID Organization Fiskaly</h6>
                                  <p class="text-body-secondary" id="fiskaly_id_organization_fisklaly" data-id-organization-fisklaly="<?php echo isset($datos_fiskaly['id_organization_fisklaly']) && !empty($datos_fiskaly['id_organization_fisklaly']) ? htmlspecialchars($datos_fiskaly['id_organization_fisklaly']) : ''; ?>"><?php echo isset($datos_fiskaly['id_organization_fisklaly']) && !empty($datos_fiskaly['id_organization_fisklaly']) ? htmlspecialchars($datos_fiskaly['id_organization_fisklaly']) : 'N/A'; ?></p>
                                </div>
                              </div>

                            </div>

                            <div class="row">
                              <div class="col-12">
                                <div class="mb-4">
                                  <button type="button" onclick="ejecutarAutenticarFiskaly()" class="btn btn-primary waves-effect waves-light" id="btn_autenticar_fiskaly">
                                    <i class="icon-base ri ri-shield-check-line icon-16px me-2"></i>autenticarFiskaly
                                  </button>
                                </div>
                              </div>
                            </div>

                            <div class="row mt-5" id="resultado_autenticacion_fiskaly" style="display: none;">
                              <h5 class="fw-medium mb-2">Autenticación Fiskaly</h5>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Environment</h6>
                                  <p class="text-body-secondary" id="fiskaly_environment">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Organization ID</h6>
                                  <p class="text-body-secondary" id="fiskaly_organization_id">-</p>
                                </div>
                              </div>
                            </div>

                            <div class="row mt-5" id="resultado_contribuyente_fiskaly" style="display: none;">
                              <input type="hidden" id="fiskaly_contribuyente_existe" value="0">
                              <h5 class="fw-medium mb-2">Contribuyente Fiskaly</h5>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Contribuyente</h6>
                                  <p class="text-body-secondary" id="fiskaly_contribuyente">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">NIF</h6>
                                  <p class="text-body-secondary" id="fiskaly_nif">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Territorio</h6>
                                  <p class="text-body-secondary" id="fiskaly_territory">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Estado</h6>
                                  <p class="text-body-secondary" id="fiskaly_state">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Municipio</h6>
                                  <p class="text-body-secondary" id="fiskaly_municipality">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Ciudad</h6>
                                  <p class="text-body-secondary" id="fiskaly_city">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Calle</h6>
                                  <p class="text-body-secondary" id="fiskaly_street">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Código Postal</h6>
                                  <p class="text-body-secondary" id="fiskaly_postal_code">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Número</h6>
                                  <p class="text-body-secondary" id="fiskaly_number">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Código País</h6>
                                  <p class="text-body-secondary" id="fiskaly_country_code">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Email</h6>
                                  <p class="text-body-secondary" id="fiskaly_email">-</p>
                                </div>
                              </div>
                              
                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Registrado</h6>
                                  <p class="text-body-secondary" id="fiskaly_registered">-</p>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Tipo</h6>
                                  <p class="text-body-secondary" id="fiskaly_type">-</p>
                                </div>
                              </div>

                              <div class="col-12" id="btn_actualizar_contribuyente_fiskaly">
                                <div class="mb-4">
                                  <button type="button" onclick="abrirModalActualizarContribuyente()" class="btn btn-primary waves-effect waves-light" id="btn_actualizar_contribuyente">
                                    <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Actualizar contribuyente
                                  </button>
                                </div>
                              </div>

                            </div>

                            <div class="row" id="sin_contribuyente_fiskaly" style="display: none;">
                              <div class="col-12">
                                <div class="text-center py-4">
                                  <i class="icon-base ri ri-information-line icon-48px text-body-secondary mb-2"></i>
                                    <p class="text-muted mb-3">No hay contribuyente</p>
                                  <button type="button" onclick="abrirModalCrearContribuyente()" class="btn btn-primary waves-effect waves-light" id="btn_crear_contribuyente">
                                    <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear contribuyente
                                  </button>
                                </div>
                              </div>
                            </div>
                            
                            <div class="row" id="sin_acuerdos" style="display: none;">
                              <div class="col-12">
                                <div class="text-center py-4">
                                  <i class="icon-base ri ri-information-line icon-48px text-body-secondary mb-2"></i>
                                  <p class="text-muted mb-3">No existe acuerdo</p>
                                  <button type="button" onclick="abrirModalCrearAcuerdo()" class="btn btn-primary waves-effect waves-light" id="btn_crear_acuerdo">
                                    <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear acuerdo
                                  </button>
                                </div>
                              </div>
                            </div>

                            <div class="row" id="resultado_acuerdos" style="display: none;">
                              
                              <h5 class="fw-medium mb-2">Acuerdos</h5>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Document URL</h6>
                                  <p class="text-body-secondary" id="document_url_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Nombre</h6>
                                  <p class="text-body-secondary" id="full_name_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">NIF</h6>
                                  <p class="text-body-secondary" id="tax_number_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Municipio</h6>
                                  <p class="text-body-secondary" id="municipality_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Ciudad</h6>
                                  <p class="text-body-secondary" id="city_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Calle</h6>
                                  <p class="text-body-secondary" id="street_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Código Postal</h6>
                                  <p class="text-body-secondary" id="postal_code_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Número</h6>
                                  <p class="text-body-secondary" id="number_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Código País</h6>
                                  <p class="text-body-secondary" id="country_code_agreement">-</p>
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="mb-4">
                                  <h6 class="fw-medium mb-2">Creado el</h6>
                                  <p class="text-body-secondary" id="created_at_agreement">-</p>
                                </div>
                              </div>

                            </div>

                            <div class="row" id="sin_dispositivos_firmantes_fiskaly" style="display: none;">
                              <div class="col-12">
                                <div class="text-center py-4">
                                  <i class="icon-base ri ri-information-line icon-48px text-body-secondary mb-2"></i>
                                  <p class="text-muted mb-3">No hay dispositivos firmantes</p>
                                </div>
                              </div>
                            </div>

                            <div class="row mt-5" id="resultado_dispositivos_firmantes" style="display: none;">

                              <div class="col-12">

                                  <div class="card">

                                    <div class="card-header d-flex justify-content-between align-items-center">
                                      <h5 class="card-title mb-0">Dispositivos Firmantes</h5>
                                      <button type="button" onclick="abrirModalCrearDispositivoFirmante('<?php echo generarUUIDv4(); ?>')" class="btn btn-primary waves-effect waves-light">
                                        <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear dispositivo firmante
                                      </button>
                                    </div>

                                    <div class="table-responsive text-nowrap">

                                      <table class="table th_small">
                                        <thead>
                                          <tr class="text-nowrap">
                                            <th>ID</th>
                                            <th>Estado</th>
                                            <th>Número de Serie</th>
                                          </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0" id="dispositivos_firmantes">
                                          <!-- Los datos se cargarán dinámicamente -->
                                        </tbody>
                                      </table>

                                    </div> <!--/ Table Responsive -->

                                  </div> <!--/ Card -->

                              </div> <!--/ Col 12 -->

                            </div> <!--/ Row -->

                            <div class="row" id="sin_clients_fiskaly" style="display: none;">
                              <div class="col-12">
                                <div class="text-center py-4">
                                  <i class="icon-base ri ri-information-line icon-48px text-body-secondary mb-2"></i>
                                  <p class="text-muted mb-3">No hay clientes</p>
                                </div>
                              </div>
                            </div>

                            <div class="row mt-5" id="resultado_clients" style="display: none;">

                              <div class="col-12">

                                  <div class="card">

                                    <div class="card-header d-flex justify-content-between align-items-center">
                                      <h5 class="card-title mb-0">Clientes</h5>
                                      <button type="button" id="btn_crear_client" onclick="abrirModalCrearClient('<?php echo generarUUIDv4(); ?>')" class="btn btn-primary waves-effect waves-light">
                                        <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear cliente
                                      </button>
                                    </div>

                                    <div class="table-responsive text-nowrap">

                                      <table class="table th_small">
                                        <thead>
                                          <tr class="text-nowrap">
                                            <th>ID</th>
                                            <th>Estado</th>
                                            <th>ID Sucursal</th>
                                            <th>Nombre Sucursal</th>
                                            <th>ID Empresa</th>
                                            <th>ACCIONES</th>
                                          </tr>
                                        </thead>
                                        <tbody class="table-border-bottom-0" id="clients">
                                          <!-- Los datos se cargarán dinámicamente -->
                                        </tbody>
                                      </table>

                                    </div> <!--/ Table Responsive -->

                                  </div> <!--/ Card -->

                              </div> <!--/ Col 12 -->

                            </div> <!--/ Row -->
                           
                          <?php else: ?>
                            <div class="text-center py-4">
                              <i class="icon-base ri ri-information-line icon-48px text-body-secondary mb-2"></i>
                              <p class="text-muted mb-3">Sin datos de fiskaly</p>
                              <button type="button" onclick="abrirModalCrearFiskaly()" class="btn btn-primary waves-effect waves-light">
                                <i class="icon-base ri ri-add-line icon-16px me-2"></i>Crear organizacion
                              </button>
                            </div>
                          <?php endif; ?>

                        </div> <!--/ Card Body -->

                      </div> <!--/ Card -->

                    </div>

                  </div>

                </div>
                <!--/ Tab Datos Fiskaly -->

                <!-- Tab Invoices Fiskaly -->
                <div class="tab-pane fade" id="navs-pills-top-invoices-fiskaly" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <!-- Contenido de Invoices Fiskaly -->
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Invoices Fiskaly</h5>
                        </div>
                        <div class="card-body pt-5">
                          <div class="card-datatable table-responsive">
                            <table class="datatables-invoices-fiskaly table border-top">
                              <thead>
                                <tr>
                                  <th>ID INVOICE</th>
                                  <th>CLIENT</th>
                                  <th>TBAI</th>
                                  <th>URL</th>
                                  <th>ISSUED AT</th>
                                  <th>SIGNER</th>
                                  <th>ESTADO</th>
                                  <th>CANCELLATION</th>
                                  <th>REGISTRATION</th>
                                  <th>REGISTRATION CSV</th>
                                  <th>CODE</th>
                                  <th>DESCRIPTION</th>
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
                <!--/ Tab Invoices Fiskaly -->
                
              </div>
              <!--/ Tab Content -->
</div>
<!-- / Content -->