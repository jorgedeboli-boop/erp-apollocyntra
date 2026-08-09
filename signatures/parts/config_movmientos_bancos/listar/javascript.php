<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTables = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/config_movmientos_bancos/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/config_movmientos_bancos/listar/tables-datatables-load.js?v=<?php echo $vTables; ?>"></script>
<script>
function eliminarConfigMovmientoBanco(idConfig) {
  Swal.fire({
    title: '¿Eliminar configuración?',
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
    fd.append('id_config', idConfig);
    fetch('parts/config_movmientos_bancos/listar/eliminar_config.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.success) throw new Error(data.message || 'Error');
        Swal.fire('Eliminada', data.message, 'success').then(function () {
          if (window.dt_config_movmientos_bancos) window.dt_config_movmientos_bancos.ajax.reload();
          if (typeof cargarEstadisticasConfigMovBancos === 'function') cargarEstadisticasConfigMovBancos();
        });
      })
      .catch(function (err) {
        Swal.fire('Error', err.message, 'error');
      });
  });
}
</script>
