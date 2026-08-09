<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Crear Nuevo Usuario</h4>
          <small class="text-muted">Complete el formulario para registrar un nuevo usuario en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='usuarios.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Usuarios
          </button>
        </div>
        <div class="card-body mt-4">
          <form id="formNuevoUsuario" method="POST" action="parts/usuarios/crear/procesar_nuevo_usuario.php">
            <div class="row">
              <!-- Información Personal -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Personal</h5>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" placeholder="Nombre" required />
                  <label for="nombre_usuario">Nombre *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="apellido_usuario" name="apellido_usuario" placeholder="Apellido" required />
                  <label for="apellido_usuario">Apellido *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="email" class="form-control" id="email_usuario" name="email_usuario" placeholder="correo@ejemplo.com" required />
                  <label for="email_usuario">Email *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="tel" class="form-control" id="telefono_usuario" name="telefono_usuario" placeholder="+34 123 456 789" />
                  <label for="telefono_usuario">Teléfono</label>
                </div>
              </div>
              
              <!-- Información de Acceso -->
              <div class="col-md-6">
                <h5 class="mb-3">Información de Acceso</h5>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="usuario_login" name="usuario_login" placeholder="usuario123" required />
                  <label for="usuario_login">Usuario de Login *</label>
                  <small class="form-text text-muted">Nombre de usuario único para acceder al sistema</small>
                </div>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="password" class="form-control" id="password_usuario" name="password_usuario" placeholder="Contraseña" required />
                  <label for="password_usuario">Contraseña *</label>
                  <small class="form-text text-muted">Mínimo 8 caracteres</small>
                </div>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" placeholder="Confirmar contraseña" required />
                  <label for="confirmar_password">Confirmar Contraseña *</label>
                </div>
              </div>
            </div>
            
            <div class="row mt-4">
              <!-- Configuración del Sistema -->
              <div class="col-md-6">
                <h5 class="mb-3">Configuración del Sistema</h5>
                
                <div class="form-floating form-floating-outline mb-3">
                  <select class="form-select select2" id="privilegio_usuario" name="privilegio_usuario" required>
                    <option value="">Seleccionar privilegio</option>
                    <?php
                    $privilegios = obtener_privilegios();
                    foreach ($privilegios as $privilegio) {
                        echo '<option value="' . $privilegio['id_privilegios'] . '">' . htmlspecialchars($privilegio['nombre_privilegio']) . '</option>';
                    }
                    ?>
                  </select>
                  <label for="privilegio_usuario">Privilegio *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-3">
                  <select class="form-select select2" id="sucursal_usuario" name="sucursal_usuario" required>
                    <option value="">Seleccionar sucursal</option>
                    <?php
                    $sucursales = obtener_sucursales();
                    foreach ($sucursales as $sucursal) {
                        echo '<option value="' . $sucursal['id_sucursal'] . '">' . htmlspecialchars($sucursal['nombre_sucursal']) . '</option>';
                    }
                    ?>
                  </select>
                  <label for="sucursal_usuario">Sucursal *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-3">
                  <select class="form-select" id="estado_usuario" name="estado_usuario" required>
                    <option value="true">Activo</option>
                    <option value="false">Inactivo</option>
                  </select>
                  <label for="estado_usuario">Estado *</label>
                </div>
              </div>
              
              <!-- Información Adicional -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Adicional</h5>
                
                <div class="form-floating form-floating-outline mb-3">
                  <textarea class="form-control" id="observaciones_usuario" name="observaciones_usuario" placeholder="Observaciones sobre el usuario" style="height: 100px"></textarea>
                  <label for="observaciones_usuario">Observaciones</label>
                </div>
                
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" id="enviar_credenciales" name="enviar_credenciales" checked />
                  <label class="form-check-label" for="enviar_credenciales">
                    Enviar credenciales por email
                  </label>
                </div>
                
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" id="forzar_cambio_password" name="forzar_cambio_password" />
                  <label class="form-check-label" for="forzar_cambio_password">
                    Forzar cambio de contraseña en primer login
                  </label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="../../../usuarios.php" class="btn btn-text-primary me-2">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la lista
              </a>
              
              <div>
                <button type="reset" class="btn btn-outline-danger me-2" >
                  <i class="icon-base ri ri-refresh-line me-2"></i>
                  Limpiar
                </button>
                <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                      <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                      Aguarde...
                    </button>
                <button type="submit" class="btn btn-primary">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Crear Usuario
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
