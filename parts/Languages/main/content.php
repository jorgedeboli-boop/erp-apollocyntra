<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // Cargar datos del language
  $id_lang = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  // Variable global para el ID del language
  if ($id_lang) {
      echo "<script>window.idLanguage = {$id_lang};</script>";
  }
  
  if ($id_lang) {
      $conexion = conectar_bd();
      
      // Consulta para obtener datos del language
      $query_language = "
          SELECT 
              l.id_lang,
              l.cod_LP,
              l.description,
              l.stateLang,
              l.rel_id_country,
              c.name_spanish as pais
          FROM Languages l
          LEFT JOIN countrys c ON l.rel_id_country = c.id_country
          WHERE l.id_lang = ?
      ";
      
      $stmt_language = mysqli_prepare($conexion, $query_language);
      mysqli_stmt_bind_param($stmt_language, 'i', $id_lang);
      mysqli_stmt_execute($stmt_language);
      $result_language = mysqli_stmt_get_result($stmt_language);
      
      if ($result_language && mysqli_num_rows($result_language) > 0) {
          $language = mysqli_fetch_assoc($result_language);
          mysqli_stmt_close($stmt_language);
      } else {
          echo '<div class="alert alert-danger">Language no encontrado</div>';
          $language = null;
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de language no válido</div>';
      $language = null;
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
                          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='Languages.php'">
                            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Languages
                          </button>
                            <h4 class="mb-2">Language <?php echo isset($language['description']) ? htmlspecialchars($language['description']) : 'Language no encontrado'; ?></h4>
                            <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                              <li class="list-inline-item">
                                <i class="icon-base ri ri-global-line me-2 icon-24px"></i><span class="fw-medium">Código: <?php echo isset($language['cod_LP']) ? htmlspecialchars($language['cod_LP']) : 'N/A'; ?></span>
                              </li>
                              <li class="list-inline-item">
                                <i class="icon-base ri ri-map-pin-line me-2 icon-24px"></i><span class="fw-medium">País: <?php echo isset($language['pais']) ? htmlspecialchars($language['pais']) : 'N/A'; ?></span>
                              </li>
                            </ul>
                          </div>
                          <div class="d-flex gap-2">
                            <a href="editar_Language.php?id=<?php echo $id_lang; ?>" class="btn btn-primary waves-effect waves-light">
                              <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar Language
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
                          <i class="icon-base ri ri-global-line icon-sm me-2"></i>Información
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-traducciones" aria-controls="navs-pills-top-traducciones" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-translate icon-sm me-2"></i>Traducciones
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
                <div class="tab-pane fade show active" id="navs-pills-top-perfil" role="tabpanel">
                  <!-- Language Profile Content -->
              <div class="row">
                <div class="col-xl-4 col-lg-5 col-md-5">
                  <!-- About Language -->
                  <div class="card mb-6">
                    <div class="card-body">
                      <small class="card-text text-uppercase text-body-secondary small">Información General</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-global-line icon-24px"></i><span class="fw-medium mx-2">Código:</span> <span><?php echo isset($language['cod_LP']) ? htmlspecialchars($language['cod_LP']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-text icon-24px"></i><span class="fw-medium mx-2">Descripción:</span> <span><?php echo isset($language['description']) ? htmlspecialchars($language['description']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-map-pin-line icon-24px"></i><span class="fw-medium mx-2">País:</span> <span><?php echo isset($language['pais']) ? htmlspecialchars($language['pais']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                          <i class="icon-base ri ri-check-line icon-24px"></i><span class="fw-medium mx-2">Estado:</span> 
                          <span>
                            <?php 
                            if (isset($language['stateLang'])) {
                                echo $language['stateLang'] === 'true' ? 
                                    '<span class="badge bg-success">Activo</span>' : 
                                    '<span class="badge bg-secondary">Inactivo</span>';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                          </span>
                        </li>
                      </ul>
                    </div>
                  </div>
                  <!--/ About Language -->
                </div>
                <!--/ About Language -->

                <div class="col-xl-8 col-lg-7 col-md-7">
                                     <!-- Language Activity -->
                   <div class="card card-action mb-6">
                     <div class="card-header align-items-center">
                       <h5 class="card-action-title mb-0">
                         <i class="icon-base ri ri-information-line icon-24px text-body me-4"></i>Detalles del Language
                       </h5>
                     </div>
                                         <div class="card-body pt-5">
                       <div class="row">
                         <div class="col-md-6">
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Código del Language</h6>
                             <p class="text-body-secondary"><?php echo isset($language['cod_LP']) ? htmlspecialchars($language['cod_LP']) : 'N/A'; ?></p>
                           </div>
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Descripción</h6>
                             <p class="text-body-secondary"><?php echo isset($language['description']) ? htmlspecialchars($language['description']) : 'N/A'; ?></p>
                           </div>
                         </div>
                         <div class="col-md-6">
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">País Asociado</h6>
                             <p class="text-body-secondary"><?php echo isset($language['pais']) ? htmlspecialchars($language['pais']) : 'N/A'; ?></p>
                           </div>
                           <div class="mb-4">
                             <h6 class="fw-medium mb-2">Estado</h6>
                             <p class="text-body-secondary">
                               <?php 
                               if (isset($language['stateLang'])) {
                                   echo $language['stateLang'] === 'true' ? 
                                       '<span class="badge bg-success">Activo</span>' : 
                                       '<span class="badge bg-secondary">Inactivo</span>';
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
                  <!--/ Language Activity -->
                </div>
              </div>
              <!--/ Language Profile Content -->
                </div>
                <!--/ Tab Información -->

                <!-- Tab Traducciones -->
                <div class="tab-pane fade" id="navs-pills-top-traducciones" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title">Traducciones del Language</h5>
                        </div>
                        <div class="card-body">
                          <div class="table-responsive">
                            <table id="tablaTraducciones" class="table table-hover">
                              <!-- DataTable se carga dinámicamente -->
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Traducciones -->
              </div>
              <!--/ Tab Content -->
</div>

<!-- Modal Editar Traducción -->
<div class="modal fade" id="modalEditarTraduccion" tabindex="-1" aria-labelledby="modalEditarTraduccionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarTraduccionLabel">Editar Traducción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarTraduccion">
          <input type="hidden" id="id_translations" name="id_translations">
          
          <div class="row">
            <div class="col-12">
              <div class="form-floating form-floating-outline mb-4">
                <input type="text" class="form-control" id="entry_translate" name="entry_translate" placeholder="Texto de entrada" required>
                <label for="entry_translate">Entrada *</label>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-12">
              <div class="form-floating form-floating-outline mb-4">
                <textarea class="form-control" id="exit_translate" name="exit_translate" placeholder="Traducción" style="height: 100px" required></textarea>
                <label for="exit_translate">Traducción *</label>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="icon-base ri ri-close-line me-2"></i>
          Cancelar
        </button>
        <button type="button" class="btn btn-primary" id="btnGuardarTraduccion">
          <i class="icon-base ri ri-check-line me-2"></i>
          Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- / Content -->