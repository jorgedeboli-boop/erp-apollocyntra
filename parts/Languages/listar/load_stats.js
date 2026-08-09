/**
 * Cargar estadísticas de idiomas
 */
document.addEventListener("DOMContentLoaded", function() {
    cargarEstadisticas();
});

function cargarEstadisticas() {
    fetch("parts/Languages/listar/load_stats.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const totalIdiomasElement = document.getElementById("total-idiomas");
                if (totalIdiomasElement) {
                    totalIdiomasElement.textContent = data.total_idiomas;
                }
                
                const totalActivosElement = document.getElementById("total-idiomas-activos");
                if (totalActivosElement) {
                    totalActivosElement.textContent = data.total_activos;
                }
            } else {
                console.error("Error al cargar estadísticas:", data.error);
            }
        })
        .catch(error => {
            console.error("Error al cargar estadísticas:", error);
        });
}
