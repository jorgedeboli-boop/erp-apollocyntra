<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear Nuevo Gasto</h4>
          <small class="text-muted">Complete el formulario para registrar un nuevo gasto en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='gastos.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Gastos
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearGasto" method="POST" action="parts/gastos/crear/procesar_gasto.php">
            <div class="row">
              <!-- Información Básica -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Básica</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_gasto" name="nombre_gasto" placeholder="Nombre de la gasto" required />
                  <label for="nombre_gasto">Nombre de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="cif_gasto" name="cif_gasto" placeholder="CIF de la gasto" required />
                  <label for="cif_gasto">CIF de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="email" class="form-control" id="email_gasto" name="email_gasto" placeholder="gasto@ejemplo.com" required />
                  <label for="email_gasto">Email de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="tel" class="form-control" id="telefono_gasto" name="telefono_gasto" placeholder="+34 91 123 45 67" required />
                  <label for="telefono_gasto">Teléfono *</label>
                </div>
              </div>
              
              <!-- Dirección -->
              <div class="col-md-6">
                <h5 class="mb-3">Dirección</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="direccion_gasto" name="direccion_gasto" placeholder="Calle, número, piso..." required />
                  <label for="direccion_gasto">Dirección *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="poblacion_gasto" name="poblacion_gasto" placeholder="Población" required />
                  <label for="poblacion_gasto">Población *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="provincia_gasto" name="provincia_gasto" placeholder="Provincia" required />
                  <label for="provincia_gasto">Provincia *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="codigo_postal_gasto" name="codigo_postal_gasto" placeholder="Código postal" maxlength="5" required />
                  <label for="codigo_postal_gasto">Código Postal *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="pais_gasto" name="pais_gasto" placeholder="País" required />
                  <label for="pais_gasto">País *</label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="gastos.php" class="btn btn-text-primary me-2">
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