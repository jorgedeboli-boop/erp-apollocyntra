 <!-- Content -->
 <div class="container-fluid flex-grow-1 container-p-y">

              <div class="row g-6 mb-6">
                <!-- Congratulations card -->
                <div class="col-xxl-4">
                  <div class="card h-100">
                    <div class="card-body text-nowrap">
                      <h5 class="card-title mb-1"><?php echo $_SESSION['sucursal_section']; ?>¡Felicitacionesa <span class="fw-bold"><?php echo sanitizar_dato_sesion($usuario_nombre_completo); ?>!</span> 🎉</h5>
                      <p class="card-subtitle mb-3">Bienvenido al sistema TPV Quinta Gracia</p>
                      <h4 class="text-primary mb-0"><?php echo sanitizar_dato_sesion($usuario_privilegio_nombre); ?></h4>
                      <p class="mb-3">Privilegio asignado 🚀</p>
                      <a href="javascript:;" class="btn btn-sm btn-primary">Ver Perfil</a>
                    </div>
                    <img
                      src="assets/img/illustrations/trophy.png"
                      class="position-absolute bottom-0 end-0 me-4"
                      height="140"
                      alt="view sales" />
                  </div>
                </div>
                <!--/ Congratulations card -->

                <!-- Información del Usuario -->
                <div class="col-xxl-2 col-md-3 col-sm-6">
                  <div class="card h-100">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="avatar">
                          <div class="avatar-initial bg-label-primary rounded-circle">
                            <i class="icon-base ri ri-user-line icon-24px"></i>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <p class="mb-0 text-success">Activo</p>
                          <i class="icon-base ri ri-check-line text-success icon-sm"></i>
                        </div>
                      </div>
                      <div class="card-info mt-5">
                        <h5 class="mb-1"><?php echo sanitizar_dato_sesion($usuario_nombre); ?></h5>
                        <p>Usuario</p>
                        <div class="badge bg-label-secondary rounded-pill"><?php echo sanitizar_dato_sesion($usuario_privilegio_nombre); ?></div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Información del Usuario -->

                <!-- Sucursal -->
                <div class="col-xxl-2 col-md-3 col-sm-6">
                  <div class="card h-100">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="avatar">
                          <div class="avatar-initial bg-label-success rounded-circle">
                            <i class="icon-base ri ri-building-line icon-24px"></i>
                          </div>
                        </div>
                        <div class="d-flex align-items-center">
                          <p class="mb-0 text-success">Sucursal</p>
                          <i class="icon-base ri ri-map-pin-line text-success icon-sm"></i>
                        </div>
                      </div>
                      <div class="card-info mt-5">
                        <h5 class="mb-1"><?php echo sanitizar_dato_sesion($usuario_sucursal); ?></h5>
                        <p>Número de Sucursal</p>
                        <div class="badge bg-label-secondary rounded-pill">Activa</div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Sucursal -->

                <!-- Estado del Usuario -->
                <div class="col-xxl-2 col-md-3 col-sm-6">
                  <div class="card h-100">
                    <div class="card-header">
                      <div class="d-flex align-items-center mb-1 flex-wrap">
                        <h5 class="mb-0 me-1"><?php echo obtener_estado_usuario_formateado(); ?></h5>
                        <p class="mb-0 text-<?php echo obtener_clase_estado_usuario(); ?>">
                          <?php echo $usuario_estado === 'true' ? '+100%' : '-100%'; ?>
                        </p>
                      </div>
                      <span class="d-block card-subtitle">Estado del Usuario</span>
                    </div>
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                          <div class="avatar-initial bg-label-<?php echo obtener_clase_estado_usuario(); ?> rounded">
                            <i class="icon-base ri ri-<?php echo obtener_icono_estado_usuario(); ?>-line icon-24px"></i>
                          </div>
                        </div>
                        <div>
                          <h6 class="mb-0"><?php echo obtener_estado_usuario_formateado(); ?></h6>
                          <small class="text-body-secondary">Estado actual</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Estado del Usuario -->

                <!-- Último Acceso -->
                <div class="col-xxl-2 col-md-3 col-sm-6">
                  <div class="card h-100">
                    <div class="card-header">
                      <div class="d-flex align-items-center mb-1 flex-wrap">
                        <h5 class="mb-0 me-1"><?php echo sanitizar_dato_sesion($usuario_ultimo_acceso); ?></h5>
                        <p class="mb-0 text-info">Reciente</p>
                      </div>
                      <span class="d-block card-subtitle">Último Acceso</span>
                    </div>
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                          <div class="avatar-initial bg-label-info rounded">
                            <i class="icon-base ri ri-time-line icon-24px"></i>
                          </div>
                        </div>
                        <div>
                          <h6 class="mb-0">Acceso Registrado</h6>
                          <small class="text-body-secondary"><?php echo tiempo_desde_ultimo_acceso(); ?></small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Último Acceso -->
              </div>

              <div class="row g-6">
                <!-- Información Detallada del Usuario -->
                <div class="col-lg-8 col-12">
                  <div class="card h-100">
                    <div class="row">
                      <div class="col-md-8 col-12 order-2 order-md-0">
                        <div class="card-header">
                          <h5 class="mb-1">Información del Usuario</h5>
                          <p class="mb-0 card-subtitle">Datos completos del perfil</p>
                        </div>
                        <div class="card-body px-2 pt-xl-7">
                          <div class="row">
                            <div class="col-md-6 mb-4">
                              <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                  <div class="avatar-initial bg-label-primary rounded">
                                    <i class="icon-base ri ri-user-line icon-24px"></i>
                                  </div>
                                </div>
                                <div>
                                  <h6 class="mb-1">Nombre Completo</h6>
                                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_nombre_completo); ?></small>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6 mb-4">
                              <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                  <div class="avatar-initial bg-label-success rounded">
                                    <i class="icon-base ri ri-mail-line icon-24px"></i>
                                  </div>
                                </div>
                                <div>
                                  <h6 class="mb-1">Email</h6>
                                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_email); ?></small>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6 mb-4">
                              <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                  <div class="avatar-initial bg-label-warning rounded">
                                    <i class="icon-base ri ri-phone-line icon-24px"></i>
                                  </div>
                                </div>
                                <div>
                                  <h6 class="mb-1">Teléfono</h6>
                                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_telefono); ?></small>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6 mb-4">
                              <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                  <div class="avatar-initial bg-label-info rounded">
                                    <i class="icon-base ri ri-building-line icon-24px"></i>
                                  </div>
                                </div>
                                <div>
                                  <h6 class="mb-1">Sucursal</h6>
                                  <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_sucursal); ?></small>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-4 col-12 border-start">
                        <div class="card-header">
                          <h5 class="mb-1">Privilegios del Sistema</h5>
                          <p class="mb-0 card-subtitle">Nivel de acceso actual</p>
                        </div>
                        <div class="card-body pt-4">
                          <div class="d-flex align-items-center mb-6">
                            <div class="avatar">
                              <div class="avatar-initial bg-label-primary rounded">
                                <i class="icon-base ri ri-shield-keyhole-line icon-24px"></i>
                              </div>
                            </div>
                            <div class="ms-3 d-flex flex-column">
                              <h6 class="mb-1"><?php echo sanitizar_dato_sesion($usuario_privilegio_nombre); ?></h6>
                              <small>ID: <?php echo sanitizar_dato_sesion($usuario_privilegio_id); ?></small>
                            </div>
                          </div>
                          <div class="d-flex align-items-center mb-6">
                            <div class="avatar">
                              <div class="avatar-initial bg-label-success rounded">
                                <i class="icon-base ri ri-check-line icon-24px"></i>
                              </div>
                            </div>
                            <div class="ms-3 d-flex flex-column">
                              <h6 class="mb-1">Estado</h6>
                              <small><?php echo obtener_estado_usuario_formateado(); ?></small>
                            </div>
                          </div>
                          <div class="d-flex align-items-center mb-6">
                            <div class="avatar">
                              <div class="avatar-initial bg-label-info rounded">
                                <i class="icon-base ri ri-time-line icon-24px"></i>
                              </div>
                            </div>
                            <div class="ms-3 d-flex flex-column">
                              <h6 class="mb-1">Último Acceso</h6>
                              <small><?php echo sanitizar_dato_sesion($usuario_ultimo_acceso); ?></small>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Información Detallada del Usuario -->

                <!-- Estadísticas Rápidas -->
                <div class="col-lg-4 col-md-6">
                  <div class="card h-100">
                    <div class="card-header">
                      <h5 class="mb-0">Estadísticas Rápidas</h5>
                    </div>
                    <div class="card-body">
                      <div class="d-flex align-items-center mb-4">
                        <div class="avatar">
                          <div class="avatar-initial bg-label-primary rounded">
                            <i class="icon-base ri ri-user-line icon-24px"></i>
                          </div>
                        </div>
                        <div class="ms-3">
                          <h6 class="mb-0">Usuario ID</h6>
                          <small class="text-body-secondary"><?php echo sanitizar_dato_sesion($usuario_id); ?></small>
                        </div>
                      </div>
                      <div class="d-flex align-items-center mb-4">
                        <div class="avatar">
                          <div class="avatar-initial bg-label-success rounded">
                            <i class="icon-base ri ri-shield-check-line icon-24px"></i>
                          </div>
                        </div>
                        <div class="ms-3">
                          <h6 class="mb-0">Sesión Activa</h6>
                          <small class="text-body-secondary"><?php echo obtener_info_sesion(); ?></small>
                        </div>
                      </div>
                      <div class="d-flex align-items-center mb-4">
                        <div class="avatar">
                          <div class="avatar-initial bg-label-warning rounded">
                            <i class="icon-base ri ri-calendar-line icon-24px"></i>
                          </div>
                        </div>
                        <div class="ms-3">
                          <h6 class="mb-0">Fecha Actual</h6>
                          <small class="text-body-secondary"><?php echo $fecha_actual; ?></small>
                        </div>
                      </div>
                      <div class="d-flex align-items-center mb-4">
                        <div class="avatar">
                          <div class="avatar-initial bg-label-info rounded">
                            <i class="icon-base ri ri-time-line icon-24px"></i>
                          </div>
                        </div>
                        <div class="ms-3">
                          <h6 class="mb-0">Hora Actual</h6>
                          <small class="text-body-secondary"><?php echo $hora_actual; ?></small>
                        </div>
                      </div>
                      <div class="d-grid mt-4">
                        <button class="btn btn-primary" type="button">Ver Detalles</button>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Estadísticas Rápidas -->
              </div>
            </div>
            <!-- / Content -->

            

