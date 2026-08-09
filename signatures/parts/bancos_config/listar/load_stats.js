/**
 * Estadísticas de bancos_config
 */
document.addEventListener('DOMContentLoaded', function () {
  cargarEstadisticasBancos();
});

function cargarEstadisticasBancos() {
  document.querySelectorAll('.stats-loading').forEach(function (el) {
    el.style.display = 'block';
  });

  var elTotal = document.getElementById('total-bancos');
  var elActivos = document.getElementById('total-bancos-activos');
  if (elTotal) elTotal.style.display = 'none';
  if (elActivos) elActivos.style.display = 'none';

  fetch('parts/bancos_config/listar/load_stats.php')
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (!data.success) {
        console.error('Error al cargar estadísticas:', data.error);
        return;
      }
      if (elTotal) {
        elTotal.textContent = data.total_bancos;
        elTotal.style.display = 'block';
      }
      if (elActivos) {
        elActivos.textContent = data.total_activos;
        elActivos.style.display = 'block';
      }
    })
    .catch(function (error) {
      console.error('Error al cargar estadísticas:', error);
    })
    .finally(function () {
      document.querySelectorAll('.stats-loading').forEach(function (el) {
        el.style.display = 'none';
      });
    });
}
