<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar banco</h4>
          <small class="text-muted">Modifique los datos del banco</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='bancos_config.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Bancos
          </button>
        </div>
        <div class="card-body mt-4">
          <?php
          $id_banco = isset($_GET['id']) ? (int) $_GET['id'] : 0;
          $banco = null;

          if ($id_banco > 0) {
              $conexion = conectar_bd();
              $stmt = mysqli_prepare(
                  $conexion,
                  'SELECT id_banco, nombre_banco, direccion_banco, provincia_banco, poblacion_banco,
                          pais_banco, estado_banco, telefono_banco, email_banco, contacto_banco
                   FROM bancos_config WHERE id_banco = ? LIMIT 1'
              );
              if ($stmt) {
                  mysqli_stmt_bind_param($stmt, 'i', $id_banco);
                  mysqli_stmt_execute($stmt);
                  $res = mysqli_stmt_get_result($stmt);
                  $banco = $res ? mysqli_fetch_assoc($res) : null;
                  mysqli_stmt_close($stmt);
              }
              mysqli_close($conexion);
          }

          if (!$banco) {
              echo '<div class="alert alert-danger">Banco no encontrado</div>';
          }
          ?>

          <?php if ($banco) : ?>
          <form id="formEditarBanco" method="POST" action="parts/bancos_config/editar/procesar_editar_banco.php">
            <input type="hidden" id="id_banco" name="id_banco" value="<?php echo (int) $id_banco; ?>" />

            <div class="row">
              <div class="col-md-6">
                <h5 class="mb-3">Información básica</h5>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="nombre_banco" name="nombre_banco" placeholder="Nombre del banco" maxlength="124" value="<?php echo htmlspecialchars($banco['nombre_banco'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                  <label for="nombre_banco">Nombre del banco *</label>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="contacto_banco" name="contacto_banco" placeholder="Persona de contacto" maxlength="164" value="<?php echo htmlspecialchars($banco['contacto_banco'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                  <label for="contacto_banco">Contacto *</label>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="tel" class="form-control" id="telefono_banco" name="telefono_banco" placeholder="Teléfono" maxlength="64" value="<?php echo htmlspecialchars($banco['telefono_banco'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                  <label for="telefono_banco">Teléfono *</label>
                </div>

                <div class="form-floating form-floating-outline mb-8">
                  <input type="email" class="form-control" id="email_banco" name="email_banco" placeholder="banco@ejemplo.com" maxlength="128" value="<?php echo htmlspecialchars($banco['email_banco'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                  <label for="email_banco">Email *</label>
                </div>

                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="estado_banco" name="estado_banco" value="true" <?php echo (($banco['estado_banco'] ?? '') === 'true') ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="estado_banco">Banco activo</label>
                </div>
              </div>

              <div class="col-md-6">
                <h5 class="mb-3">Dirección</h5>

                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="direccion_banco" name="direccion_banco" placeholder="Calle, número..." maxlength="168" value="<?php echo htmlspecialchars($banco['direccion_banco'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required />
                  <label for="direccion_banco">Dirección *</label>
                </div>

                <div class="mb-3">
                  <label for="pais" class="form-label">País *</label>
                  <select class="form-select select2" id="pais" name="pais" required>
                    <option value="">Seleccionar país</option>
                    <?php
                    if (!empty($banco['pais_banco'])) {
                        $conexionPais = conectar_bd();
                        $stmtPais = mysqli_prepare($conexionPais, 'SELECT id_country, name_spanish FROM countrys WHERE id_country = ? LIMIT 1');
                        if ($stmtPais) {
                            $idPais = (int) $banco['pais_banco'];
                            mysqli_stmt_bind_param($stmtPais, 'i', $idPais);
                            mysqli_stmt_execute($stmtPais);
                            $resPais = mysqli_stmt_get_result($stmtPais);
                            if ($rowPais = mysqli_fetch_assoc($resPais)) {
                                echo '<option value="' . (int) $rowPais['id_country'] . '" selected>' . htmlspecialchars($rowPais['name_spanish'], ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            mysqli_stmt_close($stmtPais);
                        }
                        mysqli_close($conexionPais);
                    }
                    ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="c_provincia" class="form-label">Provincia *</label>
                  <select class="form-select select2" id="c_provincia" name="c_provincia" required>
                    <option value="">Seleccionar provincia</option>
                    <?php
                    if (!empty($banco['provincia_banco'])) {
                        $conexionProv = conectar_bd();
                        $stmtProv = mysqli_prepare($conexionProv, 'SELECT id_province, nombreProvince FROM provincias WHERE id_province = ? LIMIT 1');
                        if ($stmtProv) {
                            $idProv = (int) $banco['provincia_banco'];
                            mysqli_stmt_bind_param($stmtProv, 'i', $idProv);
                            mysqli_stmt_execute($stmtProv);
                            $resProv = mysqli_stmt_get_result($stmtProv);
                            if ($rowProv = mysqli_fetch_assoc($resProv)) {
                                echo '<option value="' . (int) $rowProv['id_province'] . '" selected>' . htmlspecialchars($rowProv['nombreProvince'], ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            mysqli_stmt_close($stmtProv);
                        }
                        mysqli_close($conexionProv);
                    }
                    ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="c_poblacion" class="form-label">Población *</label>
                  <select class="form-select select2" id="c_poblacion" name="c_poblacion" required>
                    <option value="">Seleccionar población</option>
                    <?php
                    $codigo_postal_banco = '';
                    if (!empty($banco['poblacion_banco'])) {
                        $conexionPob = conectar_bd();
                        $stmtPob = mysqli_prepare($conexionPob, 'SELECT idpoblacion, poblacion, postal FROM poblacion WHERE idpoblacion = ? LIMIT 1');
                        if ($stmtPob) {
                            $idPob = (int) $banco['poblacion_banco'];
                            mysqli_stmt_bind_param($stmtPob, 'i', $idPob);
                            mysqli_stmt_execute($stmtPob);
                            $resPob = mysqli_stmt_get_result($stmtPob);
                            if ($rowPob = mysqli_fetch_assoc($resPob)) {
                                $codigo_postal_banco = (string) ($rowPob['postal'] ?? '');
                                echo '<option value="' . (int) $rowPob['idpoblacion'] . '" selected>' . htmlspecialchars($rowPob['poblacion'], ENT_QUOTES, 'UTF-8') . '</option>';
                            }
                            mysqli_stmt_close($stmtPob);
                        }
                        mysqli_close($conexionPob);
                    }
                    ?>
                  </select>
                </div>

                <div class="form-floating form-floating-outline mb-3">
                  <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Código postal" maxlength="5" value="<?php echo htmlspecialchars($codigo_postal_banco, ENT_QUOTES, 'UTF-8'); ?>" readonly />
                  <label for="codigo_postal">Código postal</label>
                </div>
              </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between">
              <a href="banco_config.php?id=<?php echo (int) $id_banco; ?>" class="btn btn-text-primary me-2">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la ficha
              </a>
              <div>
                <button type="button" class="btn btn-text-danger me-2" onclick="window.location.reload()">
                  <i class="icon-base ri ri-refresh-line me-2"></i>
                  Restaurar
                </button>
                <button type="submit" class="btn btn-primary" id="btnEditarBanco">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Actualizar banco
                </button>
              </div>
            </div>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->
