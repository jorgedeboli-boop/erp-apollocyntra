/**
 * Estadísticas de sellos
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  cargarEstadisticasSellos();
});

function cargarEstadisticasSellos() {
  document.querySelectorAll('.stats-loading').forEach(function (el) {
    el.style.display = 'block';
  });

  var elTotal = document.getElementById('total-sellos');
  var elLogotipo = document.getElementById('total-sellos-logotipo');
  var elFecha = document.getElementById('fecha-ultimo-sello');

  if (elTotal) elTotal.style.display = 'none';
  if (elLogotipo) elLogotipo.style.display = 'none';
  if (elFecha) elFecha.style.display = 'none';

  fetch('parts/sellos/listar/load_stats.php')
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (!data.success) {
        console.error('Error al cargar estadísticas:', data.error);
        return;
      }

      if (elTotal) {
        elTotal.textContent = data.total_sellos;
        elTotal.style.display = 'block';
      }

      if (elLogotipo) {
        elLogotipo.textContent = data.total_con_logotipo;
        elLogotipo.style.display = 'block';
      }

      if (elFecha) {
        if (data.fecha_ultimo) {
          var fecha = new Date(data.fecha_ultimo);
          if (!isNaN(fecha.getTime())) {
            elFecha.textContent = fecha.toLocaleDateString('es-ES');
          } else {
            elFecha.textContent = '-';
          }
        } else {
          elFecha.textContent = '-';
        }
        elFecha.style.display = 'block';
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

window.cargarEstadisticasSellos = cargarEstadisticasSellos;
