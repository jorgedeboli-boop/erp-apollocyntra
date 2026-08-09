<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar Proveedor</h4>
          <small class="text-muted">Modifique los datos del proveedor en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='proveedores.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Proveedores
          </button>
        </div>
        <div class="card-body mt-4">
          <?php
          // Cargar datos del proveedor directamente en PHP
          $id_proveedor = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          
          if ($id_proveedor) {
              $conexion = conectar_bd();
              
              // Consulta para obtener datos del proveedor
              $query_proveedor = "
                  SELECT 
                      id_proveedor,
                      nombre_proveedor,
                      cif_proveedor,
                      direccion_proveedor,
                      poblacion_proveedor,
                      provincia_proveedor,
                      telefono_proveedor,
                      codigo_postal_proveedor,
                      pais_proveedor,
                      email_proveedor,
                      moneda_proveedor,
                      forma_pago_proveedor,
                      fundicion,
                      fundicion_multi_kilates
                  FROM proveedores
                  WHERE id_proveedor = ?
              ";
              
              $stmt_proveedor = mysqli_prepare($conexion, $query_proveedor);
              mysqli_stmt_bind_param($stmt_proveedor, 'i', $id_proveedor);
              mysqli_stmt_execute($stmt_proveedor);
              $result_proveedor = mysqli_stmt_get_result($stmt_proveedor);
              
              if ($result_proveedor && mysqli_num_rows($result_proveedor) > 0) {
                  $proveedor = mysqli_fetch_assoc($result_proveedor);
                  mysqli_stmt_close($stmt_proveedor);
              } else {
                  echo '<div class="alert alert-danger">Proveedor no encontrado</div>';
                  $proveedor = null;
              }
              
              mysqli_close($conexion);
          } else {
              echo '<div class="alert alert-danger">ID de proveedor no válido</div>';
              $proveedor = null;
          }
          ?>
          
          <form id="formEditarProveedor" method="POST" action="parts/proveedores/editar/procesar_editar_proveedor.php">
            <input type="hidden" id="id_proveedor" name="id_proveedor" value="<?php echo $id_proveedor; ?>" />
            
            <div class="row">
              <!-- Información Básica -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Básica</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_proveedor" name="nombre_proveedor" placeholder="Nombre del proveedor" value="<?php echo htmlspecialchars($proveedor['nombre_proveedor'] ?? ''); ?>" required />
                  <label for="nombre_proveedor">Nombre del Proveedor *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="cif_proveedor" name="cif_proveedor" placeholder="CIF del proveedor" value="<?php echo htmlspecialchars($proveedor['cif_proveedor'] ?? ''); ?>" required />
                  <label for="cif_proveedor">CIF del Proveedor *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="email" class="form-control" id="email_proveedor" name="email_proveedor" placeholder="proveedor@ejemplo.com" value="<?php echo htmlspecialchars($proveedor['email_proveedor'] ?? ''); ?>" required />
                  <label for="email_proveedor">Email del Proveedor *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="tel" class="form-control" id="telefono_proveedor" name="telefono_proveedor" placeholder="+34 91 123 45 67" value="<?php echo htmlspecialchars($proveedor['telefono_proveedor'] ?? ''); ?>" required />
                  <label for="telefono_proveedor">Teléfono *</label>
                </div>
              </div>
              
              <!-- Dirección -->
              <div class="col-md-6">
                <h5 class="mb-3">Dirección</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="direccion_proveedor" name="direccion_proveedor" placeholder="Calle, número, piso..." value="<?php echo htmlspecialchars($proveedor['direccion_proveedor'] ?? ''); ?>" required />
                  <label for="direccion_proveedor">Dirección *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="poblacion_proveedor" name="poblacion_proveedor" placeholder="Población" value="<?php echo htmlspecialchars($proveedor['poblacion_proveedor'] ?? ''); ?>" required />
                  <label for="poblacion_proveedor">Población *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="provincia_proveedor" name="provincia_proveedor" placeholder="Provincia" value="<?php echo htmlspecialchars($proveedor['provincia_proveedor'] ?? ''); ?>" required />
                  <label for="provincia_proveedor">Provincia *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="codigo_postal_proveedor" name="codigo_postal_proveedor" placeholder="Código postal" maxlength="5" value="<?php echo htmlspecialchars($proveedor['codigo_postal_proveedor'] ?? ''); ?>" required />
                  <label for="codigo_postal_proveedor">Código Postal *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="pais_proveedor" name="pais_proveedor" placeholder="País" value="<?php echo htmlspecialchars($proveedor['pais_proveedor'] ?? 'España'); ?>" required />
                  <label for="pais_proveedor">País *</label>
                </div>
              </div>
            </div>
            
            <div class="row mt-4">
              <!-- Información Adicional -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Adicional</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="moneda_proveedor" name="moneda_proveedor" placeholder="EUR" value="<?php echo htmlspecialchars($proveedor['moneda_proveedor'] ?? 'EUR'); ?>" required />
                  <label for="moneda_proveedor">Moneda *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <select class="form-select" id="forma_pago_proveedor" name="forma_pago_proveedor" required>
                    <option value="">Seleccionar forma de pago</option>
                    <option value="efectivo" <?php echo (isset($proveedor['forma_pago_proveedor']) && $proveedor['forma_pago_proveedor'] === 'efectivo') ? 'selected' : ''; ?>>Efectivo</option>
                    <option value="tarjeta" <?php echo (isset($proveedor['forma_pago_proveedor']) && $proveedor['forma_pago_proveedor'] === 'tarjeta') ? 'selected' : ''; ?>>Tarjeta</option>
                    <option value="transferencia" <?php echo (isset($proveedor['forma_pago_proveedor']) && $proveedor['forma_pago_proveedor'] === 'transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                    <option value="domiciliacion" <?php echo (isset($proveedor['forma_pago_proveedor']) && $proveedor['forma_pago_proveedor'] === 'domiciliacion') ? 'selected' : ''; ?>>Domiciliación</option>
                    <option value="bizum" <?php echo (isset($proveedor['forma_pago_proveedor']) && $proveedor['forma_pago_proveedor'] === 'bizum') ? 'selected' : ''; ?>>Bizum</option>
                  </select>
                  <label for="forma_pago_proveedor">Forma de Pago *</label>
                </div>
              </div>
              
              <!-- Servicios de Fundición -->
              <div class="col-md-6">
                <h5 class="mb-3">Servicios de Fundición</h5>
                
                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="fundicion" name="fundicion" value="true" <?php echo (isset($proveedor['fundicion']) && $proveedor['fundicion'] === 'true') ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="fundicion">
                    Servicio de Fundición
                  </label>
                </div>
                
                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="fundicion_multi_kilates" name="fundicion_multi_kilates" value="true" <?php echo (isset($proveedor['fundicion_multi_kilates']) && $proveedor['fundicion_multi_kilates'] === 'true') ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="fundicion_multi_kilates">
                    Fundición Multi-Kilates
                  </label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="proveedores.php" class="btn btn-text-primary me-2">
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
                <button type="submit" class="btn btn-primary" id="btnEditarProveedor">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Actualizar Proveedor
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