<?php
$sku_prefill = isset($_GET['sku']) ? trim($_GET['sku']) : '';
$descripcion_prefill = isset($_GET['descripcion']) ? trim($_GET['descripcion']) : '';
?>
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <h4 class="mb-1">Crear devolución</h4>
      <p class="mb-4">Indique el motivo y seleccione el artículo vendido a devolver.</p>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form id="form_crear_devolucion" action="parts/devoluciones/crear/insertar_devolucion.php" method="POST" class="needs-validation" novalidate data-ajax-submit="1">
            <input type="hidden" name="id_articulo" id="id_articulo" value="" />

            <div class="mb-4">
              <label for="motivo_devolucion" class="form-label">Motivo de la devolución</label>
              <textarea class="form-control" id="motivo_devolucion" name="motivo_devolucion" rows="4" placeholder="Describa el motivo de la devolución..." required></textarea>
              <div class="invalid-feedback">Indique el motivo de la devolución.</div>
            </div>

            <div class="mb-4">
              <label for="buscar_sku" class="form-label">Buscar artículo vendido por SKU</label>
              <input type="text" class="form-control" id="buscar_sku" name="buscar_sku" placeholder="Escriba al menos 3 dígitos del SKU..." autocomplete="off" value="<?php echo htmlspecialchars($sku_prefill); ?>" />
              <div id="resultados_sku" class="list-group mt-2" style="max-height: 280px; overflow-y: auto; display: none;"></div>
              <small class="text-muted">Se mostrarán artículos en estado vendido o vendido web.</small>
            </div>

            <div id="articulo_seleccionado" class="mb-4 p-3 bg-light rounded" style="display: none;">
              <strong>Artículo seleccionado:</strong>
              <span id="articulo_sku_text"></span> – <span id="articulo_descripcion_text"></span>
              <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="quitar_articulo">Quitar</button>
            </div>

            <div class="d-flex gap-2 flex-wrap">
              <a href="devoluciones.php" class="btn btn-outline-secondary">Volver al listado</a>
              <button type="submit" class="btn btn-primary" id="btn_generar_devolucion">
                <i class="icon-base ri ri-refund-line me-1"></i>Generar devolución
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->

<script>
(function() {
  var skuPrefill = <?php echo json_encode($sku_prefill); ?>;
  var descripcionPrefill = <?php echo json_encode($descripcion_prefill); ?>;
  if (skuPrefill && descripcionPrefill) {
    document.addEventListener('DOMContentLoaded', function() {
      var idInput = document.getElementById('id_articulo');
      var skuInput = document.getElementById('buscar_sku');
      var panel = document.getElementById('articulo_seleccionado');
      var skuText = document.getElementById('articulo_sku_text');
      var descText = document.getElementById('articulo_descripcion_text');
      if (idInput && skuInput && panel) {
        idInput.value = skuPrefill;
        skuText && (skuText.textContent = 'SKU ' + skuPrefill);
        descText && (descText.textContent = descripcionPrefill);
        panel.style.display = 'block';
        skuInput.placeholder = 'Artículo cargado desde ficha';
      }
    });
  }
})();
</script>
