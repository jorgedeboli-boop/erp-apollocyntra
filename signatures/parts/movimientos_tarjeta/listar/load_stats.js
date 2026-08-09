/**
 * Script para cargar estadísticas de movimientos tarjeta
 * Actualiza las tarjetas superiores con datos en tiempo real
 */

document.addEventListener('DOMContentLoaded', function() {
    // Cargar estadísticas al iniciar la página
    cargarEstadisticas();
    
    // Recargar estadísticas cada 30 segundos
    setInterval(cargarEstadisticas, 30000);
});

/**
 * Función para cargar todas las estadísticas
 */
function cargarEstadisticas() {
    cargarEstadistica('total_entradas', 'total-entradas', true);
    cargarEstadistica('total_salidas', 'total-salidas', true);
    cargarEstadistica('total_saldo', 'total-saldo', true);
}

/**
 * Función para obtener los filtros actuales
 */
function obtenerFiltrosActuales() {
    const sucursalFilter = document.getElementById('filtro_sucursal');
    const fechaDesdeFilter = document.getElementById('filtro_fecha_desde');
    const fechaHastaFilter = document.getElementById('filtro_fecha_hasta');
    
    const params = new URLSearchParams();
    
    if (sucursalFilter && sucursalFilter.value) {
        params.append('filtro_sucursal', sucursalFilter.value);
    }
    if (fechaDesdeFilter && fechaDesdeFilter.value) {
        params.append('filtro_fecha_desde', fechaDesdeFilter.value);
    }
    if (fechaHastaFilter && fechaHastaFilter.value) {
        params.append('filtro_fecha_hasta', fechaHastaFilter.value);
    }
    
    params.append('filtro_periodo', window.filtro_periodo_activo || 'dia');
    
    return params;
}

/**
 * Función para cargar una estadística específica
 */
function cargarEstadistica(tipo, elementoId, esMoneda = false) {
    const elemento = document.getElementById(elementoId);
    const loadingElement = elemento.parentElement.querySelector('.stats-loading');
    
    if (!elemento || !loadingElement) {
        console.error('Elemento no encontrado:', elementoId);
        return;
    }
    
    // Mostrar loading
    loadingElement.style.display = 'block';
    
    // Obtener filtros actuales
    const params = obtenerFiltrosActuales();
    params.append('tipo', tipo);
    
    // Realizar petición AJAX
    fetch('parts/movimientos_tarjeta/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: params.toString()
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Formatear el valor según el tipo
            if (esMoneda) {
                elemento.textContent = parseFloat(data.total).toLocaleString('es-ES', { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2 
                }) + ' €';
            } else {
                elemento.textContent = parseInt(data.total).toLocaleString('es-ES');
            }
            
            // Ocultar loading
            loadingElement.style.display = 'none';
        } else {
            throw new Error(data.error || 'Error desconocido');
        }
    })
    .catch(error => {
        console.error('Error al cargar estadística:', error);
        
        // Ocultar loading
        loadingElement.style.display = 'none';
        
        // Mostrar error en el elemento
        elemento.textContent = 'Error';
        elemento.style.color = '#dc3545';
    });
}

/**
 * Función para recargar estadísticas manualmente
 */
function recargarEstadisticas() {
    cargarEstadisticas();
}

