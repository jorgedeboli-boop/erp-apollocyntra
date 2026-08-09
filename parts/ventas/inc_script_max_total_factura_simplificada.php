<?php
require __DIR__ . '/inc_max_total_factura_simplificada.php';
?>
<script>
window.MAX_TOTAL_FACTURA_SIMPLIFICADA = <?php echo json_encode($max_total_factura_simplificada); ?>;
</script>
