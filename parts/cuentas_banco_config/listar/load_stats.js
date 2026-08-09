document.addEventListener('DOMContentLoaded', function () {
  cargarEstadisticasCuentasBanco();
});

function cargarEstadisticasCuentasBanco() {
  document.querySelectorAll('.stats-loading').forEach(function (el) {
    el.style.display = 'block';
  });
  var elTotal = document.getElementById('total-cuentas');
  var elDefecto = document.getElementById('total-cuentas-defecto');
  if (elTotal) elTotal.style.display = 'none';
  if (elDefecto) elDefecto.style.display = 'none';

  fetch('parts/cuentas_banco_config/listar/load_stats.php')
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data.success) return;
      if (elTotal) {
        elTotal.textContent = data.total_cuentas;
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
