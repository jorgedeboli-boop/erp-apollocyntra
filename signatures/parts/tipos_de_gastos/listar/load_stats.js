/**
 * Carga de estadísticas para Tipos de Gasto
 */

'use strict';

// Función para cargar estadísticas
function cargarEstadisticas() {
  // Mostrar loading en todas las estadísticas
  document.querySelectorAll('.stats-loading').forEach(loading => {
    loading.style.display = 'block';
  });

  // Ocultar valores mientras carga
  document.getElementById('total-tipos-gasto').textContent = '...';
  document.getElementById('total-tipos-activos').textContent = '...';
  document.getElementById('fecha-ultimo-tipo').textContent = '...';

  // Cargar estadísticas desde el servidor
  fetch('parts/tipos_de_gastos/listar/load_stats.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    }
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Actualizar total de tipos de gasto
      document.getElementById('total-tipos-gasto').textContent = data.stats.total_tipos || 0;
      
      // Actualizar tipos activos
      document.getElementById('total-tipos-activos').textContent = data.stats.total_activos || 0;
      
      // Actualizar fecha del último tipo
      if (data.stats.fecha_ultimo) {
        const fecha = new Date(data.stats.fecha_ultimo);
        document.getElementById('fecha-ultimo-tipo').textContent = fecha.toLocaleDateString('es-ES');
      } else {
        document.getElementById('fecha-ultimo-tipo').textContent = '-';
      }
    } else {
      console.error('Error al cargar estadísticas:', data.message);
    }
  })
  .catch(error => {
    console.error('Error al cargar estadísticas:', error);
  })
  .finally(() => {
    // Ocultar loading en todas las estadísticas
    document.querySelectorAll('.stats-loading').forEach(loading => {
      loading.style.display = 'none';
    });
  });
}

// Cargar estadísticas cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
  cargarEstadisticas();
});

// Función para recargar estadísticas (se llama desde otros scripts)
window.cargarEstadisticas = cargarEstadisticas;
