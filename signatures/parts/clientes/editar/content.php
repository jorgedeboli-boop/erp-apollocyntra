<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
<?php
  // Cargar datos del cliente
  $id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
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
                          c.sucursal,
                          c.estado,
                          c.f_alta,
                          c.delete_state,
                          s.nombre_sucursal
                      FROM clientes c
                      LEFT JOIN sucursal s ON c.sucursal = s.id_sucursal
                      WHERE c.id_cliente = ?
                  ";
      
      $stmt_cliente = mysqli_prepare($conexion, $query_cliente);
      mysqli_stmt_bind_param($stmt_cliente, 'i', $id_cliente);
      mysqli_stmt_execute($stmt_cliente);
      $result_cliente = mysqli_stmt_get_result($stmt_cliente);
      
      if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {
          $cliente = mysqli_fetch_assoc($result_cliente);
          $delete_state = $cliente['delete_state'];
          if ($delete_state == 'true') {
            $texto_action_user = "Usuario intenta editar el cliente Nº '$id_cliente' eliminado";
            registrar_accion_usuario_not_access_id($texto_action_user, $id_type_Item);
            $cliente = null;
            $datos_cliente = null;
            $direccion_cliente = null;
            mysqli_close($conexion);
            header('Location: clientes.php');
            exit;
          }
          // Consulta para obtener datos adicionales del cliente
          $query_datos = "
              SELECT 
                  dc.id_datos_cliente,
                  dc.rel_id_cliente,
                  dc.f_nacimiento,
                  dc.movil,
                  dc.email,
                  dc.observaciones,
                  dc.publicidad,
                  dc.sexo,
                  dc.f_vencimiento,
                  dc.firma_cliente
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
          
          // Consulta para obtener direcciones del cliente
          $query_direcciones = "
              SELECT 
                  d.id_direcciones,
                  d.direccion,
                  d.c_provincia,
                  d.c_poblacion,
                  d.c_pais,
                  d.codigo_postal,
                  d.observaciones_direccion,
                  d.rel_id_provincia,
                  d.rel_id_pais,
                  d.rel_id_poblacion
              FROM direcciones d
              WHERE d.rel_id_item = ? AND d.type_direccion = 'clientes'
              LIMIT 1
          ";
          
          $stmt_direccion = mysqli_prepare($conexion, $query_direcciones);
          mysqli_stmt_bind_param($stmt_direccion, 'i', $id_cliente);
          mysqli_stmt_execute($stmt_direccion);
          $result_direccion = mysqli_stmt_get_result($stmt_direccion);
          
          $direccion_cliente = null;
          if ($result_direccion && mysqli_num_rows($result_direccion) > 0) {
              $direccion_cliente = mysqli_fetch_assoc($result_direccion);
          }
          
          mysqli_stmt_close($stmt_direccion);
          mysqli_stmt_close($stmt_cliente);
      } else {
          echo '<div class="alert alert-danger">Cliente no encontrado</div>';
          $cliente = null;
          $datos_cliente = null;
          $direccion_cliente = null;
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de cliente no válido</div>';
      $cliente = null;
      $datos_cliente = null;
      $direccion_cliente = null;
  }
  ?>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Actualizar Cliente</h4>
          <small class="text-muted">Complete el formulario para actualizar un cliente en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='cliente.php?id=<?php echo (int) $id_cliente; ?>'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Cliente
          </button>
        </div>
        <div class="card-body mt-5">
          <div id="card-form-custom-id" class="card card-form-custom">
          <form id="formEditarCliente" method="POST" action="parts/clientes/editar/procesar_editar_cliente.php" class="fv-plugins-bootstrap5 fv-plugins-framework" autocomplete="off">
            <input type="hidden" id="id_cliente" name="id_cliente" value="<?php echo $id_cliente; ?>" />
            <?php
            $tipo_identificacion = isset($cliente['tipo_identificacion_id']) ? $cliente['tipo_identificacion_id'] : '';
            $tiene_tipo_identificacion = !empty($tipo_identificacion);
            ?>
            <div id="datos_cliente_identificacion" style="margin-top: 0px;">
              <div class="row">
                <h5 class="mb-3">Identificación</h5>
                <div class="col-md-6">
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectTipoIdentificacion($tipo_identificacion, $app_country_id, true); ?>
                    <label for="tipo_identificacion">Tipo de Identificación *</label>
                  </div>
                </div>
                <div class="col-md-6 form-control-validation">
                  <div class="input-group input-group-merge mb-4 inputgroupidentificacion<?php echo $tiene_tipo_identificacion ? '' : ' disabled'; ?>">
                    <div class="form-floating form-floating-outline flex-grow-1">
                      <input type="text" class="form-control" id="identificacion" name="identificacion" placeholder="<?php echo $tiene_tipo_identificacion ? 'Número de identificación' : 'Primero seleccione el tipo de identificación'; ?>" value="<?php echo isset($cliente['identificacion']) ? htmlspecialchars($cliente['identificacion']) : ''; ?>" required autocomplete="off" aria-describedby="btn_comprobar_identificacion"<?php echo $tiene_tipo_identificacion ? '' : ' disabled'; ?> />
                      <label for="identificacion">Número de Identificación *</label>
                    </div>
                    <span class="input-group-text cursor-pointer p-1">
                      <button type="button" class="btn btn-primary waves-effect waves-light" id="btn_comprobar_identificacion"<?php echo $tiene_tipo_identificacion ? '' : ' disabled'; ?>>Comprobar</button>
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div id="datos_cliente" class="<?php echo $tiene_tipo_identificacion ? '' : 'formulario-borroso'; ?>" style="margin-top: 15px;">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-floating form-floating-outline mb-4">
                    <?php generarSelectNacionalidades(isset($cliente['nacionalidad_id']) ? $cliente['nacionalidad_id'] : '', true); ?>
                    <label for="nacionalidad">Nacionalidad *</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating form-floating-outline mb-3">
                    <input type="date" class="form-control" id="f_vencimiento" name="f_vencimiento" value="<?php echo isset($datos_cliente['f_vencimiento']) ? $datos_cliente['f_vencimiento'] : ''; ?>" required autocomplete="off" />
                    <label for="f_vencimiento">Fecha vencimiento identificación *</label>
                  </div>
                </div>
              </div>
              <div class="row">
                <h5 class="mb-3">Información Personal</h5>
                <div class="col-md-6">
                  <div class="mb-4 form-floating form-floating-outline">
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Nombre" value="<?php echo isset($cliente['nombre']) ? htmlspecialchars($cliente['nombre']) : ''; ?>" required autocomplete="off" />
                    <label for="nombre" class="form-label">Nombre *</label>
                  </div>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="text" class="form-control" id="apellido" name="apellido" placeholder="Apellido" value="<?php echo isset($cliente['apellido']) ? htmlspecialchars($cliente['apellido']) : ''; ?>" required autocomplete="off" />
                    <label for="apellido" class="form-label">Apellido *</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="date" class="form-control" id="f_nacimiento" name="f_nacimiento" value="<?php echo isset($datos_cliente['f_nacimiento']) ? $datos_cliente['f_nacimiento'] : ''; ?>" required autocomplete="off" />
                    <label for="f_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                  </div>
                  <div class="form-floating form-floating-outline mb-4">
                    <select class="form-select select2" id="sexo" name="sexo" required autocomplete="off">
                      <option value="">Seleccionar...</option>
                      <option value="MASCULINO" <?php echo (isset($datos_cliente['sexo']) && $datos_cliente['sexo'] == 'MASCULINO') ? 'selected' : ''; ?>>Masculino</option>
                      <option value="FEMENINO" <?php echo (isset($datos_cliente['sexo']) && $datos_cliente['sexo'] == 'FEMENINO') ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                    <label for="sexo" class="form-label">Sexo *</label>
                  </div>
                </div>
              </div>
              <div class="row mt-4">
                <div class="col-md-6" id="container_direccion">
                  <?php require_once 'parts/universal/direcciones/formulario_direccion_edit.php'; ?>
                </div>
                <div class="col-md-6">
                  <h5 class="mb-3">Información de Contacto</h5>
                  <div class="form-floating form-floating-outline mb-4">
                    <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="0034600000000" value="<?php echo isset($cliente['telefono']) ? htmlspecialchars($cliente['telefono']) : ''; ?>" required autocomplete="off" />
                    <label for="telefono">Teléfono *</label>
                  </div>
                  <div class="form-floating form-floating-outline mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="cliente@ejemplo.com" value="<?php echo isset($datos_cliente['email']) ? htmlspecialchars($datos_cliente['email']) : ''; ?>" autocomplete="off" />
                    <label for="email">Email</label>
                  </div>
                  <div class="mt-5">
                    <h5 class="mb-3">Sucursal</h5>
                    <div class="form-floating form-floating-outline mb-3">
                      <?php
                      $sucursal_seleccionada = isset($cliente['sucursal']) ? $cliente['sucursal'] : 0;
                      generarSelectSucursales($sucursal_seleccionada, 'sucursal', 'sucursal', true);
                      ?>
                      <label for="sucursal">Sucursal *</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row mt-4">
                <div class="col-md-12">
                  <h5 class="mb-3">Información Adicional</h5>
                  <div class="form-floating form-floating-outline mb-3">
                    <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Observaciones sobre el cliente" style="height: 100px" autocomplete="off"><?php echo isset($datos_cliente['observaciones']) ? htmlspecialchars($datos_cliente['observaciones']) : ''; ?></textarea>
                    <label for="observaciones">Observaciones</label>
                  </div>
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
</div>
<!-- / Content -->