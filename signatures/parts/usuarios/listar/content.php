<div class="container-fluid flex-grow-1 container-p-y">
              <div class="row g-6 mb-6">
                <div class="col-sm-6 col-xl-3">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between">
                        <div class="me-1">
                          <p class="text-heading mb-1">Usuarios Conectados</p>
                          <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-2" id="total-usuarios-conectados">0</h4>
                            <div class="stats-loading" style="display: none;">
                              <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                          </div>
                          <small class="mb-0">Actualmente en línea</small>
                        </div>
                        <div class="avatar">
                          <div class="avatar-initial bg-label-primary rounded-circle">
                            <div class="icon-base ri ri-wifi-line icon-26px"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between">
                        <div class="me-1">
                          <p class="text-heading mb-1">Total Usuarios</p>
                          <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-1" id="total-usuarios">0</h4>
                            <div class="stats-loading" style="display: none;">
                              <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                            </div>
                          </div>
                          <small class="mb-0">Registrados en el sistema</small>
                        </div>
                        <div class="avatar">
                          <div class="avatar-initial bg-label-danger rounded">
                            <div class="icon-base ri ri-group-line icon-26px"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between">
                        <div class="me-1">
                          <p class="text-heading mb-1">Usuarios Habilitados</p>
                          <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-1" id="total-usuarios-habilitados">0</h4>
                            <div class="stats-loading" style="display: none;">
                              <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                            </div>
                          </div>
                          <small class="mb-0">Con acceso activo</small>
                        </div>
                        <div class="avatar">
                          <div class="avatar-initial bg-label-success rounded-circle">
                            <div class="icon-base ri ri-checkbox-circle-fill icon-26px"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                  <div class="card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between">
                        <div class="me-1">
                          <p class="text-heading mb-1">Usuarios Bloqueados</p>
                          <div class="d-flex align-items-center">
                            <h4 class="mb-1 me-1" id="total-usuarios-bloqueados">0</h4>
                            <div class="stats-loading" style="display: none;">
                              <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                            </div>
                          </div>
                          <small class="mb-0">Sin acceso al sistema</small>
                        </div>
                        <div class="avatar">
                          <div class="avatar-initial bg-label-warning rounded-circle">
                            <div class="icon-base ri ri-user-forbid-line icon-26px"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Users List Table -->
              <div class="card">
                <div class="card-header border-bottom card-header-forms">
                  <h5 class="card-title mb-0">Usuarios</h5>
                  <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 select2-btn-height">
                    <div class="col-md-4 user_sucursal"></div>
                    <div class="col-md-4 user_estado"></div>
                    <div class="col-md-4 user_jerarquia"></div>
                  </div>
                </div>
                <div class="card-datatable">
                  <table class="datatables-users table">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Ultima conexión</th>
                        <th>Jerarquía</th>
                        <th>Sucursal</th>
                        <th>Estado</th>
                      </tr>
                    </thead>
                  </table>
                </div>
              </div>
            </div>