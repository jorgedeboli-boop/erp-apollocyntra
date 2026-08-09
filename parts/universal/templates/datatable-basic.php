<!-- Template: DataTable Básico -->
<!-- Uso: Incluir en content.php para crear listados con DataTable -->
<!-- Variables requeridas: $table_class, $table_id, $columns -->

<div class="card">
  <div class="card-header border-bottom card-header-forms">
    <h5 class="card-title mb-0"><?php echo $module_title ?? 'Listado'; ?></h5>
  </div>
  <div class="card-datatable table-responsive">
    <table class="<?php echo $table_class ?? 'datatables-table'; ?> table border-top" id="<?php echo $table_id ?? 'datatable'; ?>">
      <thead>
        <tr>
          <?php foreach ($columns as $column): ?>
            <th><?php echo $column; ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- 
EJEMPLO DE USO:
$table_class = 'datatables-tipos-gastos';
$table_id = 'datatable-tipos-gastos';
$columns = ['ID', 'NOMBRE', 'DESCRIPCION', 'ACCIONES'];
include 'parts/universal/templates/datatable-basic.php';
-->
