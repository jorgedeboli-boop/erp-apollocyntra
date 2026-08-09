<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTables = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/tarjetas_banco_config/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/tarjetas_banco_config/listar/tables-datatables-load.js?v=<?php echo $vTables; ?>"></script>
<script>
function eliminarTarjetaBancoConfig(idTarjeta) {
  Swal.fire({
    title: '¿Eliminar tarjeta?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(function (result) {
    if (!result.isConfirmed) return;
    const fd = new FormData();
    fd.append('id_tarjeta_banco', idTarjeta);
    fetch('parts/tarjetas_banco_config/listar/eliminar_tarjeta.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) throw new Error(data.message || 'Error');
        Swal.fire('Eliminada', data.message, 'success').then(function () {
          if (window.dt_tarjetas_banco_config) window.dt_tarjetas_banco_config.ajax.reload();
          if (typeof cargarEstadisticasTarjetasBanco === 'function') cargarEstadisticasTarjetasBanco();
        });
      })
      .catch(function (err) {
        Swal.fire('Error', err.message, 'error');
      });
  });
}
</script>
