/**
 * Cargar estadísticas de proveedores
 */
document.addEventListener('DOMContentLoaded', function() {
    cargarEstadisticas();
});

function cargarEstadisticas() {
    // Mostrar loading en todas las tarjetas
    document.querySelectorAll('.stats-loading').forEach(el => {
        el.style.display = 'block';
    });
    
    // Ocultar los números mientras se cargan
    document.getElementById('total-proveedores').style.display = 'none';
    document.getElementById('total-proveedores-fundicion').style.display = 'none';
    
    fetch('parts/proveedores/listar/load_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar total de proveedores
                document.getElementById('total-proveedores').textContent = data.total_proveedores;
                document.getElementById('total-proveedores').style.display = 'block';
                
                // Actualizar proveedores con fundición
                document.getElementById('total-proveedores-fundicion').textContent = data.total_fundicion;
                document.getElementById('total-proveedores-fundicion').style.display = 'block';
            } else {
                console.error('Error al cargar estadísticas:', data.error);
            }
        })
        .catch(error => {
            console.error('Error al cargar estadísticas:', error);
        })
        .finally(() => {
            // Ocultar loading en todas las tarjetas
            document.querySelectorAll('.stats-loading').forEach(el => {
                el.style.display = 'none';
            });
        });
}
