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
              av.*,
              s_destino.nombre_sucursal as nombre_sucursal_destino,
              s_origen.nombre_sucursal as nombre_sucursal_origen
          FROM articulos_venta av
          LEFT JOIN sucursal s_destino ON av.id_sucursal_destino = s_destino.id_sucursal
          LEFT JOIN sucursal s_origen ON av.id_sucursal_origen = s_origen.id_sucursal
          WHERE av.id = ? AND av.estado IN ('noetiquetado_c', 'noetiquetado_u', 'enventa', 'enviado', 'enreparacion')
      ";
      
      $stmt_articulo = mysqli_prepare($conexion, $query_articulo);
      mysqli_stmt_bind_param($stmt_articulo, 'i', $id_articulo);
      mysqli_stmt_execute($stmt_articulo);
      $result_articulo = mysqli_stmt_get_result($stmt_articulo);
      
      if ($result_articulo && mysqli_num_rows($result_articulo) > 0) {
          $articulo = mysqli_fetch_assoc($result_articulo);
          mysqli_stmt_close($stmt_articulo);
          
          // Verificar que el estado permita la edición
          $estado_articulo = strtolower($articulo['estado'] ?? '');
          $estados_editables = ['noetiquetado_c', 'noetiquetado_u', 'enventa', 'enviado', 'enreparacion'];
          
          if (!in_array($estado_articulo, $estados_editables)) {
              echo '<div class="alert alert-warning">Este artículo no se puede editar porque está en estado: ' . htmlspecialchars($articulo['estado']) . '</div>';
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
            
            <!-- Datos de vinculación -->
            <div class="row mb-4">
              <div class="col-12">
                <h5 class="mb-4">Datos de vinculación</h5>
              </div>
              
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline">
                  <select class="form-select select2" id="sucursal_origen" name="sucursal_origen">
                    <option value="">Seleccionar...</option>
                    <?php 
                    // Obtener todas las sucursales habilitadas para marcar la seleccionada
                    $query_sucursales = "SELECT id_sucursal, nombre_sucursal FROM sucursal WHERE estado_tienda = 'habilitada' ORDER BY nombre_sucursal ASC";
                    $result_sucursales = mysqli_query($conexion, $query_sucursales);
                    while ($suc = mysqli_fetch_assoc($result_sucursales)) {
                        $selected = ($articulo['id_sucursal_origen'] == $suc['id_sucursal']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($suc['id_sucursal']) . '" ' . $selected . '>' . htmlspecialchars($suc['nombre_sucursal']) . '</option>';
                    }
                    ?>
                  </select>
                  <label for="sucursal_origen" class="form-label">Sucursal origen</label>
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="lote_origen" name="lote_origen" placeholder="Lote origen" value="<?php echo htmlspecialchars($articulo['id_lote_origen'] ?? ''); ?>" />
                  <label for="lote_origen">Lote origen</label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
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
              
              <div class="col-md-6 mb-3">
                <div class="form-floating form-floating-outline">
                  <input type="number" step="0.01" class="form-control" id="peso" name="peso" placeholder="Peso" value="<?php echo htmlspecialchars($articulo['peso'] ?? ''); ?>" required />
                  <label for="peso">Peso (g) *</label>
                </div>
              </div>
              
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline" id="container_ley_oro" style="display: <?php echo (stripos($articulo['tipo'], 'oro') !== false) ? 'block' : 'none'; ?>;">
                  <select class="form-select select2" id="leyoro" <?php echo (stripos($articulo['tipo'], 'oro') !== false) ? 'name="ley" required' : ''; ?>>
                    <option value="">Seleccionar...</option>
                    <?php
                    $leyes_oro = ['9kl', '14kl', '16kl', '17kl', '18kl', '19kl', '20kl', '21kl', '22kl', '23kl', '24kl', '216kl'];
                    foreach ($leyes_oro as $ley) {
                        $selected = ($articulo['ley'] == $ley) ? 'selected' : '';
                        $texto = ($ley == '216kl') ? '21,6 Quilates' : $ley . ' Quilates';
                        echo '<option value="' . htmlspecialchars($ley) . '" ' . $selected . '>' . htmlspecialchars($texto) . '</option>';
                    }
                    ?>
                  </select>
                  <label for="leyoro" class="form-label">Ley </label>
                </div>
                <div class="form-floating form-floating-outline" id="container_ley_plata" style="display: <?php echo (stripos($articulo['tipo'], 'plata') !== false) ? 'block' : 'none'; ?>;">
                  <select class="form-select select2" id="leyplata" <?php echo (stripos($articulo['tipo'], 'plata') !== false) ? 'name="ley" required' : ''; ?>>
                    <option value="">Seleccionar...</option>
                    <?php
                    $leyes_plata = ['925', '900', '850', '999'];
                    foreach ($leyes_plata as $ley) {
                        $selected = ($articulo['ley'] == $ley) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($ley) . '" ' . $selected . '>' . htmlspecialchars($ley) . '</option>';
                    }
                    ?>
                  </select>
                  <label for="leyplata" class="form-label">Ley </label>
                </div>
              </div>
              
              <div class="col-12 mb-3">
                <div class="form-floating form-floating-outline">
                  <textarea class="form-control" id="descripcion" name="descripcion" placeholder="Descripción" style="min-height: 100px;" required><?php echo htmlspecialchars($articulo['descripcion'] ?? ''); ?></textarea>
                  <label for="descripcion">Descripción *</label>
                </div>
              </div>
              
              <!-- Inscripciones -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Inscripciones</label>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['inscripciones'] === 'si') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="inscripciones_si">
                        <input class="form-check-input" type="radio" name="inscripciones" value="si" id="inscripciones_si" <?php echo ($articulo['inscripciones'] === 'si') ? 'checked' : ''; ?>>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Si</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['inscripciones'] !== 'si') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="inscripciones_no">
                        <input class="form-check-input" type="radio" name="inscripciones" value="no" id="inscripciones_no" <?php echo ($articulo['inscripciones'] !== 'si') ? 'checked' : ''; ?>>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">No</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Piedras -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Piedras</label>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['piedras'] === 'si') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="piedras_si">
                        <input class="form-check-input" type="radio" name="piedras" value="si" id="piedras_si" <?php echo ($articulo['piedras'] === 'si') ? 'checked' : ''; ?>>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Si</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['piedras'] !== 'si') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="piedras_no">
                        <input class="form-check-input" type="radio" name="piedras" value="no" id="piedras_no" <?php echo ($articulo['piedras'] !== 'si') ? 'checked' : ''; ?>>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">No</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Tipo de artículo -->
              <div class="col-12 mb-4">
                <label class="form-label mb-3">Tipo de artículo *</label>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['tipo'] === 'oro') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="tipo_articulo_oro">
                        <input class="form-check-input" type="radio" name="tipo_articulo" value="oro" id="tipo_articulo_oro" <?php echo ($articulo['tipo'] === 'oro') ? 'checked' : ''; ?> required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Oro</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['tipo'] === 'plata') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="tipo_articulo_plata">
                        <input class="form-check-input" type="radio" name="tipo_articulo" value="plata" id="tipo_articulo_plata" <?php echo ($articulo['tipo'] === 'plata') ? 'checked' : ''; ?> required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Plata</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <div class="form-check custom-option custom-option-basic <?php echo ($articulo['tipo'] === 'acero') ? 'checked' : ''; ?>">
                      <label class="form-check-label custom-option-content" for="tipo_articulo_acero">
                        <input class="form-check-input" type="radio" name="tipo_articulo" value="acero" id="tipo_articulo_acero" <?php echo ($articulo['tipo'] === 'acero') ? 'checked' : ''; ?> required>
                        <span class="custom-option-header">
                          <span class="h6 mb-0">Acero</span>
                        </span>
                      </label>
                    </div>
                  </div>
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
