    <div class="col-md-12">
                <h5 class="mb-3">Dirección de residencia</h5>
                
                <div class="form-floating form-floating-outline mb-4">
                  <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Calle, número, piso..." value="<?php echo isset($direccion_cliente['direccion']) ? htmlspecialchars($direccion_cliente['direccion']) : ''; ?>" required />
                  <label for="direccion" class="form-label">Dirección *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-4">
                  
                  <select class="form-select select2" id="pais" name="pais" required>
                    <option value="">Seleccionar...</option>
                    <?php
                    // Si existe el país, cargarlo
                    if (isset($direccion_cliente['rel_id_pais']) && $direccion_cliente['rel_id_pais']) {
                        $conexion_pais = conectar_bd();
                        $query_pais = "SELECT id_country, name_spanish FROM countrys WHERE id_country = ?";
                        $stmt_pais = mysqli_prepare($conexion_pais, $query_pais);
                        mysqli_stmt_bind_param($stmt_pais, 'i', $direccion_cliente['rel_id_pais']);
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
                  <label for="pais" class="form-label">País *</label>
                </div>

                <div class="form-floating form-floating-outline mb-4">
                  
                  <select class="form-select select2" id="c_provincia" name="c_provincia" required
                    <?php 
                    // Si NO hay rel_id_provincia pero SÍ hay texto, agregar data-manual-text
                    if ((!isset($direccion_cliente['rel_id_provincia']) || !$direccion_cliente['rel_id_provincia']) && 
                        isset($direccion_cliente['c_provincia']) && $direccion_cliente['c_provincia']) {
                        echo 'data-manual-text="' . htmlspecialchars($direccion_cliente['c_provincia']) . '"';
                    }
                    ?>>
                    <option value="">Seleccionar...</option>
                    <?php
                    // Si existe la provincia, cargarla
                    if (isset($direccion_cliente['rel_id_provincia']) && $direccion_cliente['rel_id_provincia']) {
                        $conexion_prov = conectar_bd();
                        $query_prov = "SELECT id_province, nombreProvince FROM provincias WHERE id_province = ?";
                        $stmt_prov = mysqli_prepare($conexion_prov, $query_prov);
                        mysqli_stmt_bind_param($stmt_prov, 'i', $direccion_cliente['rel_id_provincia']);
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
                  <label for="c_provincia" class="form-label">Provincia *</label>
                </div>

                <div class="form-floating form-floating-outline mb-4">
                  
                  <select class="form-select select2" id="c_poblacion" name="c_poblacion" required
                    <?php 
                    // Si NO hay rel_id_poblacion pero SÍ hay texto, agregar data-manual-text
                    if ((!isset($direccion_cliente['rel_id_poblacion']) || !$direccion_cliente['rel_id_poblacion']) && 
                        isset($direccion_cliente['c_poblacion']) && $direccion_cliente['c_poblacion']) {
                        echo 'data-manual-text="' . htmlspecialchars($direccion_cliente['c_poblacion']) . '"';
                    }
                    ?>>
                    <option value="">Seleccionar...</option>
                    <?php
                    // Si existe la población, cargarla
                    if (isset($direccion_cliente['rel_id_poblacion']) && $direccion_cliente['rel_id_poblacion']) {
                        $conexion_pobl = conectar_bd();
                        $query_pobl = "SELECT idpoblacion, poblacion FROM poblacion WHERE idpoblacion = ?";
                        $stmt_pobl = mysqli_prepare($conexion_pobl, $query_pobl);
                        mysqli_stmt_bind_param($stmt_pobl, 'i', $direccion_cliente['rel_id_poblacion']);
                        mysqli_stmt_execute($stmt_pobl);
                        $result_pobl = mysqli_stmt_get_result($stmt_pobl);
                        if ($row_pobl = mysqli_fetch_assoc($result_pobl)) {
                            echo '<option value="' . $row_pobl['idpoblacion'] . '" selected>' . htmlspecialchars($row_pobl['poblacion']) . '</option>';
                        }
                        mysqli_stmt_close($stmt_pobl);
                        mysqli_close($conexion_pobl);
                    }
                    ?>
                  </select>
                  <label for="c_poblacion" class="form-label">Población *</label>
                </div>
                                
                <div class="form-floating form-floating-outline mb-4">
                  <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Código postal" value="<?php echo isset($direccion_cliente['codigo_postal']) ? htmlspecialchars($direccion_cliente['codigo_postal']) : ''; ?>" required />
                  <label for="codigo_postal" class="form-label">Código Postal *</label>
                </div>

                <div class="form-floating form-floating-outline mb-4">
                  <input type="text" class="form-control" id="observaciones_direccion" name="observaciones_direccion" placeholder="Observaciones Dirección" value="<?php echo isset($direccion_cliente['observaciones_direccion']) ? htmlspecialchars($direccion_cliente['observaciones_direccion']) : ''; ?>" />
                  <label for="observaciones_direccion" class="form-label">Observaciones Dirección</label>
                </div>
                
              </div>