document.addEventListener('DOMContentLoaded', function () {
  cargarEstadisticasConfigMovBancos();
});

function cargarEstadisticasConfigMovBancos() {
  document.querySelectorAll('.stats-loading').forEach(function (el) {
    el.style.display = 'block';
  });
  var elTotal = document.getElementById('total-configs');
  var elActivas = document.getElementById('total-configs-activas');
  if (elTotal) elTotal.style.display = 'none';
  if (elActivas) elActivas.style.display = 'none';

  fetch('parts/config_movmientos_bancos/listar/load_stats.php')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) return;
      if (elTotal) {
        elTotal.textContent = data.total_configs;
        elTotal.style.display = 'block';
      }
      if (elActivas) {
        elActivas.textContent = data.total_activas;
        elActivas.style.display = 'block';
      }
    })
    .finally(function () {
      document.querySelectorAll('.stats-loading').forEach(function (el) {
        el.style.display = 'none';
      });
    });
}
