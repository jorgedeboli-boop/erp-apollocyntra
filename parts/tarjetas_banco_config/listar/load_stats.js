document.addEventListener('DOMContentLoaded', function () {
  cargarEstadisticasTarjetasBanco();
});

function cargarEstadisticasTarjetasBanco() {
  document.querySelectorAll('.stats-loading').forEach(function (el) {
    el.style.display = 'block';
  });
  var elTotal = document.getElementById('total-tarjetas');
  var elDefecto = document.getElementById('total-tarjetas-defecto');
  if (elTotal) elTotal.style.display = 'none';
  if (elDefecto) elDefecto.style.display = 'none';

  fetch('parts/tarjetas_banco_config/listar/load_stats.php')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) return;
      if (elTotal) {
        elTotal.textContent = data.total_tarjetas;
        elTotal.style.display = 'block';
      }
      if (elDefecto) {
        elDefecto.textContent = data.total_defecto;
        elDefecto.style.display = 'block';
      }
    })
    .finally(function () {
      document.querySelectorAll('.stats-loading').forEach(function (el) {
        el.style.display = 'none';
      });
    });
}
