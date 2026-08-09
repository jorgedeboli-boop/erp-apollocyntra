/**
 * Cargar estadísticas de stock (artículos en venta).
 */

function cargarEstadisticas() {
  cargarEstadistica('total-articulos', 'total');
  cargarEstadistica('total-oro', 'total-oro');
  cargarEstadistica('total-plata', 'total-plata');
  cargarEstadistica('total-otros', 'total-otros');
}

function cargarEstadistica(elementId, tipo) {
  const elemento = document.getElementById(elementId);
  const loadingElement = elemento ? elemento.parentElement.querySelector('.stats-loading') : null;

  if (!elemento || !loadingElement) {
    console.error('Elemento no encontrado:', elementId);
    return;
  }

  elemento.style.display = 'none';
  loadingElement.style.display = 'block';

  const filtroSucursal = document.getElementById('filtro_sucursal_articulo');
  const filtroTipo = document.getElementById('filtro_tipo');
  const filtroOrigen = document.getElementById('filtro_origen');
  const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
  const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');

  const formData = new FormData();
  formData.append('tipo', tipo);
  formData.append('filtro_sucursal', filtroSucursal ? filtroSucursal.value : '');
  formData.append('filtro_tipo', filtroTipo ? filtroTipo.value : '');
  formData.append('filtro_origen', filtroOrigen ? filtroOrigen.value : '');
  formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
  formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
  formData.append('filtro_periodo', window.filtro_periodo_activo || 'todos');

  fetch('parts/stock/unique/load_stats.php', {
    method: 'POST',
    body: formData
  })
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.success) {
        elemento.textContent = data.total;
      } else {
        elemento.textContent = '0';
        console.error('Error al cargar estadística:', data.error);
      }
    })
    .catch(function (error) {
      elemento.textContent = '0';
      console.error('Error:', error);
    })
    .finally(function () {
      loadingElement.style.display = 'none';
      elemento.style.display = 'block';
    });
}

document.addEventListener('DOMContentLoaded', function () {
  cargarEstadisticas();
});
