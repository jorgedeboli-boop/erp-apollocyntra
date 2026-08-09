<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear Nuevo Language</h4>
          <small class="text-muted">Complete el formulario para registrar un nuevo language en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='Languages.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Languages
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearLanguage" method="POST" action="parts/Languages/crear/procesar_nuevo_Language.php">
            <div class="row">
              <!-- Información Básica -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Básica</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="cod_LP" name="cod_LP" placeholder="es-ES" required />
                  <label for="cod_LP">Código del Language *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="description" name="description" placeholder="Español (España)" required />
                  <label for="description">Descripción *</label>
                </div>
              </div>
              
              <!-- Configuración -->
              <div class="col-md-6">
                <h5 class="mb-3">Configuración</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <?php echo generarSelectPaises('rel_id_country', '', 'Seleccionar país'); ?>
                  <label for="rel_id_country">País *</label>
                </div>
                
                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="stateLang" name="stateLang" value="true" checked>
                  <label class="form-check-label" for="stateLang">
                    Language Activo
                  </label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="Languages.php" class="btn btn-text-primary me-2">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la lista
              </a>
              
              <div>
                <button type="reset" class="btn btn-text-danger me-2">
                  <i class="icon-base ri ri-refresh-line me-2"></i>
                  Limpiar
                </button>
                <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                  <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                  Aguarde...
                </button>
                <button type="submit" class="btn btn-primary" id="btnCrearLanguage">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Crear Language
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->