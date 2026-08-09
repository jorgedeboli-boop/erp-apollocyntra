<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear Nuevo Proveedor</h4>
          <small class="text-muted">Complete el formulario para registrar un nuevo proveedor en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='proveedores.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Proveedores
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearProveedor" method="POST" action="parts/proveedores/crear/procesar_proveedor.php">
            <div class="row">
              <!-- Información Básica -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Básica</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_proveedor" name="nombre_proveedor" placeholder="Nombre del proveedor" required />
                  <label for="nombre_proveedor">Nombre del Proveedor *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="cif_proveedor" name="cif_proveedor" placeholder="CIF del proveedor" required />
                  <label for="cif_proveedor">CIF del Proveedor *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="email" class="form-control" id="email_proveedor" name="email_proveedor" placeholder="proveedor@ejemplo.com" required />
                  <label for="email_proveedor">Email del Proveedor *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="tel" class="form-control" id="telefono_proveedor" name="telefono_proveedor" placeholder="+34 91 123 45 67" required />
                  <label for="telefono_proveedor">Teléfono *</label>
                </div>
              </div>
              
              <!-- Dirección -->
              <div class="col-md-6">
                <h5 class="mb-3">Dirección</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="direccion_proveedor" name="direccion_proveedor" placeholder="Calle, número, piso..." required />
                  <label for="direccion_proveedor">Dirección *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="poblacion_proveedor" name="poblacion_proveedor" placeholder="Población" required />
                  <label for="poblacion_proveedor">Población *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="provincia_proveedor" name="provincia_proveedor" placeholder="Provincia" required />
                  <label for="provincia_proveedor">Provincia *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="codigo_postal_proveedor" name="codigo_postal_proveedor" placeholder="Código postal" maxlength="5" required />
                  <label for="codigo_postal_proveedor">Código Postal *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="pais_proveedor" name="pais_proveedor" placeholder="País" value="España" required />
                  <label for="pais_proveedor">País *</label>
                </div>
              </div>
            </div>
            
            <div class="row mt-4">
              <!-- Información Adicional -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Adicional</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="moneda_proveedor" name="moneda_proveedor" placeholder="EUR" value="EUR" required />
                  <label for="moneda_proveedor">Moneda *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <select class="form-select" id="forma_pago_proveedor" name="forma_pago_proveedor" required>
                    <option value="">Seleccionar forma de pago</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="domiciliacion">Domiciliación</option>
                    <option value="bizum">Bizum</option>
                  </select>
                  <label for="forma_pago_proveedor">Forma de Pago *</label>
                </div>
              </div>
              
              <!-- Servicios de Fundición -->
              <div class="col-md-6">
                <h5 class="mb-3">Servicios de Fundición</h5>
                
                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="fundicion" name="fundicion" value="true">
                  <label class="form-check-label" for="fundicion">
                    Servicio de Fundición
                  </label>
                </div>
                
                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="fundicion_multi_kilates" name="fundicion_multi_kilates" value="true">
                  <label class="form-check-label" for="fundicion_multi_kilates">
                    Fundición Multi-Kilates
                  </label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="proveedores.php" class="btn btn-text-primary me-2">
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
                <button type="submit" class="btn btn-primary" id="btnCrearProveedor">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Crear Proveedor
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