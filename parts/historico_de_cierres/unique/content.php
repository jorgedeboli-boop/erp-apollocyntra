<div class="container-fluid flex-grow-1 container-p-y">
  
  <!-- Histórico de Cierres Table -->
  <div class="card">
    <div class="card-header border-bottom card-header-forms">
      <h5 class="card-title mb-0">Histórico de Cierres de Caja</h5>
      
      <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-5 gap-md-0 mt-0">
        <div class="col-md-6">
          <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Seleccionar rango de fechas">
          <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
          <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
        </div>
        <div class="col-md-3">
          <div class="d-flex gx-1 gap-1">
            <button type="button" class="btn btn-primary" id="filtro_hoy">Hoy</button>
            <button type="button" class="btn btn-primary" id="filtro_mes">Mes</button>
            <button type="button" class="btn btn-primary active" id="filtro_todos">Todos</button>
          </div>
        </div>
        <div class="col-md-3">
          <div class="filtro_sucursal select2-btn-height">
            <!-- El filtro de sucursal se creará dinámicamente -->
          </div>
        </div>
      </div>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-historico-cierres table border-top">
        <thead>
          <tr>
            <th>Nº Arqueo</th>
            <th>Fecha Arqueo</th>
            <th>Sucursal</th>
            <th>Caja</th>
            <th>Efectivo</th>
            <th>Diferencia</th>
            <th>Usuario</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<!-- Modal Detalle Arqueo -->
<div class="modal fade" id="modalDetalleArqueo" tabindex="-1" aria-labelledby="modalDetalleArqueoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDetalleArqueoLabel">Detalle Arqueo #<span id="modal-arqueo-id"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-8">
            <div class="card">
              <div class="card-content">
                <div class="table-responsive text-nowrap">
                  <table class="table table-borderless" id="table_detalle_arqueo">
                    <thead>
                      <tr>
                        <th>Efectivo</th>
                        <th class="text-center">Unidades</th>
                        <th class="text-center">Total</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td><strong>Billetes de 500 €</strong></td>
                        <td class="text-center" id="detalle-billete500">0</td>
                        <td class="text-center" id="total-billete500">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Billetes de 200 €</strong></td>
                        <td class="text-center" id="detalle-billete200">0</td>
                        <td class="text-center" id="total-billete200">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Billetes de 100 €</strong></td>
                        <td class="text-center" id="detalle-billete100">0</td>
                        <td class="text-center" id="total-billete100">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Billetes de 50 €</strong></td>
                        <td class="text-center" id="detalle-billete50">0</td>
                        <td class="text-center" id="total-billete50">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Billetes de 20 €</strong></td>
                        <td class="text-center" id="detalle-billete20">0</td>
                        <td class="text-center" id="total-billete20">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Billetes de 10 €</strong></td>
                        <td class="text-center" id="detalle-billete10">0</td>
                        <td class="text-center" id="total-billete10">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Billetes de 5 €</strong></td>
                        <td class="text-center" id="detalle-billete5">0</td>
                        <td class="text-center" id="total-billete5">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Monedas de 2 €</strong></td>
                        <td class="text-center" id="detalle-moneda2">0</td>
                        <td class="text-center" id="total-moneda2">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Monedas de 1 €</strong></td>
                        <td class="text-center" id="detalle-moneda1">0</td>
                        <td class="text-center" id="total-moneda1">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Monedas de 0.50 €</strong></td>
                        <td class="text-center" id="detalle-moneda50cent">0</td>
                        <td class="text-center" id="total-moneda50cent">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Monedas de 0.20 €</strong></td>
                        <td class="text-center" id="detalle-moneda20cent">0</td>
                        <td class="text-center" id="total-moneda20cent">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Monedas de 0.10 €</strong></td>
                        <td class="text-center" id="detalle-moneda10cent">0</td>
                        <td class="text-center" id="total-moneda10cent">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Monedas de 0.05 €</strong></td>
                        <td class="text-center" id="detalle-moneda5cent">0</td>
                        <td class="text-center" id="total-moneda5cent">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Monedas de 0.02 €</strong></td>
                        <td class="text-center" id="detalle-moneda2cent">0</td>
                        <td class="text-center" id="total-moneda2cent">0.00 €</td>
                      </tr>
                      <tr>
                        <td><strong>Monedas de 0.01 €</strong></td>
                        <td class="text-center" id="detalle-moneda1cent">0</td>
                        <td class="text-center" id="total-moneda1cent">0.00 €</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="totales-column">
              <!-- Efectivo -->
              <div class="total-box">
                <div class="total-value" id="modal-totalEfectivo">0.00 €</div>
                <div class="total-label">Efectivo</div>
              </div>

              <!-- Diferencia -->
              <div class="total-box" id="modal-boxDiferencia">
                <div class="total-value" id="modal-totalDiferencia">0.00 €</div> 
                <div class="total-label">Diferencia</div>
              </div>

              <!-- Caja -->
              <div class="total-box">
                <div class="total-value" id="modal-totalCaja">0.00 €</div>
                <div class="total-label">Caja</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Los scripts se cargan desde javascript.php -->
