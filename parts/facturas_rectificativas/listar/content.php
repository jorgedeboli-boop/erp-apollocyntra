<div class="container-fluid flex-grow-1 container-p-y">

  <!-- Facturas Rectificativas List Table -->
  <div class="card card-mobile-not-shadow">
      
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
        
        <div class="d-flex justify-content-between align-items-center w-100">
            <h5 class="card-title mb-0">Facturas rectificativas</h5>

            <button type="button" class="btn btn-text btn-sm waves-effect p-0  d-inline-flex d-sm-none" data-bs-toggle="collapse" data-bs-target="#collapse_filtros" aria-expanded="false" aria-controls="collapse_filtros"><i class="icon-base ri ri-equalizer-3-line icon-16px me-2"></i>filtrar</button>
        </div>
        
    </div>
      
    <div class="card-body pb-0">
        
        <div class="collapse d-lg-block" id="collapse_filtros">
            <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 select2-btn-height">
                <div class="col-md-2 factura_rect_sucursal">
                  <!-- El filtro de sucursal se creará dinámicamente -->
                </div>
            </div>

        </div>
        
    </div>
      
    <div class="card-datatable table-responsive">
      <table class="datatables-facturas-rectificativas table border-top">
        <thead>
          <tr>
            <th>Nº factura</th>
            <th>FECHA</th>
            <th>HORA</th>
            <th>CLIENTE</th>
            <th>SUCURSAL</th>
            <th>TOTAL</th>
            <th>ESTADO</th>
            <th>TIPO PAGO</th>
            <th>FACT. ORIGINAL</th>
            <th>ESTADO FISKALY</th>
            <th>ACCIONES</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

</div>
