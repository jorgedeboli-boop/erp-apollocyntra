<div class="container-fluid flex-grow-1 container-p-y">

  <!-- Devoluciones List Table -->
  <div class="card card-mobile-not-shadow">
      
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
        
        <div class="d-flex justify-content-between align-items-center w-100">
            <h5 class="card-title mb-0">Devoluciones</h5>
            
            <?php if ($puede_acceder_crear): ?>
            <a href="crear_devolucion.php" class="btn btn-primary waves-effect waves-light px-3"><span class="icon-base ri ri-add-fill icon-22px me-1"></span>Crear devolución</a>
            <?php endif; ?>
        </div>
        
    </div>
      
    <div class="card-datatable table-responsive">
      <table class="datatables-devoluciones table border-top">
        <thead>
          <tr>
            <th>Nº</th>
            <th>VENTA</th>
            <th>FECHA</th>
            <th>CLIENTE</th>
            <th>MOTIVO</th>
            <th>SKU</th>
            <th>DESCRIPCIÓN</th>
            <th>IMPORTE</th>
            <th>FORMA PAGO</th>
            <th>DEV. WEB</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

</div>

<!-- Los scripts se cargan desde javascript.php -->