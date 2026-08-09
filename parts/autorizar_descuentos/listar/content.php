<div class="container-fluid flex-grow-1 container-p-y">
<?php require_once __DIR__ . '/../../universal/filtros_opciones_autorizaciones.php'; ?>
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Autorizaciones de descuento (venta)</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 select2-btn-height" id="autorizar_filtros_container">
        <div class="col-md-6 user_sucursal">
          <select id="FiltroSucursalDescuento" class="form-select select2-filter text-capitalize select2-custom">
            <option value="">Sucursal (todas)</option>
          </select>
        </div>
        <div class="col-md-6 user_estado_descuento">
          <select id="FiltroEstadoDescuento" class="form-select select2-filter text-capitalize select2-custom">
            <?php autorizaciones_imprimir_opciones_estado_usada(); ?>
          </select>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-autorizaciones-descuento table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>SUCURSAL</th>
            <th>USUARIO</th>
            <th>CÓDIGO</th>
            <th>ID ARTÍCULO</th>
            <th>DESCRIPCIÓN</th>
            <th>ESTADO</th>
            <th>FECHA</th>
            <th>PRECIO ORIGINAL</th>
            <th>PRECIO NUEVO</th>
            <th>AUTORIZAR</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<?php if ($puede_acceder_edit): ?>
<div class="modal fade" id="modalAutorizarDescuento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header card-header-forms pb-3">
        <h5 class="modal-title">Autorizar descuento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row align-items-center g-4">
          <div class="col-sm-4">
            <p class="mb-1">Sucursal</p>
            <h5 id="modal-desc-sucursal">-</h5>
          </div>
          <div class="col-sm-4">
            <p class="mb-1">Usuario</p>
            <h5 id="modal-desc-usuario">-</h5>
          </div>
          <div class="col-sm-4">
            <p class="mb-1">Código</p>
            <h5 id="modal-desc-codigo">-</h5>
          </div>
          <div class="col-sm-4">
            <p class="mb-1">ID artículo</p>
            <h5 id="modal-desc-id-articulo">-</h5>
          </div>
          <div class="col-sm-8">
            <p class="mb-1">Descripción</p>
            <h5 id="modal-desc-descripcion" class="fw-normal fs-6">-</h5>
          </div>
          <div class="col-sm-6">
            <p class="mb-1">Precio original</p>
            <h5 id="modal-desc-precio-original">-</h5>
          </div>
          <div class="col-sm-6">
            <label for="input-precio-nuevo-autorizacion" class="form-label">Precio autorizado (nuevo)</label>
            <input type="number" class="form-control" id="input-precio-nuevo-autorizacion" min="0" step="0.01" placeholder="0,00" autocomplete="off" />
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="btn-cancelar-solicitud-descuento">Rechazar</button>
        <button type="button" class="btn btn-success" id="btn-confirmar-autorizacion-descuento">
          <i class="icon-base ri ri-checkbox-circle-fill me-1"></i>Autorizar
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
