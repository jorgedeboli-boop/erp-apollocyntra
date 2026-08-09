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
          window.location.href = 'config_movmientos_bancos.php';
        });
      })
      .catch(function (err) {
        Swal.fire('Error', err.message, 'error');
      });
  });
}
</script>
