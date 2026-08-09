<div class="container-fluid flex-grow-1 container-p-y">
<?php require_once __DIR__ . '/../../universal/filtros_opciones_autorizaciones.php'; ?>
  <!-- Autorizaciones de Firmas List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Autorizaciones de Firmas</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 select2-btn-height" id="autorizar_filtros_container">
        <div class="col-md-6 user_sucursal">
          <select id="FiltroSucursal" class="form-select select2-filter text-capitalize select2-custom">
            <option value="">Seleccionar Sucursal</option>
          </select>
        </div>
        <div class="col-md-6 user_estado">
          <select id="FiltroEstado" class="form-select select2-filter text-capitalize select2-custom">
            <?php autorizaciones_imprimir_opciones_estado_firma(); ?>
          </select>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-autorizaciones-firmas table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>SUCURSAL</th>
            <th>ESTADO</th>
            <th>CÓDIGO</th>
            <th>FECHA</th>
            <th>USUARIO</th>
            <th>TIPO ITEM</th>
            <th>ITEM ID</th>
            <th>RECIBE EUROS</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<?php if ($puede_acceder_edit): ?>
<!-- Modal Autorizar Firma -->
<div class="modal fade" id="modalAutorizarFirma" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header card-header-forms pb-3">
        <h5 class="modal-title">Autorizar firma</h5>
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
              <p class="mb-1">Tipo Item</p>
              <h5 id="modal-tipo-item">-</h5>
            </div>
            <div class="col-sm-6">
              <p class="mb-1">Item ID</p>
              <h5 id="modal-item-id">-</h5>
            </div>
            <div class="col-sm-12">
              <div class="bg-label-primary p-4 rounded-4">
                <div class="d-flex">
                  <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                    <div class="me-2 w-100 text-center">
                      <h6 class="mb-0 w-100 text-primary">Recibe Euros</h6>
                      <h3 class="mb-0 text-primary" id="modal-recibe-euros">-</h3>
                    </div>
                  </div>
                </div>
              </div>
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
