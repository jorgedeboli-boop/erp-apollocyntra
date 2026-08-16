<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar Gasto</h4>
          <small class="text-muted">Modifique los datos del gasto en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='gastos.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Gastos
          </button>
        </div>
        <div class="card-body mt-4">
          <?php
          // Cargar datos del gasto directamente en PHP
          $id_gasto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          
          if ($id_gasto) {
              $conexion = conectar_bd();
              
              // Consulta para obtener datos del gasto con JOINs
              $query_gasto = "
                  SELECT 
                      g.id_gasto,
                      g.fecha_gasto,
                      g.descripcion_gasto,
                      g.total_gasto,
                      g.estado_gasto,
                      g.empresa_gasto,
                      g.proveedor_gasto,
                      g.tipo_de_gasto,
                      g.forma_pago_gasto,
                      g.numero_factura_proveedor,
                      g.observaciones_gasto,
                      e.nombre_empresa,
                      p.nombre_proveedor,
                      tg.nombre_tipo_gasto,
                      fp.nombre_forma_de_pago
                  FROM gastos g
                  LEFT JOIN empresas e ON g.empresa_gasto = e.id_empresa
                  LEFT JOIN proveedores p ON g.proveedor_gasto = p.id_proveedor
                  LEFT JOIN tipo_de_gasto tg ON g.tipo_de_gasto = tg.id_tipo_gasto
                  LEFT JOIN formas_de_pago fp ON g.forma_pago_gasto = fp.id_forma_de_pago
                  WHERE g.id_gasto = ?
              ";
              
              $stmt_gasto = mysqli_prepare($conexion, $query_gasto);
              mysqli_stmt_bind_param($stmt_gasto, 'i', $id_gasto);
              mysqli_stmt_execute($stmt_gasto);
              $result_gasto = mysqli_stmt_get_result($stmt_gasto);
              
              if ($result_gasto && mysqli_num_rows($result_gasto) > 0) {
                  $gasto = mysqli_fetch_assoc($result_gasto);
                  mysqli_stmt_close($stmt_gasto);
              } else {
                  echo '<div class="alert alert-danger">Gasto no encontrado (ID: ' . $id_gasto . ')</div>';
                  $gasto = null;
              }
              
              mysqli_close($conexion);
          } else {
              echo '<div class="alert alert-danger">ID de gasto no válido</div>';
              $gasto = null;
          }
          ?>
          
          <form id="formEditarGasto" method="POST" action="parts/gastos/editar/procesar_editar_gasto.php">
            <input type="hidden" id="id_gasto" name="id_gasto" value="<?php echo $id_gasto; ?>" />
            
            <div class="row">
              <!-- Detalles del Gasto -->
              <div class="col-md-6">
                <h5 class="mb-3">Detalles del Gasto</h5>
                
                <div class="form-floating form-floating-outline mb-4">
                  <input type="date" class="form-control" id="fecha_gasto" name="fecha_gasto" value="<?php echo htmlspecialchars($gasto['fecha_gasto'] ?? ''); ?>" required />
                  <label for="fecha_gasto">Fecha del Gasto *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-4">
                  <textarea class="form-control" id="descripcion_gasto" name="descripcion_gasto" placeholder="Descripción del gasto" style="height: 100px" required><?php echo htmlspecialchars($gasto['descripcion_gasto'] ?? ''); ?></textarea>
                  <label for="descripcion_gasto">Descripción *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-4">
                  <input type="number" class="form-control" id="total_gasto" name="total_gasto" placeholder="0.00" step="0.01" value="<?php echo htmlspecialchars($gasto['total_gasto'] ?? ''); ?>" required />
                  <label for="total_gasto">Total (€) *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-4">
                  <input type="text" class="form-control" id="numero_factura_proveedor" name="numero_factura_proveedor" placeholder="Número de factura" value="<?php echo htmlspecialchars($gasto['numero_factura_proveedor'] ?? ''); ?>" />
                  <label for="numero_factura_proveedor">Nº Factura Proveedor</label>
                </div>
              </div>
              
              <!-- Clasificación y Estado -->
              <div class="col-md-6">
                <h5 class="mb-3">Clasificación y Estado</h5>
                
                <div class="mb-4">
                  <label for="empresa_gasto" class="form-label">Empresa *</label>
                  <select id="empresa_gasto" name="empresa_gasto" class="form-select" required>
                    <option value="">Seleccione empresa</option>
                    <?php
                    if ($gasto) {
                        $conexion = conectar_bd();
                        $query_empresas = "SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC";
                        $result_empresas = mysqli_query($conexion, $query_empresas);
                        
                        while ($empresa = mysqli_fetch_assoc($result_empresas)) {
                            $selected = ($empresa['id_empresa'] == $gasto['empresa_gasto']) ? 'selected' : '';
                            echo '<option value="' . $empresa['id_empresa'] . '" ' . $selected . '>' . htmlspecialchars($empresa['nombre_empresa']) . '</option>';
                        }
                        mysqli_close($conexion);
                    }
                    ?>
                  </select>
                </div>
                
                <div class="mb-4">
                  <label for="proveedor_gasto" class="form-label">Proveedor *</label>
                  <select id="proveedor_gasto" name="proveedor_gasto" class="form-select" required>
                    <option value="">Seleccione proveedor</option>
                    <?php
                    if ($gasto) {
                        $conexion = conectar_bd();
                        $query_proveedores = "SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor ASC";
                        $result_proveedores = mysqli_query($conexion, $query_proveedores);
                        
                        while ($proveedor = mysqli_fetch_assoc($result_proveedores)) {
                            $selected = ($proveedor['id_proveedor'] == $gasto['proveedor_gasto']) ? 'selected' : '';
                            echo '<option value="' . $proveedor['id_proveedor'] . '" ' . $selected . '>' . htmlspecialchars($proveedor['nombre_proveedor']) . '</option>';
                        }
                        mysqli_close($conexion);
                    }
                    ?>
                  </select>
                </div>
                
                <div class="mb-4">
                  <label for="tipo_de_gasto" class="form-label">Tipo de Gasto *</label>
                  <select id="tipo_de_gasto" name="tipo_de_gasto" class="form-select" required>
                    <option value="">Seleccione tipo</option>
                    <?php
                    if ($gasto) {
                        $conexion = conectar_bd();
                        $query_tipos = "SELECT id_tipo_gasto, nombre_tipo_gasto FROM tipo_de_gasto ORDER BY nombre_tipo_gasto ASC";
                        $result_tipos = mysqli_query($conexion, $query_tipos);
                        
                        while ($tipo = mysqli_fetch_assoc($result_tipos)) {
                            $selected = ($tipo['id_tipo_gasto'] == $gasto['tipo_de_gasto']) ? 'selected' : '';
                            echo '<option value="' . $tipo['id_tipo_gasto'] . '" ' . $selected . '>' . htmlspecialchars($tipo['nombre_tipo_gasto']) . '</option>';
                        }
                        mysqli_close($conexion);
                    }
                    ?>
                  </select>
                </div>
                
                <div class="mb-4">
                  <label for="forma_pago_gasto" class="form-label">Forma de Pago *</label>
                  <select id="forma_pago_gasto" name="forma_pago_gasto" class="form-select" required>
                    <option value="">Seleccione forma de pago</option>
                    <?php
                    if ($gasto) {
                        $conexion = conectar_bd();
                        $query_formas = "SELECT id_forma_de_pago, nombre_forma_de_pago FROM formas_de_pago ORDER BY nombre_forma_de_pago ASC";
                        $result_formas = mysqli_query($conexion, $query_formas);
                        
                        while ($forma = mysqli_fetch_assoc($result_formas)) {
                            $selected = ($forma['id_forma_de_pago'] == $gasto['forma_pago_gasto']) ? 'selected' : '';
                            echo '<option value="' . $forma['id_forma_de_pago'] . '" ' . $selected . '>' . htmlspecialchars($forma['nombre_forma_de_pago']) . '</option>';
                        }
                        mysqli_close($conexion);
                    }
                    ?>
                  </select>
                </div>
                
                <div class="mb-4">
                  <label for="estado_gasto" class="form-label">Estado *</label>
                  <select id="estado_gasto" name="estado_gasto" class="form-select" required>
                    <option value="pendiente" <?php echo (isset($gasto['estado_gasto']) && $gasto['estado_gasto'] == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="pagado" <?php echo (isset($gasto['estado_gasto']) && $gasto['estado_gasto'] == 'pagado') ? 'selected' : ''; ?>>Pagado</option>
                    <option value="cancelado" <?php echo (isset($gasto['estado_gasto']) && $gasto['estado_gasto'] == 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                  </select>
                </div>
                
                <div class="form-floating form-floating-outline mb-4">
                  <textarea class="form-control" id="observaciones_gasto" name="observaciones_gasto" placeholder="Observaciones adicionales" style="height: 100px"><?php echo htmlspecialchars($gasto['observaciones_gasto'] ?? ''); ?></textarea>
                  <label for="observaciones_gasto">Observaciones</label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="gastos.php" class="btn btn-text-primary me-2">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la lista
              </a>
              
              <div>
                <button type="reset" class="btn btn-text-danger me-2">
                  <i class="icon-base ri ri-refresh-line me-2"></i>
                  Restaurar
                </button>
                <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                  <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                  Aguarde...
                </button>
                <button type="submit" class="btn btn-primary" id="btnEditarGasto">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Actualizar Gasto
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