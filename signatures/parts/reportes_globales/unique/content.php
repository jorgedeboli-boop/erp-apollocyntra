<?php
$fecha_hoy = date('Y-m-d');
$fecha_hoy_txt = date('d-m-Y');
?>
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="card card-action card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages position-relative">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Reportes Globales <span id="texto_reportes_globales_titulo"></span></h5>
        <button type="button" class="btn btn-text btn-sm waves-effect p-0 d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros_reportes_globales" aria-expanded="false" aria-controls="collapse_filtros_reportes_globales">
          <i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar
        </button>
      </div>
      <div class="card-action-element btn-header-card-right" style="right: 22px;top: 11px;">
        <ul class="list-inline mb-0">
          <li class="list-inline-item">
            <a href="javascript:void(0);" class="card-expand" aria-label="Pantalla completa"><i class="icon-base ri ri-fullscreen-fill icon-sm"></i></a>
          </li>
        </ul>
      </div>
    </div>

    <div class="card-body pb-0 totales">
      <div class="collapse d-lg-block" id="collapse_filtros_reportes_globales">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
          <div class="col-md-3">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_empresa">
              <option value="">Todas las empresas</option>
              <?php foreach (obtener_empresas() as $empresa): ?>
                <option value="<?php echo (int) $empresa['id_empresa']; ?>">
                  <?php echo htmlspecialchars($empresa['nombre_empresa']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-select select2 select2-filter select2-custom" id="filtro_sucursal">
              <option value="">Todas las sucursales</option>
              <?php foreach (obtener_sucursales() as $sucursal): ?>
                <option value="<?php echo (int) $sucursal['id_sucursal']; ?>">
                  <?php echo htmlspecialchars($sucursal['nombre_sucursal']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <div class="input-group">
              <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Selecciona fechas" value="<?php echo htmlspecialchars($fecha_hoy_txt . ' > ' . $fecha_hoy_txt, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
              <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde" value="<?php echo htmlspecialchars($fecha_hoy, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta" value="<?php echo htmlspecialchars($fecha_hoy, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="filtro_periodo" id="filtro_periodo" value="dia">
              <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" id="filtro_por_fecha_informe" href="javascript:void(0);">Por fecha</a></li>
                <li><a class="dropdown-item" id="filtro_dia" href="javascript:void(0);">Día</a></li>
                <li><a class="dropdown-item" id="filtro_mes" href="javascript:void(0);">Mes</a></li>
                <li><a class="dropdown-item" id="filtro_todos" href="javascript:void(0);">Todos</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="dt-fixedcolumns datatables-reportes-globales table border-top">
        <thead>
          <tr>
            <th rowspan="2" class="text-bg-Dark rs-grupo-top rs-grupo-bottom rs-grupo-full text-center" data-dt-order="disable">Fecha</th>
            <th rowspan="2" class="text-bg-Dark bg-label-Dark rs-grupo-top rs-grupo-bottom rs-grupo-full text-aling-left" data-dt-order="disable">Sucursal</th>
            <th rowspan="2" class="text-bg-Dark bg-label-Dark rs-grupo-top rs-grupo-bottom rs-grupo-full text-center" data-dt-order="disable">Rango lotes</th>
            <th colspan="4" class="text-bg-warning rs-grupo-top" data-dt-order="disable">Compras oro</th>
            <th colspan="4" class="text-bg-secondary rs-grupo-top" data-dt-order="disable">Compras plata</th>
            <th colspan="4" class="text-bg-primary rs-grupo-top" data-dt-order="disable">Empeños</th>
            <th colspan="3" class="text-bg-info rs-grupo-top" data-dt-order="disable">Empeños retirados</th>
            <th colspan="3" class="text-bg-danger rs-grupo-top" data-dt-order="disable">Lotes intervenidos</th>
            <th colspan="5" class="text-bg-success rs-grupo-top" data-dt-order="disable">Ventas</th>
            <th rowspan="2" class="text-bg-danger rs-grupo-top rs-grupo-bottom rs-grupo-full" data-dt-order="disable">Gastos</th>
            <th colspan="6" class="text-bg-info rs-grupo-top" data-dt-order="disable">Métodos pago</th>
            <th rowspan="2" class="reportes-globales-col-oculta" aria-hidden="true">ID</th>
            <th rowspan="2" class="reportes-globales-col-oculta" aria-hidden="true">Meta</th>
            <th rowspan="2" class="reportes-globales-col-oculta" aria-hidden="true">Empresa</th>
          </tr>
          <tr>
            <th class="text-bg-warning border-0 rs-grupo-bottom rs-grupo-full">Pagado</th>
            <th class="text-bg-warning border-0 rs-grupo-bottom rs-grupo-full">Lotes</th>
            <th class="text-bg-warning border-0 rs-grupo-bottom rs-grupo-full">Peso</th>
            <th class="text-bg-warning border-0 rs-grupo-bottom rs-grupo-full">Media €/gr</th>
            <th class="text-bg-secondary border-0 rs-grupo-bottom rs-grupo-full">Pagado</th>
            <th class="text-bg-secondary border-0 rs-grupo-bottom rs-grupo-full">Peso</th>
            <th class="text-bg-secondary border-0 rs-grupo-bottom rs-grupo-full">Lotes</th>
            <th class="text-bg-secondary border-0 rs-grupo-bottom rs-grupo-full">Media €/gr</th>
            <th class="text-bg-primary border-0 rs-grupo-bottom rs-grupo-full">Total</th>
            <th class="text-bg-primary border-0 rs-grupo-bottom rs-grupo-full">Pagado</th>
            <th class="text-bg-primary border-0 rs-grupo-bottom rs-grupo-full">Peso</th>
            <th class="text-bg-primary border-0 rs-grupo-bottom rs-grupo-full">Beneficios Renovaciones</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Total</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Valor</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Peso</th>
            <th class="text-bg-danger border-0 rs-grupo-bottom rs-grupo-full">Lotes</th>
            <th class="text-bg-danger border-0 rs-grupo-bottom rs-grupo-full">Pagado</th>
            <th class="text-bg-danger border-0 rs-grupo-bottom rs-grupo-full">Peso</th>
            <th class="text-bg-success border-0 rs-grupo-bottom rs-grupo-full">Total ventas</th>
            <th class="text-bg-success border-0 rs-grupo-bottom rs-grupo-full">Cobrado</th>
            <th class="text-bg-success border-0 rs-grupo-bottom rs-grupo-full">Beneficio</th>
            <th class="text-bg-success border-0 rs-grupo-bottom rs-grupo-full">Total ventas plazos</th>
            <th class="text-bg-success border-0 rs-grupo-bottom rs-grupo-full">Euros ventas plazo</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Contado entrada</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Contado salida</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Tarjeta</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Transferencia entrada</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Transferencia salida</th>
            <th class="text-bg-info border-0 rs-grupo-bottom rs-grupo-full">Bizum</th>
          </tr>
        </thead>
      </table>
    </div>

    <!-- Modal lotes del día -->
    <div class="modal fade modal-draggable" id="modalEditarInformeGlobal" tabindex="-1" aria-labelledby="modalEditarInformeGlobalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header card-header-forms pb-3 modal-draggable-handle">
            <h5 class="modal-title" id="modalEditarInformeGlobalLabel">
              Lotes del <span id="editar_fecha_informe">-</span>
              · <span id="editar_informe_sucursal">-</span>
              <small class="text-muted ms-2" id="editar_nombre_empresa">-</small>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body" style="padding-inline: 0px !important;">
            <div id="lotesInformeLoading" class="text-center py-5 d-none">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="mt-2 mb-0 text-muted">Cargando lotes…</p>
            </div>
            <div id="lotesInformeVacio" class="alert alert-secondary d-none mb-0" role="alert">
              No hay lotes para esta sucursal en esta fecha.
            </div>
            <div id="lotesInformeError" class="alert alert-danger d-none mb-0" role="alert"></div>
            <div class="table-responsive" id="lotesInformeTablaWrap">
              <table class="table table-sm table-hover align-middle mb-0" id="tablaLotesInforme">
                <thead>
                  <tr>
                    <th>Lote Nº</th>
                    <th>Fecha compra</th>
                    <th>Tipo</th>
                    <th>Empeño</th>
                    <th class="text-end">Peso neto</th>
                    <th class="text-end">Peso bruto</th>
                    <th class="text-end">Merma</th>
                    <th>Fecha vencimiento</th>
                    <th class="text-center">Total artículos</th>
                    <th class="text-end">Peso artículos</th>
                  </tr>
                </thead>
                <tbody id="lotesInformeTbody"></tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

  <div id="totales_reportes_globales" class="d-flex flex-wrap gap-2 mt-4 px-3 pb-4">

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-primary h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-primary bg-transparent">
                <i class="icon-base ri ri-apps-2-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Total lotes</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Cantidad: <span class="totals" id="rg-total-lotes">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Peso: <span class="totals" id="rg-total-lotes-gramos">0</span> grs
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Precio compra: <span class="totals" id="rg-total-lotes-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-primary h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-primary bg-transparent">
                <i class="icon-base ri ri-apps-2-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Total compras</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Cantidad: <span class="totals" id="rg-total-compras">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Peso: <span class="totals" id="rg-total-compras-gramos">0</span> grs
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Precio compra: <span class="totals" id="rg-total-compras-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Media pagado: <span class="totals" id="rg-total-compras-media">0</span> € / gramo
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-primary h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-primary bg-transparent">
                <i class="icon-base ri ri-apps-2-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Total empeños</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Cantidad: <span class="totals" id="rg-total-empenos">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Peso: <span class="totals" id="rg-total-empenos-gramos">0</span> grs
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Precio compra: <span class="totals" id="rg-total-empenos-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Media pagado: <span class="totals" id="rg-total-empenos-media">0</span> € / gramo
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-warning h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-warning bg-transparent">
                <i class="icon-base ri ri-copper-coin-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Total oro</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Cantidad: <span class="totals" id="rg-total-oro">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Peso: <span class="totals" id="rg-total-oro-gramos">0</span> grs
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Precio compra: <span class="totals" id="rg-total-oro-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Media pagado: <span class="totals" id="rg-total-oro-media">0</span> € / gramo
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-secondary h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-secondary bg-transparent">
                <i class="icon-base ri ri-coin-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Total plata</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Cantidad: <span class="totals" id="rg-total-plata">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Peso: <span class="totals" id="rg-total-plata-gramos">0</span> grs
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Precio compra: <span class="totals" id="rg-total-plata-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Media pagado: <span class="totals" id="rg-total-plata-media">0</span> € / gramo
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-success h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-success bg-transparent">
                <i class="icon-base ri ri-shopping-bag-3-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Totales ventas</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total ventas: <span class="totals" id="rg-total-ventas">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Cobrado: <span class="totals" id="rg-total-euros-ventas">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Coste artículos: <span class="totals" id="rg-total-coste-art-venta">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total beneficio ventas: <span class="totals" id="rg-total-beneficio-ventas">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Base imponible: <span class="totals" id="rg-base-imponible-ventas">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Cuota de IVA: <span class="totals" id="rg-cuota-iva-ventas">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Beneficio final: <span class="totals" id="rg-beneficio-final-ventas">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-success h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-success bg-transparent">
                <i class="icon-base ri ri-calendar-schedule-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Ventas a plazos</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total ventas plazos: <span class="totals" id="rg-total-ventas-plazo">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Euros plazos: <span class="totals" id="rg-total-euros-ventas-plazo">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-primary h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-primary bg-transparent">
                <i class="icon-base ri ri-refresh-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Beneficio renovaciones</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total renovaciones: <span class="totals" id="rg-total-euros-renovaciones">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">IVA renovaciones: <span class="totals" id="rg-iva-renovaciones">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Beneficio: <span class="totals" id="rg-beneficio-renovaciones">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-danger h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-danger bg-transparent">
                <i class="icon-base ri ri-money-euro-circle-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Total gastos</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Gastos: <span class="totals" id="rg-total-gastos">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-danger h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-danger bg-transparent">
                <i class="icon-base ri ri-alarm-warning-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Lotes intervenidos</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Lotes: <span class="totals" id="rg-total-lotes-intervenidos">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Pagado: <span class="totals" id="rg-total-euros-intervenidos">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Peso: <span class="totals" id="rg-total-gramos-intervenidos">0</span> grs
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 200px;">
      <div class="card card-border-shadow-info h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-info bg-transparent">
                <i class="icon-base ri ri-bank-card-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Métodos de pago</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Contado entrada: <span class="totals" id="rg-total-contado-entrada">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Contado salida: <span class="totals" id="rg-total-contado-salida">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Tarjeta: <span class="totals" id="rg-total-tarjeta">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Transf. entrada: <span class="totals" id="rg-total-transf-entrada">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Transf. salida: <span class="totals" id="rg-total-transf-salida">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Bizum: <span class="totals" id="rg-total-bizum">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 200px;">
      <div class="card card-border-shadow-warning h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-warning bg-transparent">
                <i class="icon-base ri ri-error-warning-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Peso descuadrado Oro</h5>
          </div>
          <div id="rg-lotes-descuadrados-loading" class="text-muted small mb-2 d-none">
            <span class="spinner-border spinner-border-sm text-warning me-1" role="status"></span>
            Cargando…
          </div>
          <div id="rg-lotes-descuadrados-vacio" class="text-muted small mb-0 d-none">
            Sin lotes descuadrados.
          </div>
          <div id="rg-lotes-descuadrados-lista" class="rg-lotes-descuadrados-lista"></div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-success h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-success bg-transparent">
                <i class="icon-base ri ri-money-euro-circle-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Ventas contado</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total: <span class="totals" id="rg-ventas-contado">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total euros: <span class="totals" id="rg-ventas-contado-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Media / venta: <span class="totals" id="rg-ventas-contado-media">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-info h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-info bg-transparent">
                <i class="icon-base ri ri-bank-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Ventas transferencia</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total: <span class="totals" id="rg-ventas-transferencia">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total euros: <span class="totals" id="rg-ventas-transferencia-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Media / venta: <span class="totals" id="rg-ventas-transferencia-media">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-primary h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-primary bg-transparent">
                <i class="icon-base ri ri-bank-card-2-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Ventas tarjeta</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total: <span class="totals" id="rg-ventas-tarjeta">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total euros: <span class="totals" id="rg-ventas-tarjeta-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Media / venta: <span class="totals" id="rg-ventas-tarjeta-media">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="flex-fill" style="min-width: 220px;">
      <div class="card card-border-shadow-secondary h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="avatar me-1">
              <span class="avatar-initial rounded-3 bg-label-secondary bg-transparent">
                <i class="icon-base ri ri-smartphone-fill icon-32px"></i>
              </span>
            </div>
            <h5 class="mb-0">Ventas bizum</h5>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total: <span class="totals" id="rg-ventas-bizum">0</span>
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Total euros: <span class="totals" id="rg-ventas-bizum-euros">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              </div>
            </span>
          </div>
          <div class="d-flex align-items-center">
            <span class="me-1 fw-medium">Media / venta: <span class="totals" id="rg-ventas-bizum-media">0</span> €
              <div class="stats-loading" style="display: none;">
                <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
              </div>
            </span>
          </div>
        </div>
      </div>
    </div>

  </div>

  </div>
</div>
