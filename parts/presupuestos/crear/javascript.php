<?php
$vPresupuestoFormBase = filemtime(__DIR__ . '/../presupuesto-form-base.js');
$vPresupuestoCrear = filemtime(__DIR__ . '/presupuesto-crear.js');
?>
<script src="parts/presupuestos/presupuesto-form-base.js?v=<?php echo $vPresupuestoFormBase; ?>"></script>
<script src="parts/presupuestos/crear/presupuesto-crear.js?v=<?php echo $vPresupuestoCrear; ?>"></script>
