<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row mb-4">
    <div class="col-12 d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <h4 class="mb-1">Estado de conexiones</h4>
        <p class="mb-0 text-muted">Conexiones activas agrupadas por sucursal y usuarios conectados.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-primary waves-effect waves-light" id="btnRefrescarConexionesState">
          <i class="icon-base ri ri-refresh-line icon-16px me-2"></i>Actualizar
        </button>
        <button type="button" class="btn btn-outline-primary waves-effect waves-light" id="btnVerMapa">
          <i class="icon-base ri ri-map-2-line icon-16px me-2"></i>Ver mapa
        </button>
        <button type="button" class="btn btn-outline-primary waves-effect waves-light d-none" id="btnVerListado">
          <i class="icon-base ri ri-list-check icon-16px me-2"></i>Ver listado
        </button>
      </div>
    </div>
  </div>

  <div id="mapa" class="d-none"></div>

  <div id="connectionsStateListado">
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="me-1">
              <p class="text-heading mb-1">Conexiones activas</p>
              <h4 class="mb-1" id="stats-total-conexiones">0</h4>
              <small class="mb-0">Sesiones abiertas ahora</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-success rounded-circle">
                <i class="icon-base ri ri-wifi-line icon-26px"></i>
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
              <p class="text-heading mb-1">Usuarios conectados</p>
              <h4 class="mb-1" id="stats-total-usuarios">0</h4>
              <small class="mb-0">Usuarios distintos en línea</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-primary rounded-circle">
                <i class="icon-base ri ri-user-follow-line icon-26px"></i>
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
              <p class="text-heading mb-1">Sucursales con sesiones</p>
              <h4 class="mb-1" id="stats-total-sucursales">0</h4>
              <small class="mb-0">Sucursales con al menos una conexión</small>
            </div>
            <div class="avatar">
              <div class="avatar-initial bg-label-info rounded-circle">
                <i class="icon-base ri ri-building-line icon-26px"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="card-title mb-0">
            <i class="icon-base ri ri-wifi-line icon-24px text-body me-2"></i>Conexiones por sucursal
          </h5>
          <small class="text-muted" id="connectionsStateUpdatedAt">—</small>
        </div>
        <div class="card-body">
          <div id="connectionsStateLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-3 mb-0">Cargando conexiones activas...</p>
          </div>
          <div id="connectionsStateError" class="alert alert-danger d-none mb-0" role="alert"></div>
          <div id="connectionsStateEmpty" class="text-center py-5 d-none">
            <i class="icon-base ri ri-wifi-off-line icon-48px text-muted mb-3"></i>
            <h6 class="mb-1">No hay conexiones activas</h6>
            <p class="text-muted mb-0">Ningún usuario está conectado en este momento.</p>
          </div>
          <div id="connectionsStateAccordion" class="accordion accordion-custom-button"></div>
        </div>
      </div>
    </div>
  </div>
  </div>

  
</div>
<!-- / Content -->
