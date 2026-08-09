/**
 * Cargar estadísticas de empresas
 */
document.addEventListener('DOMContentLoaded', function() {
    localStorage.clear();
    cargarEstadisticas();
});

function cargarEstadisticas() {
    // Mostrar loading en todas las tarjetas
    document.querySelectorAll('.stats-loading').forEach(el => {
        el.style.display = 'block';
    });
    
    // Ocultar los números mientras se cargan
    document.getElementById('total-empresas').style.display = 'none';
    document.getElementById('total-empresas-activas').style.display = 'none';
    document.getElementById('total-empresas-nuevas').style.display = 'none';
    
    fetch('parts/fiskaly_manager/listar/load_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar total de empresas
                document.getElementById('total-empresas').textContent = data.total_empresas;
                document.getElementById('total-empresas').style.display = 'block';
                
                // Actualizar empresas activas
                document.getElementById('total-empresas-activas').textContent = data.total_activas;
                document.getElementById('total-empresas-activas').style.display = 'block';
                
                // Actualizar empresas nuevas
                document.getElementById('total-empresas-nuevas').textContent = data.total_nuevas;
                document.getElementById('total-empresas-nuevas').style.display = 'block';
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

