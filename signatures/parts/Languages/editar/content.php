<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header border-bottom card-header-forms">
          <h4 class="card-title mb-0">Editar Language</h4>
          <small class="text-muted">Modifique los datos del language en el sistema</small>
          <button type="button" class="btn btn-text-primary btn-header-card-right" onclick="window.location.href='Languages.php'">
            <i class="icon-base ri ri-arrow-left-s-line me-2"></i>Languages
          </button>
        </div>
        <div class="card-body mt-4">
          <?php
          // Cargar datos del language directamente en PHP
          $id_lang = isset($_GET['id']) ? (int)$_GET['id'] : 0;
          
          if ($id_lang) {
              $conexion = conectar_bd();
              
              // Consulta para obtener datos del language
              $query_language = "
                  SELECT 
                      l.id_lang,
                      l.cod_LP,
                      l.description,
                      l.rel_id_country,
                      l.stateLang,
                      c.name_spanish as pais
                  FROM Languages l
                  LEFT JOIN countrys c ON l.rel_id_country = c.id_country
                  WHERE l.id_lang = ?
              ";
              
              $stmt_language = mysqli_prepare($conexion, $query_language);
              mysqli_stmt_bind_param($stmt_language, 'i', $id_lang);
              mysqli_stmt_execute($stmt_language);
              $result_language = mysqli_stmt_get_result($stmt_language);
              
              if ($result_language && mysqli_num_rows($result_language) > 0) {
                  $language = mysqli_fetch_assoc($result_language);
                  mysqli_stmt_close($stmt_language);
              } else {
                  echo '<div class="alert alert-danger">Language no encontrado</div>';
                  $language = null;
              }
          } else {
              echo '<div class="alert alert-danger">ID de language no válido</div>';
              $language = null;
          }
          ?>
          
          <form id="formEditarLanguage" method="POST" action="parts/Languages/editar/procesar_editar_Language.php">
            <input type="hidden" id="id_lang" name="id_lang" value="<?php echo $id_lang; ?>" />
            
            <div class="row">
              <!-- Información Básica -->
              <div class="col-md-6">
                <h5 class="mb-3">Información Básica</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="cod_LP" name="cod_LP" placeholder="es-ES" value="<?php echo htmlspecialchars($language['cod_LP'] ?? ''); ?>" required />
                  <label for="cod_LP">Código del Language *</label>
                </div>
                
                <div class="form-floating form-floating-outline mb-8">
                  <input type="text" class="form-control" id="description" name="description" placeholder="Español (España)" value="<?php echo htmlspecialchars($language['description'] ?? ''); ?>" required />
                  <label for="description">Descripción *</label>
                </div>
              </div>
              
              <!-- Configuración -->
              <div class="col-md-6">
                <h5 class="mb-3">Configuración</h5>
                
                <div class="form-floating form-floating-outline mb-8">
                  <?php echo generarSelectPaises('rel_id_country', $language['rel_id_country'] ?? 0, 'Seleccionar país'); ?>
                  <label for="rel_id_country">País *</label>
                </div>
                
                <div class="form-check form-switch mb-4">
                  <input class="form-check-input" type="checkbox" id="stateLang" name="stateLang" value="true" <?php echo (isset($language['stateLang']) && $language['stateLang'] === 'true') ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="stateLang">
                    Language Activo
                  </label>
                </div>
              </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Botones de Acción -->
            <div class="d-flex justify-content-between">
              <a href="Languages.php" class="btn btn-text-primary me-2">
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
                <button type="submit" class="btn btn-primary" id="btnEditarLanguage">
                  <i class="icon-base ri ri-check-line me-2"></i>
                  Actualizar Language
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// Cerrar conexión después de usar generarSelectPaises
if (isset($conexion)) {
    mysqli_close($conexion);
}
?>
<!-- / Content -->