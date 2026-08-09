<?php
$vLoadStats = filemtime(__DIR__ . '/load_stats.js');
$vTablesDatatablesLoad = filemtime(__DIR__ . '/tables-datatables-load.js');
?>
<script src="parts/bancos_config/listar/load_stats.js?v=<?php echo $vLoadStats; ?>"></script>
<script src="parts/bancos_config/listar/tables-datatables-load.js?v=<?php echo $vTablesDatatablesLoad; ?>"></script>

<script>
function eliminarBancoConfig(idBanco) {
  Swal.fire({
    title: '¿Eliminar banco?',
    text: 'Esta acción eliminará el banco. No se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    showCloseButton: true,
    allowOutsideClick: false
  }).then(function (result) {
    if (!result.isConfirmed) {
      return;
    }

    Swal.fire({
      title: 'Eliminando...',
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      }
    });

    const formData = new FormData();
    formData.append('id_banco', idBanco);

    fetch('parts/bancos_config/listar/eliminar_banco.php', {
      method: 'POST',
      body: formData
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
      })
      .then(function (data) {
        if (!data.success) {
          throw new Error(data.message || 'Error al eliminar el banco');
        }
        Swal.fire({
          title: 'Eliminado',
          text: data.message,
          icon: 'success',
          confirmButtonText: 'Aceptar'
        }).then(function () {
          if (window.dt_bancos_config) {
            window.dt_bancos_config.ajax.reload();
          }
          if (typeof cargarEstadisticasBancos === 'function') {
            cargarEstadisticasBancos();
          }
        });
      })
      .catch(function (error) {
        Swal.fire({
          title: 'Error',
          text: error.message || 'Error al eliminar el banco',
          icon: 'error',
          confirmButtonText: 'Aceptar'
        });
      });
  });
}
</script>
