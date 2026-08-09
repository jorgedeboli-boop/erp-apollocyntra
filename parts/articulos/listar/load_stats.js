/**
 * Cargar estadísticas de artículos venta
 */

function cargarEstadisticas() {
  cargarEstadistica('total-articulos', 'total');
  cargarEstadistica('total-enventa', 'total-enventa');
  cargarEstadistica('total-vendidos', 'total-vendidos');
  cargarEstadistica('total-reservados', 'total-reservados');
}

function cargarEstadistica(elementId, tipo) {
  const elemento = document.getElementById(elementId);
  const loadingElement = elemento ? elemento.parentElement.querySelector('.stats-loading') : null;
  
  if (!elemento || !loadingElement) {
    console.error('Elemento no encontrado:', elementId);
    return;
  }
  
  // Mostrar loading
  elemento.style.display = 'none';
  loadingElement.style.display = 'block';
  
  // Obtener filtros activos
  const filtroSucursal = document.getElementById('filtro_sucursal_articulo');
  const filtroTipo = document.getElementById('filtro_tipo');
  const filtroEstado = document.getElementById('filtro_estado');
  const filtroOrigen = document.getElementById('filtro_origen');
  const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
  const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
  
  // Preparar datos
  const formData = new FormData();
  formData.append('tipo', tipo);
  formData.append('filtro_sucursal', filtroSucursal ? filtroSucursal.value : '');
  formData.append('filtro_tipo', filtroTipo ? filtroTipo.value : '');
  formData.append('filtro_estado', filtroEstado ? filtroEstado.value : '');
  formData.append('filtro_origen', filtroOrigen ? filtroOrigen.value : '');
  formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
  formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
  formData.append('filtro_periodo', window.filtro_periodo_activo || 'todos');
  formData.append('filtro_tipo_fecha', window.filtro_tipo_fecha || 'vendido');
  
  fetch('parts/articulos/listar/load_stats.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      elemento.textContent = data.total;
    } else {
      elemento.textContent = '0';
      console.error('Error al cargar estadística:', data.error);
    }
  })
  .catch(error => {
    elemento.textContent = '0';
    console.error('Error:', error);
  })
  .finally(() => {
    loadingElement.style.display = 'none';
    elemento.style.display = 'block';
  });
}

// Cargar estadísticas al iniciar
document.addEventListener('DOMContentLoaded', function() {
  cargarEstadisticas();
});

