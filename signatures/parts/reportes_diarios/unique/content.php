<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Reportes Diarios <span id="texto_reportes_diarios_titulo"></span></h5>
        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_reportes_diarios" aria-expanded="false" aria-controls="collapse_filtros_reportes_diarios">
          <i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar
        </button>
      </div>
    </div>

    <div class="card-body pb-0">
      <div class="collapse d-lg-block" id="collapse_filtros_reportes_diarios">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
          <div class="col-md-4">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_sucursal">
              <option value="">Todas las sucursales</option>
              <?php foreach (obtener_sucursales() as $sucursal): ?>
                <option value="<?php echo (int) $sucursal['id_sucursal']; ?>">
                  <?php echo htmlspecialchars($sucursal['nombre_sucursal']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <div class="input-group">
              <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Fecha informe">
              <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
              <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
              <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" id="filtro_por_fecha_diarios" href="javascript:void(0);">Por Fecha</a></li>
                <li><a class="dropdown-item" id="filtro_dia_diarios" href="javascript:void(0);">Día</a></li>
                <li><a class="dropdown-item" id="filtro_mes_diarios" href="javascript:void(0);">Mes</a></li>
                <li><a class="dropdown-item" id="filtro_todos_diarios" href="javascript:void(0);">Todos</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-reportes-diarios table border-top">
        <thead>
          <tr>
            <th style="width: 90px !important;">Fecha</th>
            <th>Compras € oro</th>
            <th>Compras grs oro</th>
            <th>Compras € plata</th>
            <th>Compras grs plata</th>
            <th>Empeños €</th>
            <th>Empeños grs</th>
            <th>Renovaciones €</th>
            <th>Ventas</th>
            <th>Gastos</th>
            <th>Stock</th>
            <th>Sucursal</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditarInformeDiario" tabindex="-1" aria-labelledby="modalEditarInformeDiarioLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header card-header-forms pb-3">
        <h5 class="modal-title" id="modalEditarInformeDiarioLabel">Editar reporte diario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formEditarInformeDiario">
          <input type="hidden" id="editar_informe_id" name="id_informe">
          <div class="mb-3">
            <p class="mb-1 text-muted">Fecha</p>
            <h6 id="editar_informe_fecha">-</h6>
          </div>
          <div class="mb-3">
            <p class="mb-1 text-muted">Sucursal</p>
            <h6 id="editar_informe_sucursal">-</h6>
          </div>
          <div class="mb-2">
            <label class="form-label" for="editar_informe_total_gastos">Gastos (€)</label>
            <input type="number" class="form-control" id="editar_informe_total_gastos" name="total_gastos" step="0.01" min="0" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarInformeDiario">
          <i class="icon-base ri ri-save-line me-1"></i>Guardar
        </button>
      </div>
    </div>
  </div>
</div>
