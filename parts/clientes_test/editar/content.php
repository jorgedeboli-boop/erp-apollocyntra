<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
<?php
  $id_cliente = isset($_GET['id']) ? (int) $_GET['id'] : 0;

  if ($id_cliente) {
      $conexion = conectar_bd();

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
          <h4 class="card-title mb-0">Editar Cliente (test)</h4>
          <small class="text-muted">Formulario de cliente mediante módulo reutilizable</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='cliente.php?id=<?php echo (int) $id_cliente; ?>'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Cliente
          </button>
        </div>
        <div class="card-body mt-5">
          <div id="card-form-custom-id" class="card card-form-custom">
            <form id="formEditarClienteTest" method="POST" action="parts/modulo_cliente_form/unique/procesar_editar_cliente.php" class="fv-plugins-bootstrap5 fv-plugins-framework" autocomplete="off">

              <?php
              $modulo_cliente_form_modo = 'editar';
              require_once __DIR__ . '/../../modulo_cliente_form/unique/editar_cliente_modulo.php';
              ?>

              <div class="row mt-4">
                <div class="col-md-6 offset-md-6">
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

              <div class="row mt-4">
                <div class="col-md-12">
                  <h5 class="mb-3">Información Adicional</h5>
                  <div class="form-floating form-floating-outline mb-3">
                    <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Observaciones sobre el cliente" style="height: 100px" autocomplete="off"><?php echo isset($datos_cliente['observaciones']) ? htmlspecialchars($datos_cliente['observaciones']) : ''; ?></textarea>
                    <label for="observaciones">Observaciones</label>
                  </div>
                </div>
              </div>

              <hr class="my-4">

              <div class="d-flex justify-content-between">
                <a href="cliente.php?id=<?php echo (int) $id_cliente; ?>" class="btn btn-text-primary">
                  <i class="icon-base ri ri-arrow-left-line me-2"></i>
                  Volver a la ficha de cliente
                </a>
                <div>
                  <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                    <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                    Aguarde...
                  </button>
                  <button type="submit" class="btn btn-primary" id="btnEditarClienteTest">
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
