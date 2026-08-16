<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar Usuario</h4>
          <small class="text-muted">Modifique los datos del usuario en el sistema</small>
          <div class="d-flex flex-column align-items-end">
            <button type="button" class="btn btn-text-primary btn-header-card-right mb-2" onclick="window.location.href='usuarios.php'">
              <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Usuarios
            </button>
            <button type="button" class="btn btn-outline-warning" id="btnCambiarPassword" data-bs-toggle="modal" data-bs-target="#modalCambiarPassword">
              <i class="icon-base ri ri-lock-password-line me-2"></i>
              Cambiar Contraseña
            </button>
          </div>
        </div>
        <div class="card-body mt-4">
          <?php
          $es_usuario_root = (isset($usuario_root) && $usuario_root === 'true');
          $es_usuario_super_administrador = (isset($usuario_super_administrador) && $usuario_super_administrador === 'true');
          $puede_editar_campo_usuario_root = $es_usuario_root;

          // Cargar datos del usuario directamente en PHP
          $id_usuario = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          $acceso_denegado = false;
          
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
                      u.usuario_root,
                      u.super_admin
                  FROM usuarios u
                  WHERE u.id_usuario = ?
              ";
              
              $stmt_usuario = mysqli_prepare($conexion, $query_usuario);
              if ($stmt_usuario) {
                  mysqli_stmt_bind_param($stmt_usuario, 'i', $id_usuario);
                  mysqli_stmt_execute($stmt_usuario);
                  $result_usuario = mysqli_stmt_get_result($stmt_usuario);
                  
                  if ($result_usuario && mysqli_num_rows($result_usuario) > 0) {
                      $usuario = mysqli_fetch_assoc($result_usuario);
                      mysqli_stmt_close($stmt_usuario);

                      if (!$es_usuario_root && ($usuario['usuario_root'] ?? 'false') === 'true') {
                          $acceso_denegado = true;
                      }
                      if (!$es_usuario_root && !$es_usuario_super_administrador && ($usuario['super_admin'] ?? 'false') === 'true') {
                          $acceso_denegado = true;
                      }
                  } else {
                      echo '<div class="alert alert-danger">Usuario no encontrado</div>';
                      $usuario = null;
                      mysqli_stmt_close($stmt_usuario);
                  }
              } else {
                  echo '<div class="alert alert-danger">Usuario no encontrado</div>';
                  $usuario = null;
              }
              
              mysqli_close($conexion);
          } else {
              echo '<div class="alert alert-danger">ID de usuario no válido</div>';
              $usuario = null;
          }
          ?>

          <?php if ($acceso_denegado): ?>
          <div class="alert alert-danger mb-0">
            No tienes permisos para editar este usuario.
          </div>
          <?php elseif ($usuario): ?>
          <form id="formEditarUsuario" method="POST" action="parts/usuarios/editar/procesar_editar_usuario.php">
            <input type="hidden" id="id_usuario" name="id_usuario" value="<?php echo $id_usuario; ?>" />
            
            <div class="row">
              <!-- Información Personal -->
              <div class="col-md-6">
                <h5 class="mb-6">Información Personal</h5>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Nombre de usuario" value="<?php echo isset($usuario['usuario']) ? htmlspecialchars($usuario['usuario']) : ''; ?>" required />
                  <label for="usuario">Usuario *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" placeholder="Nombre" value="<?php echo isset($usuario['nombre_usuario']) ? htmlspecialchars($usuario['nombre_usuario']) : ''; ?>" required />
                  <label for="nombre_usuario">Nombre *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="text" class="form-control" id="apellido_usuario" name="apellido_usuario" placeholder="Apellido" value="<?php echo isset($usuario['apellido_usuario']) ? htmlspecialchars($usuario['apellido_usuario']) : ''; ?>" required />
                  <label for="apellido_usuario">Apellido *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="email" class="form-control" id="email" name="email" placeholder="usuario@ejemplo.com" value="<?php echo isset($usuario['email']) ? htmlspecialchars($usuario['email']) : ''; ?>" />
                  <label for="email">Email</label>
                </div>
              </div>
              
              <!-- Estado y Acceso -->
              <div class="col-md-6">
                <h5 class="mb-6">Estado y Acceso</h5>
                
                <label class="form-check-label">Acceso *</label>
                <div class="form-floating form-floating-outline mb-6 mt-3">
                  <div class="form-check form-check-inline">
                    <input name="estado_usuario" class="form-check-input" type="radio" value="true" id="estado_usuario_true" <?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'true') ? 'checked' : ''; ?> required />
                    <label class="form-check-label" for="estado_usuario_true">Habilitado</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input name="estado_usuario" class="form-check-input" type="radio" value="false" id="estado_usuario_false" <?php echo (isset($usuario['estado_usuario']) && $usuario['estado_usuario'] === 'false') ? 'checked' : ''; ?> required />
                    <label class="form-check-label" for="estado_usuario_false">Deshabilitado</label>
                  </div>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="tel" class="form-control" id="telefono_usuario" name="telefono_usuario" placeholder="+34 600 000 000" value="<?php echo isset($usuario['telefono_usuario']) ? htmlspecialchars($usuario['telefono_usuario']) : ''; ?>" />
                  <label for="telefono_usuario">Teléfono</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <select class="form-select select2" id="privilegio_usuario" name="privilegio_usuario" required>
                    <option value="">Seleccionar privilegio</option>
                    <?php
                    try {
                        $privilegios = obtener_privilegios();
                        if ($privilegios && is_array($privilegios)) {
                            foreach ($privilegios as $privilegio) {
                                $selected = (isset($usuario['privilegio_usuario']) && $usuario['privilegio_usuario'] == $privilegio['id_privilegios']) ? 'selected' : '';
                                echo '<option value="' . $privilegio['id_privilegios'] . '" ' . $selected . '>' . htmlspecialchars($privilegio['nombre_privilegio']) . '</option>';
                            }
                        }
                    } catch (Exception $e) {
                        echo '<option value="">Error al cargar privilegios</option>';
                    }
                    ?>
                  </select>
                  <label for="privilegio_usuario" class="select_label">Privilegio *</label>
                </div>

                <?php if ($puede_editar_campo_usuario_root): ?>
                <label class="form-check-label">Usuario Root</label>
                <div class="form-floating form-floating-outline mb-6 mt-3">
                  <div class="form-check form-check-inline">
                    <input name="usuario_root" class="form-check-input" type="radio" value="true" id="usuario_root_true" <?php echo (isset($usuario['usuario_root']) && $usuario['usuario_root'] === 'true') ? 'checked' : ''; ?> />
                    <label class="form-check-label" for="usuario_root_true">Sí</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input name="usuario_root" class="form-check-input" type="radio" value="false" id="usuario_root_false" <?php echo (!isset($usuario['usuario_root']) || $usuario['usuario_root'] !== 'true') ? 'checked' : ''; ?> />
                    <label class="form-check-label" for="usuario_root_false">No</label>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="row mt-4">
              <!-- Información Adicional -->
              <div class="col-12">
                <h5 class="mb-6">Información Adicional</h5>
                
                <div class="form-floating form-floating-outline mb-6">
                  <textarea class="form-control" id="observaciones_usuario" name="observaciones_usuario" placeholder="Observaciones sobre el usuario" style="height: 100px"><?php echo isset($usuario['observaciones_usuario']) ? htmlspecialchars($usuario['observaciones_usuario']) : ''; ?></textarea>
                  <label for="observaciones_usuario">Observaciones</label>
                </div>
              </div>
            </div>
            

            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="usuarios.php" class="btn btn-text-primary me-2">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la lista
              </a>
              
              <div>
                <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                  <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                  Aguarde...
                </button>
                <button type="submit" class="btn btn-primary" id="btnEditarUsuario">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Actualizar Usuario
                </button>
              </div>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($usuario) && !$acceso_denegado): ?>
