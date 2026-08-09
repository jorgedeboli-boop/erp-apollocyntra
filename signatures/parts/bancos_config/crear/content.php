<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear nuevo banco</h4>
          <small class="text-muted">Complete el formulario para registrar un banco</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='bancos_config.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Bancos
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formCrearBanco" method="POST" action="parts/bancos_config/crear/procesar_banco.php">
            <div class="row">
              <div class="col-md-6">
                <h5 class="mb-3">Información básica</h5>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_banco" name="nombre_banco" placeholder="Nombre del banco" maxlength="124" required />
                  <label for="nombre_banco">Nombre del banco *</label>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="contacto_banco" name="contacto_banco" placeholder="Persona de contacto" maxlength="164" required />
                  <label for="contacto_banco">Contacto *</label>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="tel" class="form-control" id="telefono_banco" name="telefono_banco" placeholder="Teléfono" maxlength="64" required />
                  <label for="telefono_banco">Teléfono *</label>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="email" class="form-control" id="email_banco" name="email_banco" placeholder="banco@ejemplo.com" maxlength="128" required />
                  <label for="email_banco">Email *</label>
                </div>

                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="estado_banco" name="estado_banco" value="true" checked>
                  <label class="form-check-label" for="estado_banco">Banco activo</label>
                </div>
              </div>

              <div class="col-md-6">
                <h5 class="mb-3">Dirección</h5>

                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="direccion_banco" name="direccion_banco" placeholder="Calle, número..." maxlength="168" required />
                  <label for="direccion_banco">Dirección *</label>
                </div>

                <div class="mb-3">
                  <label for="pais" class="form-label">País *</label>
                  <select class="form-select select2" id="pais" name="pais" required>
                    <option value="">Seleccionar país</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="c_provincia" class="form-label">Provincia *</label>
                  <select class="form-select select2" id="c_provincia" name="c_provincia" required>
                    <option value="">Seleccionar provincia</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="c_poblacion" class="form-label">Población *</label>
                  <select class="form-select select2" id="c_poblacion" name="c_poblacion" required>
                    <option value="">Seleccionar población</option>
                  </select>
                </div>

                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Código postal" maxlength="5" readonly />
                  <label for="codigo_postal">Código postal</label>
                </div>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between">
              <a href="bancos_config.php" class="btn btn-text-primary me-2">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la lista
              </a>
              <div>
                <button type="reset" class="btn btn-text-danger me-2">
                  <i class="icon-base ri ri-refresh-line me-2"></i>
                  Limpiar
                </button>
                <button type="submit" class="btn btn-primary" id="btnCrearBanco">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Crear banco
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
