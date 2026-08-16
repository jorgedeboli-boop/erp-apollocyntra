<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <?php
  // Cargar datos del artículo
  $id_articulo = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  
  if ($id_articulo) {
      $conexion = conectar_bd();
      
      // Consulta para obtener datos del artículo
      $query_articulo = "
          SELECT 
              av.*
          FROM articulos_venta av
          WHERE av.id = ?
      ";
      
      $stmt_articulo = mysqli_prepare($conexion, $query_articulo);
      mysqli_stmt_bind_param($stmt_articulo, 'i', $id_articulo);
      mysqli_stmt_execute($stmt_articulo);
      $result_articulo = mysqli_stmt_get_result($stmt_articulo);
      
      if ($result_articulo && mysqli_num_rows($result_articulo) > 0) {
          $articulo = mysqli_fetch_assoc($result_articulo);
          mysqli_stmt_close($stmt_articulo);
          
          $estado_articulo = strtolower($articulo['estado'] ?? '');
          $estados_editables = ['noetiquetado_c', 'noetiquetado_u', 'enventa', 'enviado', 'enreparacion'];
          
          if (!in_array($estado_articulo, $estados_editables)) {
              echo '<div class="alert alert-warning">Este artículo no se puede editar.</div>';
              echo '<div class="mt-3"><a href="articulo.php?id=' . htmlspecialchars($id_articulo) . '" class="btn btn-primary">Volver a la ficha del artículo</a></div>';
              mysqli_close($conexion);
              exit;
          }
  ?>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h5 class="card-title mb-0">Editar artículo #<?php echo htmlspecialchars($articulo['id']); ?></h5>
          <small class="text-muted">Modifique los datos del artículo</small>
          <button type="button" id="btn_volver_articulos" class="btn btn-text-primary btn-header-card-right">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Ir a la ficha del artículo
          </button>
        </div>
        <div class="card-body mt-5">
          <form id="formEditarArticulo" method="POST" action="parts/articulos/editar/actualizar_articulo.php" class="fv-plugins-bootstrap5 fv-plugins-framework">
            <input type="hidden" name="id_articulo" value="<?php echo htmlspecialchars($articulo['id']); ?>">
            
            <!-- Datos de artículo -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Datos de artículo</h5>
              </div>
              
              <div class="col-md-6 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_venta" name="precio_venta" placeholder="Precio de venta" value="<?php echo htmlspecialchars($articulo['precio'] ?? ''); ?>" required />
                  <label for="precio_venta">Precio de venta *</label>
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="precio_coste" name="precio_coste" placeholder="Precio de coste" value="<?php echo htmlspecialchars($articulo['precio_coste'] ?? ''); ?>" />
                  <label for="precio_coste">Precio de coste </label>
                </div>
              </div>
              
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="descripcion" name="descripcion" placeholder="Descripción" style="min-height: 100px;" required><?php echo htmlspecialchars($articulo['descripcion'] ?? ''); ?></textarea>
                  <label for="descripcion">Descripción *</label>
                </div>
              </div>
              
              <!-- Tipo de IVA -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Tipo de IVA *</label>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['system_codigo_regimen'] === 'REBU') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="system_codigo_regimen_REBU">
                        <input class="form-check-input" type="radio" name="system_codigo_regimen" value="REBU" id="system_codigo_regimen_REBU" <?php echo ($articulo['system_codigo_regimen'] === 'REBU') ? 'checked' : ''; ?> required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">REBU</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['system_codigo_regimen'] === 'INVERSION') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="system_codigo_regimen_INVERSION">
                        <input class="form-check-input" type="radio" name="system_codigo_regimen" value="INVERSION" id="system_codigo_regimen_INVERSION" <?php echo ($articulo['system_codigo_regimen'] === 'INVERSION') ? 'checked' : ''; ?> required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">ORO INVERSIÓN</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['system_codigo_regimen'] === 'GENERAL') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="system_codigo_regimen_GENERAL">
                        <input class="form-check-input" type="radio" name="system_codigo_regimen" value="GENERAL" id="system_codigo_regimen_GENERAL" <?php echo ($articulo['system_codigo_regimen'] === 'GENERAL') ? 'checked' : ''; ?> required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">RÉGIMEN GENERAL</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="observaciones" name="observaciones" placeholder="Observaciones" style="min-height: 100px;"><?php echo htmlspecialchars($articulo['observaciones'] ?? ''); ?></textarea>
                  <label for="observaciones">Observaciones</label>
                </div>
              </div>
            </div>
            
            <!-- Botones -->
            <div class="mt-4">
              <button type="submit" class="btn btn-primary me-2">
                <i class="icon-base ri ri-check-line me-2"></i>Actualizar artículo
              </button>
              <button type="button" id="btn_cancelar_articulo" class="btn btn-outline-secondary">
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php
      } else {
          echo '<div class="alert alert-danger">Artículo no encontrado</div>';
      }
      
      mysqli_close($conexion);
  } else {
      echo '<div class="alert alert-danger">ID de artículo no válido</div>';
  }
  ?>
</div>
<!-- / Content -->
