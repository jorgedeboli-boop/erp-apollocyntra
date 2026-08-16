<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  $es_usuario_root = (isset($usuario_root) && $usuario_root === 'true');
  $es_usuario_super_administrador = (isset($usuario_super_administrador) && $usuario_super_administrador === 'true');
  $acceso_denegado = false;
  $puede_editar_ficha = false;

  // Cargar datos del usuario
  $id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  
  if ($id_usuario) {
      $conexion = conectar_bd();
      
      // Consulta para obtener datos del usuario
      $query_usuario = "
          SELECT 
              u.id_usuario,
              u.usuario,
              u.nombre_usuario,
              u.apellido_usuario,
              u.email,
              u.estado_usuario,
              u.telefono_usuario,
              u.privilegio_usuario,
              u.observaciones_usuario,
              u.ultimo_acceso,
              u.fecAlta,
              u.usuario_root,
              u.super_admin,
              p.nombre_privilegio
          FROM usuarios u
          LEFT JOIN privilegios_usuarios p ON u.privilegio_usuario = p.id_privilegios
          WHERE u.id_usuario = ?
      ";
      
      $stmt_usuario = mysqli_prepare($conexion, $query_usuario);
      if ($stmt_usuario) {
          mysqli_stmt_bind_param($stmt_usuario, 'i', $id_usuario);
          mysqli_stmt_execute($stmt_usuario);
          $result_usuario = mysqli_stmt_get_result($stmt_usuario);
          
          if ($result_usuario && mysqli_num_rows($result_usuario) > 0) {
              $usuario = mysqli_fetch_assoc($result_usuario);

              if (!$es_usuario_root && ($usuario['usuario_root'] ?? 'false') === 'true') {
                  $acceso_denegado = true;
              }
              if (!$es_usuario_root && !$es_usuario_super_administrador && ($usuario['super_admin'] ?? 'false') === 'true') {
                  $acceso_denegado = true;
              }

              $puede_editar_ficha = $puede_acceder_editar && !$acceso_denegado;
              
              // Consulta para obtener el estado de conexión del usuario
              $query_conexion = "
                  SELECT 
                      uc.state_connection
                  FROM usersConexions uc
                  WHERE uc.userId = ?
                  ORDER BY uc.idUserConexion DESC
                  LIMIT 1
              ";
              
              $stmt_conexion = mysqli_prepare($conexion, $query_conexion);
              if ($stmt_conexion) {
                  mysqli_stmt_bind_param($stmt_conexion, 'i', $id_usuario);
                  mysqli_stmt_execute($stmt_conexion);
                  $result_conexion = mysqli_stmt_get_result($stmt_conexion);
                  
                  $estado_conexion = 'Desconectado';
                  if ($result_conexion && mysqli_num_rows($result_conexion) > 0) {
                      $conexion_data = mysqli_fetch_assoc($result_conexion);
                      $estado_conexion = ($conexion_data['state_connection'] == 'true') ? 'Conectado' : 'Desconectado';
                  }
                  
                  mysqli_stmt_close($stmt_conexion);
              } else {
                  $estado_conexion = 'Desconectado';
              }
              mysqli_stmt_close($stmt_usuario);
          } else {
              echo '<div class="alert alert-danger">Usuario no encontrado</div>';
          }
      } else {
          echo '<div class="alert alert-danger">Usuario no encontrado</div>';
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de usuario no válido</div>';
      
  }
  ?>

  <?php if ($acceso_denegado): ?>
  <div class="alert alert-danger">No tienes permisos para ver este usuario.</div>
  <?php elseif (!empty($usuario)): ?>

              <!-- Header -->
              <div class="row">
                <div class="col-12">
                  <div class="card mb-6">
                    
                    <div class="user-profile-header d-flex flex-column flex-sm-row text-sm-start text-center mb-5">
                      
                      <div class="flex-grow-1 mt-4 mt-sm-12">
                        <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-6">
                          <div class="user-profile-info">
                            <h4 class="mb-2"><?php echo isset($usuario['nombre_usuario']) ? htmlspecialchars($usuario['nombre_usuario'] . ' ' . $usuario['apellido_usuario']) : 'Usuario no encontrado'; ?></h4>
                            <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4">
                              <li class="list-inline-item">
                                <i class="icon-base ri ri-calendar-line me-2 icon-24px"></i><span class="fw-medium">Alta: <?php echo isset($usuario['fecAlta']) ? date('d/m/Y', strtotime($usuario['fecAlta'])) : 'N/A'; ?></span>
                              </li>
                              <li class="list-inline-item">
                                <span class="badge bg-label-<?php echo ($estado_conexion == 'Conectado') ? 'success' : 'secondary'; ?> me-2 ms-2 rounded-pill">
                                  <i class="icon-base ri <?php echo ($estado_conexion == 'Conectado') ? 'ri-wifi-line' : 'ri-wifi-off-line'; ?> me-1"></i>
                                  <?php echo $estado_conexion; ?>
                                </span>
                              </li>
                            </ul>
                          </div>
                          <div class="d-flex gap-2">
                            <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='usuarios.php'">
                              <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Usuarios
                            </button>
                            <?php if ($puede_editar_ficha): ?>
                            <a href="editar_usuario.php?id=<?php echo $id_usuario; ?>" class="btn btn-primary waves-effect waves-light">
                              <i class="icon-base ri ri-edit-line icon-16px me-2"></i>Editar Usuario
                            </a>
                            <button type="button" id="btnToggleEstado" class="btn <?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'true') ? 'btn-danger' : 'btn-success'; ?> waves-effect waves-light" onclick="toggleEstadoUsuario(<?php echo $id_usuario; ?>)">
                              <i class="icon-base ri <?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'true') ? 'ri-user-forbid-line' : 'ri-user-follow-line'; ?> icon-16px me-2"></i>
                              <?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'true') ? 'Deshabilitar' : 'Habilitar'; ?>
                            </button>
                              <?php if ($estado_conexion == 'Conectado'): ?>
                              <button
                                type="button"
                                id="btndesconectarUser"
                                class="btn btn-danger waves-effect waves-light"
                                data-user-id="<?php echo (int) $id_usuario; ?>"
                                data-nombre-usuario="<?php echo htmlspecialchars(trim(($usuario['nombre_usuario'] ?? '') . ' ' . ($usuario['apellido_usuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                              >
                                <i class="icon-base ri ri-wifi-off-line icon-16px me-2"></i>
                                Desconectar usuario
                              </button>
                              <?php endif; ?>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!--/ Header -->

              <!-- Navbar pills -->
              <div class="row">
                <div class="col-md-12">
                  <div class="nav-align-top">
                    <ul class="nav nav-pills mb-4" role="tablist">
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-perfil" aria-controls="navs-pills-top-perfil" aria-selected="true">
                          <i class="icon-base ri ri-user-3-line icon-sm me-2"></i>Perfil
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-actividad" aria-controls="navs-pills-top-actividad" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-time-line icon-sm me-2"></i>Actividad
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-conexiones" aria-controls="navs-pills-top-conexiones" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-wifi-line icon-sm me-2"></i>Conexiones
                        </button>
                      </li>
                      <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link waves-effect waves-light" role="tab" data-bs-toggle="tab" data-bs-target="#navs-pills-top-accesocustom" aria-controls="navs-pills-top-accesocustom" aria-selected="false" tabindex="-1">
                          <i class="icon-base ri ri-admin-line icon-sm me-2"></i>Acceso personalizado
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <!--/ Navbar pills -->

              <!-- Tab Content -->
              <div class="tab-content">
                <!-- Tab Perfil -->
                <div class="tab-pane fade show active" id="navs-pills-top-perfil" role="tabpanel">
                  <!-- User Profile Content -->
                  <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-5">
                      <!-- About User -->
                      <div class="card mb-6">
                    <div class="card-body">
                      <small class="card-text text-uppercase text-body-secondary small">Información Personal</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-user-3-line icon-24px"></i><span class="fw-medium mx-2">Nombre:</span> <span><?php echo isset($usuario['nombre_usuario']) ? htmlspecialchars($usuario['nombre_usuario']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-user-3-line icon-24px"></i><span class="fw-medium mx-2">Apellido:</span> <span><?php echo isset($usuario['apellido_usuario']) ? htmlspecialchars($usuario['apellido_usuario']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-user-line icon-24px"></i><span class="fw-medium mx-2">Usuario:</span> <span><?php echo isset($usuario['usuario']) ? htmlspecialchars($usuario['usuario']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-check-line icon-24px"></i><span class="fw-medium mx-2">Acceso:</span> <span><span class="badge bg-label-<?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'true') ? 'success' : 'danger'; ?> me-2 ms-2 rounded-pill"><?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'true') ? 'Habilitado' : 'Deshabilitado'; ?></span></span>
                        </li>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-shield-user-line icon-24px"></i><span class="fw-medium mx-2">Privilegio:</span> <span><?php echo isset($usuario['nombre_privilegio']) ? htmlspecialchars($usuario['nombre_privilegio']) : 'N/A'; ?></span>
                        </li>
                        <?php if ($es_usuario_root): ?>
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-shield-star-line icon-24px"></i><span class="fw-medium mx-2">Usuario Root:</span>
                          <span class="badge bg-label-<?php echo (isset($usuario['usuario_root']) && $usuario['usuario_root'] === 'true') ? 'warning' : 'secondary'; ?> rounded-pill">
                            <?php echo (isset($usuario['usuario_root']) && $usuario['usuario_root'] === 'true') ? 'Sí' : 'No'; ?>
                          </span>
                        </li>
                        <?php endif; ?>
                      </ul>
                      <small class="card-text text-uppercase text-body-secondary small">Contacto</small>
                      <ul class="list-unstyled my-3 py-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-phone-line icon-24px"></i><span class="fw-medium mx-2">Teléfono:</span> <span><?php echo isset($usuario['telefono_usuario']) ? htmlspecialchars($usuario['telefono_usuario']) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center mb-2">
                          <i class="icon-base ri ri-mail-open-line icon-24px"></i><span class="fw-medium mx-2">Email:</span> <span><?php echo isset($usuario['email']) ? htmlspecialchars($usuario['email']) : 'N/A'; ?></span>
                        </li>
                      </ul>
                      <small class="card-text text-uppercase text-body-secondary small">Estado de Conexión</small>
                      <ul class="list-unstyled mb-0 mt-3 pt-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri <?php echo ($estado_conexion == 'Conectado') ? 'ri-wifi-line' : 'ri-wifi-off-line'; ?> icon-24px text-body me-2"></i>
                          <span class="fw-medium me-2">Estado:</span>
                          <span class="badge bg-label-<?php echo ($estado_conexion == 'Conectado') ? 'success' : 'secondary'; ?> me-2 ms-2 rounded-pill">
                            <?php echo $estado_conexion; ?>
                          </span>
                        </li>
                        <li class="d-flex align-items-center">
                          <i class="icon-base ri ri-time-line icon-24px text-body me-2"></i>
                          <div class="d-flex flex-wrap">
                            <span class="fw-medium me-2">Último Acceso:</span><span><?php echo isset($usuario['ultimo_acceso']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'N/A'; ?></span>
                          </div>
                        </li>
                      </ul>
                    </div>
                  </div>
                  <!--/ About User -->
                  <!-- Profile Overview -->
                  <div class="card mb-6">
                    <div class="card-body">
                      <small class="card-text text-uppercase text-body-secondary small">Información Adicional</small>
                      <ul class="list-unstyled mb-0 mt-3 pt-1">
                        <li class="d-flex align-items-center mb-4">
                          <i class="icon-base ri ri-calendar-line icon-24px"></i><span class="fw-medium mx-2">Fecha Alta:</span> <span><?php echo isset($usuario['fecAlta']) ? date('d/m/Y', strtotime($usuario['fecAlta'])) : 'N/A'; ?></span>
                        </li>
                        <li class="d-flex align-items-center">
                          <i class="icon-base ri ri-id-card-line icon-24px"></i><span class="fw-medium mx-2">ID Usuario:</span> <span><?php echo isset($usuario['id_usuario']) ? htmlspecialchars($usuario['id_usuario']) : 'N/A'; ?></span>
                        </li>
                      </ul>
                    </div>
                  </div>
                  <!--/ Profile Overview -->
                </div>
                <div class="col-xl-8 col-lg-7 col-md-7">
                  <!-- Observaciones del Usuario -->
                  <?php if (isset($usuario['observaciones_usuario']) && !empty($usuario['observaciones_usuario'])): ?>
                  <div class="card card-action mb-6">
                    <div class="card-header align-items-center">
                      <h5 class="card-action-title mb-0">
                        <i class="icon-base ri ri-file-text-line icon-24px text-body me-4"></i>Observaciones
                      </h5>
                    </div>
                    <div class="card-body pt-5">
                      <p class="text-body-secondary"><?php echo htmlspecialchars($usuario['observaciones_usuario']); ?></p>
                    </div>
                  </div>
                  <!--/ Observaciones del Usuario -->
                  <?php endif; ?>

                  <!-- Información de Acceso -->
                  <div class="card card-action mb-6">
                    <div class="card-header align-items-center">
                      <h5 class="card-action-title mb-0">
                        <i class="icon-base ri ri-lock-line icon-24px text-body me-4"></i>Información de Acceso
                      </h5>
                    </div>
                    <div class="card-body pt-5">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-4">
                            <h6 class="fw-medium mb-2">Nombre de Usuario</h6>
                            <p class="text-body-secondary"><?php echo isset($usuario['usuario']) ? htmlspecialchars($usuario['usuario']) : 'N/A'; ?></p>
                          </div>
                          <div class="mb-4">
                            <h6 class="fw-medium mb-2">Estado de Acceso</h6>
                            <span class="badge bg-label-<?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'true') ? 'success' : 'danger'; ?> me-2 ms-2 rounded-pill">
                              <?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'true') ? 'Habilitado' : 'Deshabilitado'; ?>
                            </span>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-4">
                            <h6 class="fw-medium mb-2">Nivel de Privilegio</h6>
                            <p class="text-body-secondary"><?php echo isset($usuario['nombre_privilegio']) ? htmlspecialchars($usuario['nombre_privilegio']) : 'N/A'; ?></p>
                          </div>
                          <div class="mb-4">
                            <h6 class="fw-medium mb-2">Estado de Conexión</h6>
                            <span class="badge bg-label-<?php echo ($estado_conexion == 'Conectado') ? 'success' : 'secondary'; ?> me-2 ms-2 rounded-pill">
                              <i class="icon-base ri <?php echo ($estado_conexion == 'Conectado') ? 'ri-wifi-line' : 'ri-wifi-off-line'; ?> me-1"></i>
                              <?php echo $estado_conexion; ?>
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!--/ Información de Acceso -->
                  
                  <!-- Historial de Actividad -->
                  <div class="card card-action mb-6">
                    <div class="card-header align-items-center">
                      <h5 class="card-action-title mb-0">
                        <i class="icon-base ri ri-time-line icon-24px text-body me-4"></i>Historial de Actividad
                      </h5>
                    </div>
                    <div class="card-body pt-5">
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-4">
                            <h6 class="fw-medium mb-2">Fecha de Alta</h6>
                            <p class="text-body-secondary"><?php echo isset($usuario['fecAlta']) ? date('d/m/Y', strtotime($usuario['fecAlta'])) : 'N/A'; ?></p>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-4">
                            <h6 class="fw-medium mb-2">Último Acceso</h6>
                            <p class="text-body-secondary"><?php echo isset($usuario['ultimo_acceso']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'N/A'; ?></p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <!--/ Historial de Actividad -->
                </div>
              </div>
              <!--/ User Profile Content -->
                </div>
                <!--/ Tab Perfil -->

                <!-- Tab Actividad -->
                <div class="tab-pane fade" id="navs-pills-top-actividad" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <div class="card">
                        <div class="card-header border-bottom card-header-forms">
                          <h5 class="card-title mb-0">
                            <i class="icon-base ri ri-time-line icon-24px text-body me-2"></i>Actividad del Usuario
                          </h5>
                        </div>
                        <div class="card-datatable table-responsive">
                          <table class="datatables-usuario-acciones table border-top">
                            <thead>
                              <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                                <th>IP</th>
                                <th>URL</th>
                                <th>Item</th>
                              </tr>
                            </thead>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Actividad -->

                <!-- Tab Conexiones -->
                <div class="tab-pane fade" id="navs-pills-top-conexiones" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                      <div class="card mb-6">
                        <div class="card-header">
                          <h5 class="card-title mb-0">
                            <i class="icon-base ri ri-wifi-line icon-24px text-body me-2"></i>Estado de Conexiones
                          </h5>
                        </div>
                        <div class="card-body">
                          <div class="row">
                            <div class="col-md-6">
                              <div class="card bg-light">
                                <div class="card-body text-center">
                                  <i class="icon-base ri <?php echo ($estado_conexion == 'Conectado') ? 'ri-wifi-line text-success' : 'ri-wifi-off-line text-secondary'; ?> icon-48px mb-3"></i>
                                  <h6>Estado Actual</h6>
                                  <span class="badge bg-label-<?php echo ($estado_conexion == 'Conectado') ? 'success' : 'secondary'; ?> fs-6">
                                    <?php echo $estado_conexion; ?>
                                  </span>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="card bg-light">
                                <div class="card-body text-center">
                                  <i class="icon-base ri ri-time-line icon-48px text-primary mb-3"></i>
                                  <h6>Último Acceso</h6>
                                  <p class="mb-0"><?php echo isset($usuario['ultimo_acceso']) ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'N/A'; ?></p>
                                </div>
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
                        <div class="card-header border-bottom card-header-forms">
                          <h5 class="card-title mb-0">
                            <i class="icon-base ri ri-history-line icon-24px text-body me-2"></i>Historial de Conexiones
                          </h5>
                        </div>
                        <div class="card-datatable table-responsive">
                          <table class="datatables-usuario-conexiones table border-top">
                            <thead>
                              <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>IP</th>
                                <th>User Agent</th>
                                <th>Ubicación</th>
                                <th>Token</th>
                              </tr>
                            </thead>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Conexiones -->

                <!-- Tab Acceso personalizado -->
                <div class="tab-pane fade" id="navs-pills-top-accesocustom" role="tabpanel">
                  <input type="hidden" name="id_jerarquia_usuario" id="id_jerarquia_usuario" value="<?php echo (int) ($usuario['privilegio_usuario'] ?? 0); ?>">
                  <input type="hidden" name="id_usuario_permisos" id="id_usuario_permisos" value="<?php echo (int) ($usuario['id_usuario'] ?? 0); ?>">
                  <div class="row">
                    <div class="col-12">
                      <div class="card mb-4">
                        <div class="card-header border-bottom card-header-forms">
                          <div>
                            <h5 class="card-title mb-0">
                              <i class="icon-base ri ri-admin-line icon-24px text-body me-2"></i>Accesos de la jerarquía
                            </h5>
                            <small class="text-muted">
                              Configuración heredada de
                              <strong><?php echo htmlspecialchars($usuario['nombre_privilegio'] ?? 'Sin jerarquía'); ?></strong>
                              <span id="usuario-jerarquia-section-badge" class="badge bg-label-primary ms-1 d-none"></span>
                            </small>
                            <small class="text-muted d-block mt-1">
                              <span class="d-inline-flex align-items-center me-3">
                                <span class="permiso-leyenda-normal d-inline-block me-1"></span> Permiso de jerarquía (solo lectura)
                              </span>
                              <span class="d-inline-flex align-items-center">
                                <span class="permiso-leyenda-solo-usuario d-inline-block me-1"></span> Acceso personalizado del usuario (editable)
                              </span>
                            </small>
                          </div>
                          <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <button type="button" class="btn btn-success btn-xs waves-effect waves-light btn-accion-lote button-actions-datatable" id="usuario-jerarquia-filtroActivas" data-filtro-estado="activas">
                              <span class="icon-base ri ri-checkbox-circle-fill icon-20px me-1"></span>Activas
                            </button>
                            <button type="button" class="btn btn-danger btn-xs waves-effect waves-light btn-accion-lote button-actions-datatable" id="usuario-jerarquia-filtroNoActivas" data-filtro-estado="no_activas">
                              <span class="icon-base ri ri-close-circle-fill icon-20px me-1"></span>No activas
                            </button>
                            <button type="button" class="btn btn-primary btn-xs waves-effect waves-light btn-accion-lote button-actions-datatable active" id="usuario-jerarquia-filtroTodos" data-filtro-estado="todos">
                              <span class="icon-base ri ri-list-check icon-20px me-1"></span>Todos
                            </button>
                          </div>
                        </div>
                        <div class="card-body" style="padding: 9px 18px 9px 5px;">
                          <div class="row align-items-center">
                            <div class="col-md-12">
                              <div class="input-group usuario-jerarquia-search-group">
                                <span class="input-group-text">
                                  <svg class="aa-SubmitIcon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                    <path d="M16.041 15.856c-0.034 0.026-0.067 0.055-0.099 0.087s-0.060 0.064-0.087 0.099c-1.258 1.213-2.969 1.958-4.855 1.958-1.933 0-3.682-0.782-4.95-2.050s-2.050-3.017-2.050-4.95 0.782-3.682 2.050-4.95 3.017-2.050 4.95-2.050 3.682 0.782 4.95 2.050 2.050 3.017 2.050 4.95c0 1.886-0.745 3.597-1.959 4.856zM21.707 20.293l-3.675-3.675c1.231-1.54 1.968-3.493 1.968-5.618 0-2.485-1.008-4.736-2.636-6.364s-3.879-2.636-6.364-2.636-4.736 1.008-6.364 2.636-2.636 3.879-2.636 6.364 1.008 4.736 2.636 6.364 3.879 2.636 6.364 2.636c2.125 0 4.078-0.737 5.618-1.968l3.675 3.675c0.391 0.391 1.024 0.391 1.414 0s0.391-1.024 0-1.414z"></path>
                                  </svg>
                                </span>
                                <input type="text" class="form-control usuario-jerarquia-search-items" id="usuario-jerarquia-search" placeholder="Buscar items o elementos DOM por nombre, URL, ID..." autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="usuario-jerarquia-clear-search" style="display: none;">
                                  <i class="ri ri-close-line"></i>
                                </button>
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
                        <div class="card-body px-0 py-4">
                          <form id="formUsuarioJerarquiaPermisos" class="row g-3" onsubmit="return false">
                            <div class="col-12 m-0">
                              <div id="usuario-jerarquia-items-container">
                                <div class="text-center py-4">
                                  <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                  </div>
                                  <p class="mt-2">Cargando permisos de la jerarquía...</p>
                                </div>
                              </div>
                              <div id="usuario-jerarquia-elements-container" class="border-top pt-4 mt-4 d-none">
                                <h6 class="fw-medium mb-3 px-3">
                                  <i class="icon-base ri ri-cursor-line icon-20px text-body me-1"></i>
                                  Elementos DOM (jerarquía y personalizados)
                                </h6>
                                <div id="usuario-jerarquia-elements-table-wrap">
                                  <div class="text-center py-3 text-muted small">Cargando elementos DOM...</div>
                                </div>
                              </div>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!--/ Tab Acceso personalizado -->

              </div>
              <!--/ Tab Content -->
  <?php endif; ?>
              </div><!-- / Content -->