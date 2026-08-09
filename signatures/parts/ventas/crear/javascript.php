<?php require __DIR__ . '/../inc_script_max_total_factura_simplificada.php'; ?>
<?php
$vNuevaVenta = filemtime(__DIR__ . '/nueva-venta.js');
$vOffcanvasAddPayment = filemtime(__DIR__ . '/../../../assets/js/offcanvas-add-payment.js');
?>
<script src="parts/ventas/crear/nueva-venta.js?v=<?php echo $vNuevaVenta; ?>"></script>

<script src="assets/js/offcanvas-add-payment.js?v=<?php echo $vOffcanvasAddPayment; ?>"></script>
