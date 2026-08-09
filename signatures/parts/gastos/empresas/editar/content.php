<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar Empresa</h4>
          <small class="text-muted">Modifique los datos de la empresa en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='empresas.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Empresas
          </button>
        </div>
        <div class="card-body mt-4">
          <?php
          // Cargar datos de la empresa directamente en PHP
          $id_empresa = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          
          if ($id_empresa) {
              $conexion = conectar_bd();
              
              // Consulta para obtener datos de la empresa
              $query_empresa = "
                  SELECT 
                      id_empresa,
                      nombre_empresa,
                      cif_empresa,
                      direccion_empresa,
                      poblacion_empresa,
                      provincia_empresa,
                      telefono_empresa,
                      codigo_postal_empresa,
                      pais_empresa,
                      email_empresa,
                      texto_facturas,
                      texto_contrato_empeno,
                      texto_contrato_compra,
                      webempresa
                  FROM empresas
                  WHERE id_empresa = ?
              ";
              
              $stmt_empresa = mysqli_prepare($conexion, $query_empresa);
              mysqli_stmt_bind_param($stmt_empresa, 'i', $id_empresa);
              mysqli_stmt_execute($stmt_empresa);
              $result_empresa = mysqli_stmt_get_result($stmt_empresa);
              
              if ($result_empresa && mysqli_num_rows($result_empresa) > 0) {
                  $empresa = mysqli_fetch_assoc($result_empresa);
                  mysqli_stmt_close($stmt_empresa);
              } else {
                  echo '<div class="alert alert-danger">Empresa no encontrada</div>';
                  $empresa = null;
              }
              
              mysqli_close($conexion);
          } else {
              echo '<div class="alert alert-danger">ID de empresa no válido</div>';
              $empresa = null;
          }
          ?>
          
          <form id="formEditarEmpresa" method="POST" action="parts/empresas/editar/procesar_editar_empresa.php">
            <input type="hidden" id="id_empresa" name="id_empresa" value="<?php echo $id_empresa; ?>" />
            
            <div class="row">
              <!-- Información Básica -->
              <div class="col-md-6">
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
              <div class="col-md-6">
                <h5 class="mb-3">Dirección</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="direccion_empresa" name="direccion_empresa" placeholder="Calle, número, piso..." value="<?php echo htmlspecialchars($empresa['direccion_empresa'] ?? ''); ?>" required />
                  <label for="direccion_empresa">Dirección *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="poblacion_empresa" name="poblacion_empresa" placeholder="Población" value="<?php echo htmlspecialchars($empresa['poblacion_empresa'] ?? ''); ?>" required />
                  <label for="poblacion_empresa">Población *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="provincia_empresa" name="provincia_empresa" placeholder="Provincia" value="<?php echo htmlspecialchars($empresa['provincia_empresa'] ?? ''); ?>" required />
                  <label for="provincia_empresa">Provincia *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="codigo_postal_empresa" name="codigo_postal_empresa" placeholder="Código postal" maxlength="5" value="<?php echo htmlspecialchars($empresa['codigo_postal_empresa'] ?? ''); ?>" required />
                  <label for="codigo_postal_empresa">Código Postal *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="pais_empresa" name="pais_empresa" placeholder="País" value="<?php echo htmlspecialchars($empresa['pais_empresa'] ?? ''); ?>" required />
                  <label for="pais_empresa">País *</label>
                </div>
              </div>
            </div>
            
            <div class="row mt-4">
              <!-- Textos y Web -->
              <div class="col-md-6">
                <h5 class="mb-3">Textos y Web</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <textarea class="form-control" id="texto_facturas" name="texto_facturas" placeholder="Texto para facturas" style="height: 100px" required><?php echo htmlspecialchars($empresa['texto_facturas'] ?? ''); ?></textarea>
                  <label for="texto_facturas">Texto Facturas *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <textarea class="form-control" id="texto_contrato_empeno" name="texto_contrato_empeno" placeholder="Texto para contratos de empeño" style="height: 100px"><?php echo htmlspecialchars($empresa['texto_contrato_empeno'] ?? ''); ?></textarea>
                  <label for="texto_contrato_empeno">Texto Contrato Empeño</label>
                </div>
              </div>
              
              <!-- Textos Adicionales -->
              <div class="col-md-6">
                <h5 class="mb-3">Textos Adicionales</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <textarea class="form-control" id="texto_contrato_compra" name="texto_contrato_compra" placeholder="Texto para contratos de compra" style="height: 100px"><?php echo htmlspecialchars($empresa['texto_contrato_compra'] ?? ''); ?></textarea>
                  <label for="texto_contrato_compra">Texto Contrato Compra</label>
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
              <a href="empresas.php" class="btn btn-outline-secondary">
                <i class="icon-base ri ri-arrow-left-line me-2"></i>
                Volver a la lista
              </a>
              
              <div>
                <button type="reset" class="btn btn-outline-danger me-2">
                  <i class="icon-base ri ri-refresh-line me-2"></i>
                  Restaurar
                </button>
                <button class="btn btn-primary" type="button" disabled id="loaderbtn" style="display: none;">
                  <span class="spinner-border me-1" role="status" aria-hidden="true"></span>
                  Aguarde...
                </button>
                <button type="submit" class="btn btn-primary" id="btnEditarEmpresa">
                  <i class="icon-base ri ri-save-line me-2"></i>
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