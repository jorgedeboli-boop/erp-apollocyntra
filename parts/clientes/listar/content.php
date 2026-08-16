<div class="container-fluid flex-grow-1 container-p-y">
<?php require_once __DIR__ . '/filtros_opciones.php'; ?>

  <!-- Clientes List Table    -->
  <div class="card card-mobile-not-shadow">

    <div class="card-header border-bottom card-header-forms titulos-cards-pages">

      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center w-100 gap-2">
        <h5 class="card-title mb-0 w-100 w-md-auto">Clientes</h5>

        <div class="d-flex align-items-center justify-content-between gap-2 flex-shrink-0">
          <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-md-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_clientes" aria-expanded="false" aria-controls="collapse_filtros_clientes"><i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar</button>
          <?php if ($puede_acceder_crear): ?>
          <a href="crear_cliente.php" type="button" class="btn btn-primary waves-effect waves-light px-3" id="btn_crear_cliente"><span class="icon-base ri ri-add-fill icon-22px me-1"></span>Nuevo cliente</a>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <div class="card-body pb-0">

      <div class="collapse d-lg-block" id="collapse_filtros_clientes">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
          
          <div class="col-md-4 user_tipo_identificacion">
            <select id="UserTipoIdentificacion" class="form-select select2-filter text-capitalize select2-custom">
              <?php clientes_listar_imprimir_opciones_tipo_identificacion($app_country_id); ?>
            </select>
          </div>
          <div class="col-md-4 user_provincia">
            <select id="UserProvincia" class="form-select select2-filter text-capitalize select2-custom">
              <?php clientes_listar_imprimir_opciones_provincia($app_country_id); ?>
            </select>
          </div>
          <div class="col-md-4 user_estado">
            <select id="UserEstado" class="form-select select2-filter text-capitalize select2-custom">
              <?php clientes_listar_imprimir_opciones_estado(); ?>
            </select>
          </div>
        </div>

      </div>

    </div>

    <div class="card-datatable table-responsive">
                  <table class="datatables-users table border-top">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Tipo Identificación</th>
                        <th>Número Identificación</th>
                        <th>Nacionalidad</th>
                        <th>Teléfono</th>
                        <th>Provincia</th>
                        <th>Estado</th>
                        <th>Fecha Alta</th>
                      </tr>
                    </thead>
                  </table>
                </div>
  </div>

  <div class="row g-6 mt-1 d-none d-md-flex">

    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Total Clientes</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-clientes">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Registrados en el sistema</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <div class="icon-base ri ri-group-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Clientes Habilitados</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-clientes-habilitados">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Con acceso activo</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <div class="icon-base ri ri-user-follow-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Clientes Lista Negra</p>
              <div class="d-flex align-items-center">
                <h4 class="mb-1 me-1" id="total-clientes-lista-negra">0</h4>
                <div class="stats-loading" style="display: none;">
                  <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                </div>
              </div>
              <small class="mb-0">Estado deshabilitado</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-danger rounded-circle">
                <div class="icon-base ri ri-user-forbid-line icon-26px"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Los scripts se cargan desde javascript.php -->