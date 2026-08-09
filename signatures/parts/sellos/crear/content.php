<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear nuevo sello</h4>
          <small class="text-muted">Complete el formulario para registrar un sello en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='sellos.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Sellos
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearSello" method="POST" action="parts/sellos/crear/procesar_sello.php">
            <div class="row">
              <div class="col-md-6">
                <h5 class="mb-3">Información del sello</h5>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_sello" name="nombre_sello" placeholder="Nombre del sello" maxlength="164" required />
                  <label for="nombre_sello">Nombre del sello *</label>
                </div>

                <div class="mb-4">
                  <label class="form-label d-block mb-3">¿Posee logotipo? *</label>
                  <div class="d-flex gap-3">
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="sello_logotipo_si">
                        <input class="form-check-input" type="radio" name="sello_logotipo" value="true" id="sello_logotipo_si" required>
                        <span class="custom-option-header">
                          <span class="badge bg-label-success">Sí</span>
                        </span>
                      </label>
                    </div>
                    <div class="form-check custom-option custom-option-basic">
                      <label class="form-check-label custom-option-content" for="sello_logotipo_no">
                        <input class="form-check-input" type="radio" name="sello_logotipo" value="false" id="sello_logotipo_no" checked required>
                        <span class="custom-option-header">
                          <span class="badge bg-label-secondary">No</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between">
              <a href="sellos.php" class="btn btn-text-primary me-2">
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
                <button type="submit" class="btn btn-primary" id="btnCrearSello">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Crear sello
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
