<div class="container-fluid flex-grow-1 container-p-y">
<?php require_once __DIR__ . '/../../universal/filtros_opciones_control_etiquetado.php'; ?>
  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h5 class="card-title mb-0">
          Control de etiquetado
          <span id="texto_control_etiquetado_filtros_titulo" class="text-muted fs-6"></span>
        </h5>
      </div>

      <div class="d-flex justify-content-between align-items-center row gx-5 pt-4 gap-5 gap-md-0 mt-3 listar-filtros-container" id="control_etiquetado_filtros_container">
        <div class="col-12 col-md-3 control_etiquetado_sucursal select2-btn-height">
          <select id="filtro_sucursal_control_etiquetado" class="form-select select2-filter text-capitalize select2-custom">
            <option value="">Sucursal</option>
          </select>
        </div>
        <div class="col-12 col-md-3 control_etiquetado_tipo select2-btn-height">
          <select id="filtro_tipo_control_etiquetado" class="form-select select2-filter text-capitalize select2-custom">
            <?php control_etiquetado_imprimir_opciones_tipo(); ?>
          </select>
        </div>

        <div class="col-12 col-md-4">
          <div class="input-group">
            <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas">
            <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
            <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
            <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" id="filtro_por_fecha_control_etiquetado" href="javascript:void(0);">Por fecha de etiquetado</a></li>
              <li><a class="dropdown-item" id="filtro_dia_control_etiquetado" href="javascript:void(0);">Hoy</a></li>
              <li><a class="dropdown-item" id="filtro_mes_control_etiquetado" href="javascript:void(0);">Mes</a></li>
              <li><a class="dropdown-item" id="filtro_todos_control_etiquetado" href="javascript:void(0);">Todos</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive pt-0">
      <table class="datatables-control-etiquetado table border-top">
        <thead>
          <tr>
            <th>Nº</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Usuario</th>
            <th>Sucursal</th>
            <th>Envío Nº</th>
            <th>Total etiquetas</th>
            <th>Tipo</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
<!-- / Content -->
