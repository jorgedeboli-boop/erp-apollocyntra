<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear Nueva Empresa</h4>
          <small class="text-muted">Complete el formulario para registrar una nueva empresa en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='empresas.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Empresas
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearEmpresa" method="POST" action="parts/empresas/crear/procesar_empresa.php" class="needs-validation" novalidate="">
            <div class="row">
              <!-- Información Básica -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Básica</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" placeholder="Nombre de la empresa" required />
                  <label for="nombre_empresa">Nombre de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="cif_empresa" name="cif_empresa" placeholder="CIF de la empresa" required />
                  <label for="cif_empresa">CIF de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="email" class="form-control" id="email_empresa" name="email_empresa" placeholder="empresa@ejemplo.com" required />
                  <label for="email_empresa">Email de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="tel" class="form-control" id="telefono_empresa" name="telefono_empresa" placeholder="+34 91 123 45 67" required />
                  <label for="telefono_empresa">Teléfono *</label>
                </div>
              </div>
              
              <!-- Dirección -->
              <div class="col-md-6">
                <h5 class="mb-3">Dirección</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="direccion_empresa" name="direccion_empresa" placeholder="Calle, número, piso..." required />
                  <label for="direccion_empresa">Dirección *</label>
                </div>

                <input type="hidden" id="pais_empresa" name="pais_empresa" value="" />
                <input type="hidden" id="provincia_empresa" name="provincia_empresa" value="" />
                <input type="hidden" id="poblacion_empresa" name="poblacion_empresa" value="" />

                <div class="mb-3">
                  <label for="rel_id_pais" class="form-label">País *</label>
                  <select class="form-select select2" id="rel_id_pais" name="rel_id_pais" required>
                    <option value="">Seleccionar país</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="rel_id_provincia" class="form-label">Provincia *</label>
                  <select class="form-select select2" id="rel_id_provincia" name="rel_id_provincia" required>
                    <option value="">Seleccionar provincia</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="rel_id_poblacion" class="form-label">Población *</label>
                  <select class="form-select select2" id="rel_id_poblacion" name="rel_id_poblacion" required>
                    <option value="">Seleccionar población</option>
                  </select>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="codigo_postal_empresa" name="codigo_postal_empresa" placeholder="Código postal" maxlength="5" readonly required />
                  <label for="codigo_postal_empresa">Código Postal *</label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="empresas.php" class="btn btn-text-primary me-2">
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
                <button type="submit" class="btn btn-primary" id="btnCrearEmpresa">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Crear Empresa
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