<!-- Modal Cambiar Contraseña -->
<div class="modal fade" id="modalCambiarPassword" tabindex="-1" aria-labelledby="modalCambiarPasswordLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCambiarPasswordLabel">
          <i class="icon-base ri ri-lock-password-line me-2"></i>
          Cambiar Contraseña
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="formCambiarPassword">
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="icon-base ri ri-information-line me-2"></i>
            <strong>Nota:</strong> La nueva contraseña debe tener al menos 6 caracteres.
          </div>
          
          <!-- Campo oculto para el ID del usuario -->
          <input type="hidden" id="id_usuario_modal" name="id_usuario_modal" value="<?php echo $id_usuario; ?>" />
          
          <div class="form-floating form-floating-outline mb-4">
            <input type="password" class="form-control" id="nueva_password_modal" name="nueva_password_modal" placeholder="Nueva contraseña" required />
            <label for="nueva_password_modal">Nueva Contraseña *</label>
          </div>
          
          <div class="form-floating form-floating-outline mb-4">
            <input type="password" class="form-control" id="confirmar_password_modal" name="confirmar_password_modal" placeholder="Confirmar nueva contraseña" required />
            <label for="confirmar_password_modal">Confirmar Nueva Contraseña *</label>
          </div>
          
          <div class="alert alert-warning" id="passwordError" style="display: none;">
            <i class="icon-base ri ri-error-warning-line me-2"></i>
            <span id="passwordErrorMessage"></span>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="icon-base ri ri-close-line me-2"></i>
            Cancelar
          </button>
          <button type="submit" class="btn btn-warning" id="btnGuardarPassword">
            <i class="icon-base ri ri-check-line me-2"></i>
            Guardar Contraseña
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- / Content -->