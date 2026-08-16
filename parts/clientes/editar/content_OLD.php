<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar Cliente</h4>
          <small class="text-muted">Modifique los datos del cliente en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='clientes.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Clientes
          </button>
        </div>
        <div class="card-body mt-4">
          <?php
          // Cargar datos del cliente directamente en PHP
          $id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          
          // Debug simple
          //echo '<div class="alert alert-info">ID recibido: ' . $id_cliente . '</div>';
          //echo '<div class="alert alert-info">GET completo: ' . print_r($_GET, true) . '</div>';
          
          if ($id_cliente) {
              $conexion = conectar_bd();
              
              // Consulta para obtener datos del cliente
              $query_cliente = "
                  SELECT 
                      c.id_cliente,
                      c.nombre,
                      c.apellido,
                      c.tipo_identificacion,
                      c.tipo_identificacion_id,
                      c.identificacion,
                      c.nacionalidad,
                      c.nacionalidad_id,
                      c.telefono,
                      c.f_alta
                  FROM clientes c
                  WHERE c.id_cliente = ?
              ";
              
              $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
              mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
              mysqli_stmt_execute($stmt_cliente);
              $result_cliente = mysqli_stmt_get_result($stmt_cliente);
              
              if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {
                  $cliente = mysqli_fetch_assoc($result_cliente);
                  
                  // Consulta para obtener datos adicionales del cliente
                  $query_datos = "
                      SELECT 
                          dc.direccion,
                          dc.c_provincia,
                          dc.c_poblacion,
                          dc.codigo_postal,
                          dc.email,
                          dc.observaciones,
                          dc.sexo,
                          dc.f_nacimiento,
                          dc.f_vencimiento,
                          dc.id_nacionalidad_rel,
                          dc.rel_id_pais,
                          dc.rel_id_provincia,
                          dc.rel_id_poblacion
                      FROM datos_clientes dc
                      WHERE dc.rel_id_cliente = ?
                  ";
                  
                  $stmt_datos = mysqli_prepare($conexion, $query_datos);
                  mysqli_stmt_bind_param($stmt_datos, 'i', $id_cliente);
                  mysqli_stmt_execute($stmt_datos);
                  $result_datos = mysqli_stmt_get_result($stmt_datos);
                  
                  $datos_cliente = null;
                  if ($result_datos && mysqli_num_rows($result_datos) > 0) {
                      $datos_cliente = mysqli_fetch_assoc($result_datos);
                  }
                  
                  mysqli_stmt_close($stmt_datos);
                  mysqli_stmt_close($stmt_cliente);
              } else {
                $texto_action_user = "Usuario intenta editar un cliente que no existe";
                registrar_accion_usuario_not_access_id($texto_action_user, $id_type_Item);
                $redirigir_dashboard = "true";
              }
              
              mysqli_close($conexion);
          } else {
              $texto_action_user = "Usuario intenta editar un cliente sin ID cliente";
              registrar_accion_usuario_not_access_id($texto_action_user, $id_type_Item);
              $redirigir_dashboard = "true";
          }
          ?>
          
          <form id="formEditarCliente" method="POST" action="parts/clientes/editar/procesar_editar_cliente.php">
            <input type="hidden" id="id_cliente" name="id_cliente" value="<?php echo $id_cliente; ?>" />
            
            <div class="row">
              <!-- Información Personal -->
              <div class="col-md-6">
                <h5 class="mb-6">Información Personal</h5>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" value="<?php echo isset($cliente['nombre']) ? htmlspecialchars($cliente['nombre']) : ''; ?>" required />
                  <label for="nombre">Nombre *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido" value="<?php echo isset($cliente['apellido']) ? htmlspecialchars($cliente['apellido']) : ''; ?>" required />
                  <label for="apellido">Apellido *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6 mt-5">
                  <input type="date" class="form-control" id="f_nacimiento" name="f_nacimiento" value="<?php echo isset($datos_cliente['f_nacimiento']) ? $datos_cliente['f_nacimiento'] : ''; ?>" required />
                  <label for="f_nacimiento">Fecha de Nacimiento *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <select class="form-select" id="sexo" name="sexo" required>
                    <option value="MASCULINO" <?php echo (isset($datos_cliente['sexo']) && $datos_cliente['sexo'] == 'MASCULINO') ? 'selected' : ''; ?>>Masculino</option>
                    <option value="FEMENINO" <?php echo (isset($datos_cliente['sexo']) && $datos_cliente['sexo'] == 'FEMENINO') ? 'selected' : ''; ?>>Femenino</option>
                  </select>
                  <label for="sexo">Sexo *</label>
                </div>
              </div>
              
              <!-- Identificación -->
              <div class="col-md-6">
                <h5 class="mb-6">Identificación</h5>
                
                <div class="form-floating form-floating-outline mb-5 mt-7">
                  
                  <labe<?php
                  $tipo_identificacion = $cliente['tipo_identificacion_id'];
                  generarSelectTipoIdentificacion($tipo_identificacion, $app_country_id);
                  ?>l for="tipo_identificacion" class="select_label">Tipo de Identificación *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="text" class="form-control" id="identificacion" name="identificacion" placeholder="Número de identificación" value="<?php echo isset($cliente['identificacion']) ? htmlspecialchars($cliente['identificacion']) : ''; ?>" required />
                  <label for="identificacion">Número de Identificación *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6 mt-7">
                  <?php
                  generarSelectNacionalidades($cliente['nacionalidad_id'], true);
                  ?>
                  <label for="nacionalidad" class="select_label">Nacionalidad *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6">
                  <input type="date" class="form-control" id="f_vencimiento" name="f_vencimiento" value="<?php echo isset($datos_cliente['f_vencimiento']) ? $datos_cliente['f_vencimiento'] : ''; ?>" required />
                  <label for="f_vencimiento">Fecha vencimiento identificación *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-6 mt-5">
                  <input type="date" class="form-control" id="f_alta" name="f_alta" value="<?php echo isset($cliente['f_alta']) ? $cliente['f_alta'] : ''; ?>" readonly />
                  <label for="f_alta">Fecha de Alta</label>
                </div>
              </div>
            </div>
            
            <div class="row mt-4">
              <!-- Dirección -->
              <div class="col-md-6">
                <h5 class="mb-3">Dirección</h5>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Calle, número, piso..." value="<?php echo isset($datos_cliente['direccion']) ? htmlspecialchars($datos_cliente['direccion']) : ''; ?>" required />
                  <label for="direccion">Dirección *</label>
                </div>
                
                <div class="mb-3">
                  <label for="pais" class="form-label">País *</label>
                  <select class="form-select select2" id="pais" name="pais" required>
                    <option value="">Seleccionar país</option>
                    <?php
                    // Si existe el país, cargarlo
                    if (isset($datos_cliente['rel_id_pais']) && $datos_cliente['rel_id_pais']) {
                        $conexion_pais = conectar_bd();
                        $query_pais = "SELECT id_country, name_spanish FROM countrys WHERE id_country = ?";
                        $stmt_pais = mysqli_prepare($conexion_pais, $query_pais);
                        mysqli_stmt_bind_param($stmt_pais, 'i', $datos_cliente['rel_id_pais']);
                        mysqli_stmt_execute($stmt_pais);
                        $result_pais = mysqli_stmt_get_result($stmt_pais);
                        if ($row_pais = mysqli_fetch_assoc($result_pais)) {
                            echo '<option value="' . $row_pais['id_country'] . '" selected>' . htmlspecialchars($row_pais['name_spanish']) . '</option>';
                        }
                        mysqli_stmt_close($stmt_pais);
                        mysqli_close($conexion_pais);
                    }
                    ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="c_provincia" class="form-label">Provincia *</label>
                  <select class="form-select select2" id="c_provincia" name="c_provincia" required>
                    <option value="">Seleccionar provincia</option>
                    <?php
                    // Si existe la provincia, cargarla
                    if (isset($datos_cliente['rel_id_provincia']) && $datos_cliente['rel_id_provincia']) {
                        $conexion_prov = conectar_bd();
                        $query_prov = "SELECT id_province, nombreProvince FROM provincias WHERE id_province = ?";
                        $stmt_prov = mysqli_prepare($conexion_prov, $query_prov);
                        mysqli_stmt_bind_param($stmt_prov, 'i', $datos_cliente['rel_id_provincia']);
                        mysqli_stmt_execute($stmt_prov);
                        $result_prov = mysqli_stmt_get_result($stmt_prov);
                        if ($row_prov = mysqli_fetch_assoc($result_prov)) {
                            echo '<option value="' . $row_prov['id_province'] . '" selected>' . htmlspecialchars($row_prov['nombreProvince']) . '</option>';
                        }
                        mysqli_stmt_close($stmt_prov);
                        mysqli_close($conexion_prov);
                    }
                    ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="c_poblacion" class="form-label">Población *</label>
                  <select class="form-select select2" id="c_poblacion" name="c_poblacion" required>
                    <option value="">Seleccionar población</option>
                    <?php
                    // Si existe la población, cargarla
                    if (isset($datos_cliente['rel_id_poblacion']) && $datos_cliente['rel_id_poblacion']) {
                        $conexion_pob = conectar_bd();
                        $query_pob = "SELECT idpoblacion, poblacion FROM poblacion WHERE idpoblacion = ?";
                        $stmt_pob = mysqli_prepare($conexion_pob, $query_pob);
                        mysqli_stmt_bind_param($stmt_pob, 'i', $datos_cliente['rel_id_poblacion']);
                        mysqli_stmt_execute($stmt_pob);
                        $result_pob = mysqli_stmt_get_result($stmt_pob);
                        if ($row_pob = mysqli_fetch_assoc($result_pob)) {
                            echo '<option value="' . $row_pob['idpoblacion'] . '" selected>' . htmlspecialchars($row_pob['poblacion']) . '</option>';
                        }
                        mysqli_stmt_close($stmt_pob);
                        mysqli_close($conexion_pob);
                    }
                    ?>
                  </select>
                </div>
                                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Código postal" maxlength="5" value="<?php echo isset($datos_cliente['codigo_postal']) ? htmlspecialchars($datos_cliente['codigo_postal']) : ''; ?>" readonly />
                  <label for="codigo_postal">Código Postal *</label>
                </div>
              </div>
              
              <!-- Información de Contacto -->
              <div class="col-md-6">
                <h5 class="mb-3">Información de Contacto</h5>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="+34 600 000 000" value="<?php echo isset($cliente['telefono']) ? htmlspecialchars($cliente['telefono']) : ''; ?>" required />
                  <label for="telefono">Teléfono *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="email" class="form-control" id="email" name="email" placeholder="cliente@ejemplo.com" value="<?php echo isset($datos_cliente['email']) ? htmlspecialchars($datos_cliente['email']) : ''; ?>" />
                  <label for="email">Email</label>
                </div>
              </div>
            </div>
            
            <div class="row mt-4">
              
              <!-- Información Adicional -->
              <div class="col-md-6">
                <h5 class="mb-6">Información Adicional</h5>
                
                <div class="form-floating form-floating-outline mb-6">
                  <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Observaciones sobre el cliente" style="height: 100px"><?php echo isset($datos_cliente['observaciones']) ? htmlspecialchars($datos_cliente['observaciones']) : ''; ?></textarea>
                  <label for="observaciones">Observaciones</label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
             
              <a href="cliente.php?id=<?php echo $id_cliente; ?>" class="btn btn-text-primary">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la ficha de cliente
              </a>
              
              <div>
                <!--<button type="reset" class="btn btn-text-danger me-2">
                  <i class="icon-base ri ri-refresh-line me-2"></i>
                  Restaurar
                </button>-->
                <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                  <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                  Aguarde...
                </button>
                <button type="submit" class="btn btn-primary" id="btnEditarCliente">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Actualizar Cliente
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