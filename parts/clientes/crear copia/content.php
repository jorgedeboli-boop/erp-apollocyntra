<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h5 class="card-title mb-0">Crear Nuevo Cliente</h5>
          <small class="text-muted">Complete el formulario para registrar un nuevo cliente en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='clientes.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Clientes
          </button>
        </div>
        <div class="card-body mt-5">
          <div id="card-form-custom-id" class="card card-form-custom">
          <form id="formCrearCliente" method="POST" action="parts/clientes/crear/procesar_cliente.php" class="fv-plugins-bootstrap5 fv-plugins-framework" autocomplete="off">
            <input type="hidden" id="id_cliente" name="id_cliente" value="false" autocomplete="off">

            <div id="datos_cliente_identificacion" style="margin-top: 0px;">
              <div class="row">
                <h5 class="mb-3">Identificación</h5>
                <div class="col-md-6">
                  <div class="form-floating form-floating-outline mb-4">
                    <?php
                    $countryIdTipo = !empty($app_country_id) ? (int) $app_country_id : (int) ($_SESSION['app_country_id'] ?? 0);
                    generarSelectTipoIdentificacion('', $countryIdTipo, true);
                    ?>
                    <label for="tipo_identificacion">Tipo de Identificación *</label>
                  </div>
                </div>
                <div class="col-md-6 form-control-validation">
                  <div class="input-group input-group-merge mb-4 inputgroupidentificacion">
                    <div class="form-floating form-floating-outline flex-grow-1">
                      <input type="text" class="form-control" id="identificacion" name="identificacion" placeholder="Número de identificación" required autocomplete="off" aria-describedby="btn_comprobar_identificacion" />
                      <label for="identificacion">Número de Identificación *</label>
                    </div>
                    <span class="input-group-text cursor-pointer p-1">
                      <button type="button" class="btn btn-primary waves-effect waves-light" id="btn_comprobar_identificacion">Comprobar</button>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div id="datos_cliente" style="margin-top: 15px;">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectNacionalidades('', true); ?>
                    <label for="nacionalidad">Nacionalidad *</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating form-floating-outline mb-3">
                    <input type="text" class="form-control date-mask" id="f_vencimiento" name="f_vencimiento" required autocomplete="off" placeholder="DD/MM/YYYY" inputmode="numeric" />
                    <label for="f_vencimiento">Fecha vencimiento identificación *</label>
                  </div>
                </div>
              </div>
              <div class="row">
                <h5 class="mb-3">Información Personal</h5>
                <div class="col-md-6">
                  <div class="mb-4 form-floating form-floating-outline">
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" required autocomplete="off" />
                    <label for="nombre" class="form-label">Nombre *</label>
                  </div>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido" required autocomplete="off" />
                    <label for="apellido" class="form-label">Apellido *</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control date-mask" id="f_nacimiento" name="f_nacimiento" required autocomplete="off" placeholder="DD/MM/YYYY" inputmode="numeric" />
                    <label for="f_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                  </div>
                  <div class="form-floating form-floating-outline mb-4">
                    <select class="form-select select2" id="sexo" name="sexo" required autocomplete="off">
                      <option value="">Seleccionar...</option>
                      <option value="MASCULINO">Masculino</option>
                      <option value="FEMENINO">Femenino</option>
                    </select>
                    <label for="sexo" class="form-label">Sexo *</label>
                  </div>
                </div>
              </div>
              <div class="row mt-4">
                <div class="col-md-6" id="container_direccion">
                  <?php require_once 'parts/clientes/crear/formulario_direccion_insert.php'; ?>
                </div>
                <div class="col-md-6">
                  <h5 class="mb-3">Información de Contacto</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="0034600000000" required autocomplete="off" />
                    <label for="telefono">Teléfono *</label>
                  </div>
                  <div class="form-floating form-floating-outline mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="cliente@ejemplo.com" autocomplete="off" />
                    <label for="email">Email</label>
                  </div>
                  <div class="mt-5">
                    <h5 class="mb-3">Sucursal</h5>
                    <div class="form-floating form-floating-outline mb-3">
                      <select class="form-select select2 select-custom" id="sucursal_cliente" name="sucursal_cliente" required>
                        <option value="">Seleccionar sucursal</option>
                        <?php
                        $sucursales = obtener_sucursales();
                        foreach ($sucursales as $sucursal) {
                            echo '<option value="' . $sucursal['id_sucursal'] . '">' . htmlspecialchars($sucursal['nombre_sucursal']) . '</option>';
                        }
                        ?>
                      </select>
                      <label for="sucursal_cliente">Sucursal *</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row mt-4">
                <div class="col-md-12">
                  <h5 class="mb-3">Información Adicional</h5>
                  <div class="form-floating form-floating-outline mb-3">
                    <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Observaciones sobre el cliente" style="height: 100px" autocomplete="off"></textarea>
                    <label for="observaciones">Observaciones</label>
                  </div>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              
              <a href="clientes.php" class="btn btn-text-primary me-2">
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
                <button type="submit" class="btn btn-primary" id="btnCrearCliente">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Crear Cliente
                </button>
              </div>
            </div>
          </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->