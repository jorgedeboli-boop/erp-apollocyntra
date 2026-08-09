<script>
function desactivarServicio(idServicio) {
  Swal.fire({
    icon: 'warning',
    title: '¿Desactivar servicio?',
    text: 'El servicio dejará de estar activo en el catálogo.',
    showCancelButton: true,
    confirmButtonText: 'Sí, desactivar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc3545',
  }).then(function (result) {
    if (!result.isConfirmed) return;
    Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
    var fd = new FormData();
    fd.append('id_servicio', idServicio);
    fetch('parts/servicios/main/desactivar_servicio.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          Swal.fire({ icon: 'success', title: 'Listo', text: data.message || 'Servicio desactivado' }).then(function () {
            window.location.reload();
          });
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: data.error || 'No se pudo desactivar' });
        }
      })
      .catch(function () {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red' });
      });
  });
}
</script>
