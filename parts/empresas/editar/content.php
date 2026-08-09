<!-- Content -->
<?php
$id_empresa = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar Empresa</h4>
          <small class="text-muted">Modifique los datos de la empresa en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='empresa.php?id=<?php echo (int) $id_empresa; ?>'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Volver a la ficha
          </button>
        </div>
        <div class="card-body mt-4">
          <?php
          $texto_region_itp = '';
          $empresa = null;

          if ($id_empresa) {
              $conexion = conectar_bd();

              if (function_exists('cargar_empresa_por_id')) {
                  $empresa = cargar_empresa_por_id($conexion, $id_empresa);
              }

              if (!$empresa) {
                  $result_fallback = mysqli_query(
                      $conexion,
                      'SELECT * FROM empresas WHERE id_empresa = ' . (int) $id_empresa . ' LIMIT 1'
                  );
                  if ($result_fallback && mysqli_num_rows($result_fallback) > 0) {
                      $empresa = mysqli_fetch_assoc($result_fallback);
                  }
              }

              if ($empresa && function_exists('cargar_texto_region_itp_empresa')) {
                  $texto_region_itp = cargar_texto_region_itp_empresa(
                      $conexion,
                      $id_empresa,
                      isset($empresa['rel_id_provincia']) ? (int) $empresa['rel_id_provincia'] : 0
                  );
              }

              if (!$empresa) {
                  echo '<div class="alert alert-danger">Empresa no encontrada</div>';
              }

              mysqli_close($conexion);
          } else {
              echo '<div class="alert alert-danger">ID de empresa no válido</div>';
        }
          ?>
          
          <form id="formEditarEmpresa" method="POST" action="parts/empresas/editar/procesar_editar_empresa.php">
            <input type="hidden" id="id_empresa" name="id_empresa" value="<?php echo $id_empresa; ?>" />
            
            <div class="row g-4">
              <!-- Información Básica -->
              <div class="col-12 col-lg-4">
                <h5 class="mb-3">Información Básica</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" placeholder="Nombre de la empresa" value="<?php echo htmlspecialchars($empresa['nombre_empresa'] ?? ''); ?>" required />
                  <label for="nombre_empresa">Nombre de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="cif_empresa" name="cif_empresa" placeholder="CIF de la empresa" value="<?php echo htmlspecialchars($empresa['cif_empresa'] ?? ''); ?>" required />
                  <label for="cif_empresa">CIF de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="email" class="form-control" id="email_empresa" name="email_empresa" placeholder="empresa@ejemplo.com" value="<?php echo htmlspecialchars($empresa['email_empresa'] ?? ''); ?>" required />
                  <label for="email_empresa">Email de la Empresa *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="tel" class="form-control" id="telefono_empresa" name="telefono_empresa" placeholder="+34 91 123 45 67" value="<?php echo htmlspecialchars($empresa['telefono_empresa'] ?? ''); ?>" required />
                  <label for="telefono_empresa">Teléfono *</label>
                </div>
              </div>
              
              <!-- Dirección -->
              <div class="col-12 col-lg-4">
                <h5 class="mb-3">Dirección</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="direccion_empresa" name="direccion_empresa" placeholder="Calle, número, piso..." value="<?php echo htmlspecialchars($empresa['direccion_empresa'] ?? ''); ?>" required />
                  <label for="direccion_empresa">Dirección *</label>
                </div>

                <input type="hidden" id="pais_empresa" name="pais_empresa" value="<?php echo htmlspecialchars($empresa['pais_empresa'] ?? ''); ?>" />
                <input type="hidden" id="provincia_empresa" name="provincia_empresa" value="<?php echo htmlspecialchars($empresa['provincia_empresa'] ?? ''); ?>" />
                <input type="hidden" id="poblacion_empresa" name="poblacion_empresa" value="<?php echo htmlspecialchars($empresa['poblacion_empresa'] ?? ''); ?>" />

                <div class="mb-3">
                  <label for="rel_id_pais" class="form-label">País *</label>
                  <select class="form-select select2" id="rel_id_pais" name="rel_id_pais" required>
                    <option value="">Seleccionar país</option>
                    <?php
                    if (!empty($empresa['rel_id_pais'])) {
                        $conexion_pais = conectar_bd();
                        $query_pais = 'SELECT id_country, name_spanish FROM countrys WHERE id_country = ?';
                        $stmt_pais = mysqli_prepare($conexion_pais, $query_pais);
                        mysqli_stmt_bind_param($stmt_pais, 'i', $empresa['rel_id_pais']);
                        mysqli_stmt_execute($stmt_pais);
                        $result_pais = mysqli_stmt_get_result($stmt_pais);
                        if ($row_pais = mysqli_fetch_assoc($result_pais)) {
                            echo '<option value="' . (int) $row_pais['id_country'] . '" selected>' . htmlspecialchars($row_pais['name_spanish']) . '</option>';
                        }
                        mysqli_stmt_close($stmt_pais);
                        mysqli_close($conexion_pais);
                    }
                    ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="rel_id_provincia" class="form-label">Provincia *</label>
                  <select class="form-select select2" id="rel_id_provincia" name="rel_id_provincia" required>
                    <option value="">Seleccionar provincia</option>
                    <?php
                    if (!empty($empresa['rel_id_provincia'])) {
                        $conexion_prov = conectar_bd();
                        $query_prov = 'SELECT id_province, nombreProvince FROM provincias WHERE id_province = ?';
                        $stmt_prov = mysqli_prepare($conexion_prov, $query_prov);
                        mysqli_stmt_bind_param($stmt_prov, 'i', $empresa['rel_id_provincia']);
                        mysqli_stmt_execute($stmt_prov);
                        $result_prov = mysqli_stmt_get_result($stmt_prov);
                        if ($row_prov = mysqli_fetch_assoc($result_prov)) {
                            echo '<option value="' . (int) $row_prov['id_province'] . '" selected>' . htmlspecialchars($row_prov['nombreProvince']) . '</option>';
                        }
                        mysqli_stmt_close($stmt_prov);
                        mysqli_close($conexion_prov);
                    }
                    ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="rel_id_poblacion" class="form-label">Población *</label>
                  <select class="form-select select2" id="rel_id_poblacion" name="rel_id_poblacion" required>
                    <option value="">Seleccionar población</option>
                    <?php
                    if (!empty($empresa['rel_id_poblacion'])) {
                        $conexion_pob = conectar_bd();
                        $query_pob = 'SELECT idpoblacion, poblacion FROM poblacion WHERE idpoblacion = ?';
                        $stmt_pob = mysqli_prepare($conexion_pob, $query_pob);
                        mysqli_stmt_bind_param($stmt_pob, 'i', $empresa['rel_id_poblacion']);
                        mysqli_stmt_execute($stmt_pob);
                        $result_pob = mysqli_stmt_get_result($stmt_pob);
                        if ($row_pob = mysqli_fetch_assoc($result_pob)) {
                            echo '<option value="' . (int) $row_pob['idpoblacion'] . '" selected>' . htmlspecialchars($row_pob['poblacion']) . '</option>';
                        }
                        mysqli_stmt_close($stmt_pob);
                        mysqli_close($conexion_pob);
                    }
                    ?>
                  </select>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control bg-light" id="region_itp_empresa" placeholder="Región ITP" value="<?php echo htmlspecialchars($texto_region_itp ?? ''); ?>" readonly tabindex="-1" />
                  <label for="region_itp_empresa">Región ITP</label>
                </div>
              </div>
              
              <!-- Ubicación (CP y web) -->
              <div class="col-12 col-lg-4">
                <h5 class="mb-3">Ubicación</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="codigo_postal_empresa" name="codigo_postal_empresa" placeholder="Código postal" maxlength="5" value="<?php echo htmlspecialchars($empresa['codigo_postal_empresa'] ?? ''); ?>" readonly required />
                  <label for="codigo_postal_empresa">Código Postal *</label>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="webempresa" name="webempresa" placeholder="https://www.empresa.com" value="<?php echo htmlspecialchars($empresa['webempresa'] ?? ''); ?>" />
                  <label for="webempresa">Web de la Empresa</label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="empresa.php?id=<?php echo (int) $id_empresa; ?>" class="btn btn-text-primary me-2">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la ficha
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
                <button type="submit" class="btn btn-primary" id="btnEditarEmpresa">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Actualizar Empresa
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