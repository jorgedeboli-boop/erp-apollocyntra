<div class="container-fluid flex-grow-1 container-p-y">
<?php require_once __DIR__ . '/../../universal/filtros_opciones_autorizaciones.php'; ?>
  <!-- Autorizaciones de Devoluciones List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Autorizaciones de Devoluciones</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 select2-btn-height" id="autorizar_filtros_container">
        <div class="col-md-6 user_sucursal">
          <select id="FiltroSucursal" class="form-select select2-filter text-capitalize select2-custom">
            <option value="">Seleccionar Sucursal</option>
          </select>
        </div>
        <div class="col-md-6 user_estado">
          <select id="FiltroEstado" class="form-select select2-filter text-capitalize select2-custom">
            <?php autorizaciones_imprimir_opciones_estado_devolucion(); ?>
          </select>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-autorizaciones-devoluciones table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>CÓDIGO AUTORIZACIÓN</th>
            <th>SUCURSAL</th>
            <th>ESTADO</th>
            <th>FECHA</th>
            <th>USUARIO</th>
            <th>SKU ARTÍCULO</th>
            <th>VENTA ID</th>
            <th>DEVOLUCIÓN ID</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<?php if ($puede_acceder_edit): ?>
<!-- Modal Autorizar Devolución -->
<div class="modal fade" id="modalAutorizarDevolucion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header card-header-forms pb-3">
        <h5 class="modal-title">Autorizar devolución</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <div class="row align-items-center g-4">
            <div class="col-sm-6">
              <p class="mb-1">Sucursal</p>
              <h5 id="modal-sucursal">-</h5>
            </div>
            <div class="col-sm-6">
              <p class="mb-1">Usuario</p>
              <h5 id="modal-usuario">-</h5>
            </div>
            <div class="col-sm-6">
              <p class="mb-1">SKU Artículo</p>
              <h5 id="modal-sku-articulo">-</h5>
            </div>
            <div class="col-sm-6">
              <p class="mb-1">Venta ID</p>
              <h5 id="modal-venta-id">-</h5>
            </div>
            <div class="col-sm-12">
              <p class="mb-1">Devolución ID</p>
              <h5 id="modal-devolucion-id">-</h5>
            </div>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-autorizacion">
          <i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Autorizar
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Los scripts se cargan desde javascript.php -->
