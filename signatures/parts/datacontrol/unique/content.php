<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <h4 class="mb-1">Datacontrol</h4>
      <p class="mb-4 text-muted">
        Controles de consistencia entre tablas por sucursal. Los scripts de referencia viven en
        <code>control_data/</code> en la raíz del proyecto.
      </p>
    </div>
  </div>

  <div class="row">
    <div class="col-12 col-lg-8">
      <div class="card mb-4">
        <div class="card-header">
          <span class="fw-semibold">Ejecutar controles</span>
        </div>
        <div class="card-body d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-primary" id="btnControlArticulosLotes">
            <i class="icon-base ri ri-database-2-line me-1"></i>Control artículos → lotes
          </button>
          <button type="button" class="btn btn-warning" id="btnControlArticulosVenta">
            <i class="icon-base ri ri-shopping-bag-line me-1"></i>Control artículos venta
          </button>
          <button type="button" class="btn btn-info" id="btnControlLotes">
            <i class="icon-base ri ri-stack-line me-1"></i>Control lotes
          </button>
        </div>
        <div class="card-body pt-0">
          <div id="datacontrolStatus" class="small text-muted mb-2"></div>
          <pre id="datacontrolOutput" class="p-3 rounded bg-label-secondary text-body small mb-0" style="max-height: 420px; overflow: auto; white-space: pre-wrap;">Listo.</pre>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4">
      <div class="card mb-4">
        <div class="card-header">
          <span class="fw-semibold">Notas</span>
        </div>
        <div class="card-body small">
          <p class="mb-2"><strong>Artículos → lotes:</strong> conteos por sucursal y registro en <code>control_articulos_tablas</code>; luego <strong>solo INSERT</strong> en <code>articulos_lotes</code> de lo que falta. No se hace UPDATE de filas ya existentes.</p>
          <p class="mb-0"><strong>Venta / Lotes:</strong> pendientes de especificación en backend.</p>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- / Content -->
