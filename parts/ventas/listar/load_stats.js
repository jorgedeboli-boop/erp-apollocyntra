/**
 * Cargar estadísticas de ventas
 */

function cargarEstadisticas() {
  cargarEstadistica('total-ventas', 'total');
  cargarEstadistica('total-importe', 'total-importe');
  cargarEstadistica('total-web', 'total-web');
  cargarEstadistica('total-plazos', 'total-plazos');
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
  const filtroSucursal = document.getElementById('filtro_sucursal');
  const filtroTipoVenta = document.getElementById('filtro_tipo_venta');
  const filtroVentaWeb = document.getElementById('filtro_venta_web');
  const filtroFormaPago = document.getElementById('filtro_forma_pago');
  const filtroFechaDesde = document.getElementById('filtro_fecha_desde');
  const filtroFechaHasta = document.getElementById('filtro_fecha_hasta');
  
  // Preparar datos
  const formData = new FormData();
  formData.append('tipo', tipo);
  formData.append('filtro_sucursal', filtroSucursal ? filtroSucursal.value : '');
  formData.append('filtro_tipo_venta', filtroTipoVenta ? filtroTipoVenta.value : '');
  formData.append('filtro_venta_web', filtroVentaWeb ? filtroVentaWeb.value : '');
  formData.append('filtro_forma_pago', filtroFormaPago ? filtroFormaPago.value : '');
  formData.append('filtro_fecha_desde', filtroFechaDesde ? filtroFechaDesde.value : '');
  formData.append('filtro_fecha_hasta', filtroFechaHasta ? filtroFechaHasta.value : '');
  formData.append('filtro_periodo', window.filtro_periodo_activo || 'todos');
  
  fetch('parts/ventas/listar/load_stats.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      if (tipo === 'total-importe') {
        elemento.textContent = data.total + ' €';
      } else {
        elemento.textContent = data.total;
      }
    } else {
      elemento.textContent = tipo === 'total-importe' ? '0 €' : '0';
      console.error('Error al cargar estadística:', data.error);
    }
  })
  .catch(error => {
    elemento.textContent = tipo === 'total-importe' ? '0 €' : '0';
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

