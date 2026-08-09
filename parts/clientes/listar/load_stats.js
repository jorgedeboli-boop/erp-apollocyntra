/**
 * Script para cargar estadísticas de clientes
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
    cargarEstadistica('total_clientes', 'total-clientes');
    cargarEstadistica('clientes_habilitados', 'total-clientes-habilitados');
    cargarEstadistica('clientes_lista_negra', 'total-clientes-lista-negra');
}

/**
 * Función para cargar una estadística específica
 */
function cargarEstadistica(tipo, elementoId) {
    const elemento = document.getElementById(elementoId);
    const loadingElement = elemento.parentElement.querySelector('.stats-loading');
    
    if (!elemento || !loadingElement) {
        console.error('Elemento no encontrado:', elementoId);
        return;
    }
    
    // Mostrar loading
    loadingElement.style.display = 'block';
    
    // Realizar petición AJAX
    fetch('parts/clientes/listar/load_stats.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'tipo=' + encodeURIComponent(tipo)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Actualizar el valor
            elemento.textContent = data.total.toLocaleString();
            
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

/**
 * Función para formatear números con separadores de miles
 */
function formatearNumero(numero) {
    return numero.toLocaleString();
}
