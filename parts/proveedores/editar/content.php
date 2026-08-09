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
                      fundicion_multi_kilates,
                      rel_id_provincia,
                      rel_id_poblacion,
                      rel_id_pais
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
                
                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="direccion_proveedor" name="direccion_proveedor" placeholder="Calle, número, piso..." value="<?php echo htmlspecialchars($proveedor['direccion_proveedor'] ?? ''); ?>" required />
                  <label for="direccion_proveedor">Dirección *</label>
                </div>
                
                <div class="mb-3">
                  <label for="pais" class="form-label">País *</label>
                  <select class="form-select select2" id="pais" name="pais" required>
                    <option value="">Seleccionar país</option>
                    <?php
                    // Si existe el país, cargarlo
                    if (isset($proveedor['rel_id_pais']) && $proveedor['rel_id_pais']) {
                        $conexion_pais = conectar_bd();
                        $query_pais = "SELECT id_country, name_spanish FROM countrys WHERE id_country = ?";
                        $stmt_pais = mysqli_prepare($conexion_pais, $query_pais);
                        mysqli_stmt_bind_param($stmt_pais, 'i', $proveedor['rel_id_pais']);
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
                    if (isset($proveedor['rel_id_provincia']) && $proveedor['rel_id_provincia']) {
                        $conexion_prov = conectar_bd();
                        $query_prov = "SELECT id_province, nombreProvince FROM provincias WHERE id_province = ?";
                        $stmt_prov = mysqli_prepare($conexion_prov, $query_prov);
                        mysqli_stmt_bind_param($stmt_prov, 'i', $proveedor['rel_id_provincia']);
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

                <div class="mb-5">
                  <label for="c_poblacion" class="form-label">Población *</label>
                  <select class="form-select select2" id="c_poblacion" name="c_poblacion" required>
                    <option value="">Seleccionar población</option>
                    <?php
                    // Si existe la población, cargarla
                    if (isset($proveedor['rel_id_poblacion']) && $proveedor['rel_id_poblacion']) {
                        $conexion_pob = conectar_bd();
                        $query_pob = "SELECT idpoblacion, poblacion FROM poblacion WHERE idpoblacion = ?";
                        $stmt_pob = mysqli_prepare($conexion_pob, $query_pob);
                        mysqli_stmt_bind_param($stmt_pob, 'i', $proveedor['rel_id_poblacion']);
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
                  <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Código postal" maxlength="5" value="<?php echo isset($proveedor['codigo_postal_proveedor']) ? htmlspecialchars($proveedor['codigo_postal_proveedor']) : ''; ?>" readonly />
                  <label for="codigo_postal">Código Postal *</label>
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