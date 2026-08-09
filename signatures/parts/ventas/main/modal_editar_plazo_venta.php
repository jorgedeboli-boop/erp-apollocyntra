<?php
/**
 * Modal editar importe de cuota (ventas a plazos).
 */
?>
<div class="modal fade" id="modalEditarPlazoVenta" tabindex="-1" aria-labelledby="modalEditarPlazoVentaLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header card-header border-bottom card-header-forms pb-4">
        <h4 class="modal-title" id="modalEditarPlazoVentaLabel">Editar plazo</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarPlazoVentaModal" novalidate>
          <input type="hidden" id="id_plazo_editar_venta" value="">
          <input type="hidden" id="id_venta_editar_plazo" value="<?php echo (int) ($id_venta ?? 0); ?>">

          <div class="row g-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Cuota Nº</label>
              <div class="fw-medium" id="editar_plazo_numero_cuota">—</div>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Estado</label>
              <div id="editar_plazo_estado">—</div>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Fecha vencimiento</label>
              <div class="fw-medium" id="editar_plazo_fecha_vencimiento">—</div>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Fecha cobrado</label>
              <div class="fw-medium" id="editar_plazo_fecha_cobrado">—</div>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Método de pago</label>
              <div class="fw-medium" id="editar_plazo_metodo_pago">—</div>
            </div>
            <div class="col-12">
              <label for="editar_plazo_importe" class="form-label">Importe (€)</label>
              <input type="number" class="form-control" id="editar_plazo_importe" step="0.01" min="0.01" required>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
              <i class="icon-base ri ri-close-line me-2"></i>Cancelar
            </button>
            <button type="submit" class="btn btn-primary" id="btnGuardarEditarPlazoVenta">
              <i class="icon-base ri ri-check-line me-2"></i>Guardar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
