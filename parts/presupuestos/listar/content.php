<?php
$conexion_emp = conectar_bd();
$empresas_opts = [];
if ($conexion_emp) {
    $r = mysqli_query($conexion_emp, "SELECT id_empresa, nombre_empresa FROM empresas ORDER BY nombre_empresa ASC");
    if ($r) {
        while ($e = mysqli_fetch_assoc($r)) {
            $empresas_opts[] = $e;
        }
    }
    mysqli_close($conexion_emp);
}
$estados_presupuesto = ['borrador', 'enviado', 'aceptado', 'rechazado', 'caducado', 'facturado', 'cancelado'];
?>
<div class="container-fluid flex-grow-1 container-p-y">

  <div class="card card-mobile-not-shadow">
    <div class="card-header border-bottom card-header-forms titulos-cards-pages">
      <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
        <h5 class="card-title mb-0">Presupuestos</h5>
        <?php if ($puede_acceder_crear): ?>
        <a href="crear_presupuesto.php" class="btn btn-primary waves-effect waves-light px-3 btn-create-record" id="btn_nuevo_presupuesto">
          <span class="icon-base ri ri-add-fill icon-22px me-1"></span>Nuevo presupuesto
        </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-body pb-0">
      <div class="collapse d-lg-block" id="collapse_filtros_presupuestos">
        <div class="d-flex justify-content-between align-items-center row gx-1 pt-4 gap-3 gap-md-0 select2-btn-height">
          <div class="col-md-3">
            <select id="filtro_presupuesto_estado" class="form-select select2-filter select2-custom text-capitalize">
              <option value="">Estado</option>
              <?php foreach ($estados_presupuesto as $est): ?>
                <option value="<?php echo htmlspecialchars($est); ?>"><?php echo htmlspecialchars($est); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <select id="filtro_presupuesto_empresa" class="form-select select2-filter select2-custom">
              <option value="">Empresa</option>
              <?php foreach ($empresas_opts as $emp): ?>
                <option value="<?php echo (int)$emp['id_empresa']; ?>"><?php echo htmlspecialchars($emp['nombre_empresa']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <div class="input-group">
              <input type="text" id="rangeFechas" class="form-control flatpickr-input" placeholder="Rango fechas (creación)">
              <input type="hidden" name="filtro_fecha_desde" id="filtro_fecha_desde">
              <input type="hidden" name="filtro_fecha_hasta" id="filtro_fecha_hasta">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="datatables-presupuestos table border-top">
        <thead>
          <tr>
            <th>Número</th>
            <th>Título</th>
            <th>Cliente</th>
            <th>Total</th>
            <th>Estado</th>
            <th>Fecha creación</th>
            <th>Validez</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

</div>
