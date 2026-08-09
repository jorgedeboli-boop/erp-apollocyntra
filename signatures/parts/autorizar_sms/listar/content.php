<div class="container-fluid flex-grow-1 container-p-y">
<?php require_once __DIR__ . '/../../universal/filtros_opciones_autorizaciones.php'; ?>
  <!-- Autorizaciones SMS List Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Autorizaciones SMS</h5>
      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 select2-btn-height" id="autorizar_filtros_container">
        <div class="col-md-4 user_sucursal">
          <select id="FiltroSucursal" class="form-select select2-filter text-capitalize select2-custom">
            <option value="">Seleccionar Sucursal</option>
          </select>
        </div>
        <div class="col-md-4 user_estado_sms">
          <select id="FiltroEstadoSMS" class="form-select select2-filter text-capitalize select2-custom">
            <?php autorizaciones_imprimir_opciones_estado_sms_enviado(); ?>
          </select>
        </div>
        <div class="col-md-4 user_estado_autorizado">
          <select id="FiltroEstadoAutorizado" class="form-select select2-filter text-capitalize select2-custom">
            <?php autorizaciones_imprimir_opciones_estado_sms_autorizado(); ?>
          </select>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-autorizaciones-sms table border-top">
        <thead>
          <tr>
            <th>ID</th>
            <th>SUCURSAL</th>
            <th>CÓDIGO</th>
            <th>ESTADO SMS</th>
            <th>ESTADO CÓDIGO</th>
            <th>FECHA</th>
            <th>USUARIO</th>
            <th>CLIENTE</th>
            <th>LOTE</th>
            <th>MÓVIL</th>
            <th>TIPO</th>
            <th>MENSAJE</th>
            <th>AUTORIZAR</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<?php if ($puede_acceder_edit): ?>
<!-- Modal Autorizar SMS -->
<div class="modal fade" id="modalAutorizarSMS" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header card-header-forms pb-3">
        <h5 class="modal-title">Autorizar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row align-items-center g-4">
          <div class="col-sm-4">
            <p class="mb-1">Sucursal</p>
            <h5 id="modal-sucursal">-</h5>
          </div>
          <div class="col-sm-4">
            <p class="mb-1">Usuario</p>
            <h5 id="modal-usuario">-</h5>
          </div>
          <div class="col-sm-4">
            <p class="mb-1">Cliente</p>
            <h5 id="modal-cliente">-</h5>
          </div>
          <div class="col-sm-4">
            <p class="mb-1">Móvil</p>
            <h5 id="modal-movil">-</h5>
          </div>
          <div class="col-sm-4">
            <p class="mb-1">Tipo</p>
            <h5 id="modal-tipo">-</h5>
          </div>
          <div class="col-sm-4">
            <p class="mb-1">Lote</p>
            <h5 id="modal-lote">-</h5>
          </div>
          <div class="col-12">
            <p class="mb-1">Mensaje</p>
            <h5 id="modal-mensaje">-</h5>
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
